<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Dossier extends Model
{
    public const TYPE_YEAR = 'annee';
    public const TYPE_CATEGORY = 'rubrique';
    public const TYPE_SUBCATEGORY = 'sous_dossier';
    public const TYPE_STANDARD = 'standard';

    protected $guarded = [];

    protected $casts = [
        'annee_activite' => 'integer',
        'ordre_affichage' => 'integer',
    ];

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        return $query
            ->where(fn (Builder $visibilityQuery) => static::applyVisibleConstraint($visibilityQuery, $user))
            ->whereDoesntHave('parent', fn (Builder $ancestorQuery) => static::applyForbiddenConstraint($ancestorQuery, $user))
            ->whereDoesntHave('parent.parent', fn (Builder $ancestorQuery) => static::applyForbiddenConstraint($ancestorQuery, $user))
            ->whereDoesntHave('parent.parent.parent', fn (Builder $ancestorQuery) => static::applyForbiddenConstraint($ancestorQuery, $user))
            ->whereDoesntHave('parent.parent.parent.parent', fn (Builder $ancestorQuery) => static::applyForbiddenConstraint($ancestorQuery, $user));
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_YEAR => 'Racine annuelle',
            self::TYPE_CATEGORY => 'Rubrique',
            self::TYPE_SUBCATEGORY => 'Sous-dossier',
            self::TYPE_STANDARD => 'Dossier standard',
        ];
    }

    public static function yearOptions(): array
    {
        return static::query()
            ->whereNotNull('annee_activite')
            ->distinct()
            ->orderByDesc('annee_activite')
            ->pluck('annee_activite', 'annee_activite')
            ->mapWithKeys(fn ($year): array => [(string) $year => (string) $year])
            ->all();
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if (! $user->can('ged.dossiers.view')) {
            return false;
        }

        return $this->ancestorChain()
            ->push($this)
            ->every(fn (Dossier $dossier): bool => static::passesConfidentiality($dossier, $user));
    }

    public function ancestorChain(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;
        $safetyCounter = 0;

        while ($current && $safetyCounter < 20) {
            $ancestors->prepend($current);
            $current = $current->parent;
            $safetyCounter++;
        }

        return $ancestors->values();
    }

    public static function generateAnnualTree(int $year, int $ownerId): array
    {
        return DB::transaction(function () use ($year, $ownerId): array {
            $created = 0;
            $existing = 0;

            $root = static::query()->firstOrCreate(
                ['code' => sprintf('GED-%d', $year)],
                [
                    'libelle' => sprintf('Activites %d', $year),
                    'description' => sprintf('Classement GED annuel pour les activites de %d.', $year),
                    'parent_id' => null,
                    'annee_activite' => $year,
                    'type_dossier' => self::TYPE_YEAR,
                    'ordre_affichage' => 0,
                    'confidentialite' => 'Standard',
                    'owner_id' => $ownerId,
                    'statut' => 'Actif',
                ],
            );

            $root->wasRecentlyCreated ? $created++ : $existing++;

            foreach (static::standardYearBlueprint() as $categoryIndex => $category) {
                $categoryRecord = static::query()->firstOrCreate(
                    ['code' => sprintf('GED-%d-%02d', $year, $categoryIndex + 1)],
                    [
                        'libelle' => $category['label'],
                        'description' => $category['description'],
                        'parent_id' => $root->id,
                        'annee_activite' => $year,
                        'type_dossier' => self::TYPE_CATEGORY,
                        'ordre_affichage' => ($categoryIndex + 1) * 10,
                        'confidentialite' => 'Standard',
                        'owner_id' => $ownerId,
                        'statut' => 'Actif',
                    ],
                );

                $categoryRecord->wasRecentlyCreated ? $created++ : $existing++;

                foreach ($category['children'] as $childIndex => $childLabel) {
                    $childRecord = static::query()->firstOrCreate(
                        ['code' => sprintf('GED-%d-%02d-%02d', $year, $categoryIndex + 1, $childIndex + 1)],
                        [
                            'libelle' => $childLabel,
                            'description' => sprintf('Sous-dossier %s pour l\'annee %d.', $childLabel, $year),
                            'parent_id' => $categoryRecord->id,
                            'annee_activite' => $year,
                            'type_dossier' => self::TYPE_SUBCATEGORY,
                            'ordre_affichage' => ($childIndex + 1) * 10,
                            'confidentialite' => 'Standard',
                            'owner_id' => $ownerId,
                            'statut' => 'Actif',
                        ],
                    );

                    $childRecord->wasRecentlyCreated ? $created++ : $existing++;
                }
            }

            return [
                'created' => $created,
                'existing' => $existing,
                'root' => $root->fresh(),
            ];
        });
    }

    public function scopeForActivityYear(Builder $query, int $year): Builder
    {
        return $query->where('annee_activite', $year);
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithHierarchyContext(Builder $query): Builder
    {
        return $query->with([
            'parent.parent.parent.parent',
            'owner',
        ]);
    }

    public function getBreadcrumbPathAttribute(): string
    {
        return $this->ancestorChain()
            ->pluck('libelle')
            ->push($this->libelle)
            ->implode(' > ');
    }

    public function getHierarchyLevelAttribute(): int
    {
        return $this->ancestorChain()->count();
    }

    public function getIndentedLabelAttribute(): string
    {
        return str_repeat('> ', $this->hierarchy_level) . $this->libelle;
    }

    public function selectionLabel(): string
    {
        $yearPrefix = $this->annee_activite ? ($this->annee_activite . ' • ') : '';

        return $yearPrefix . $this->breadcrumb_path;
    }

    public function aggregatedDocumentsCount(): int
    {
        $this->loadMissing('documents', 'children');

        return $this->documents->count()
            + $this->children->sum(fn (Dossier $child): int => $child->aggregatedDocumentsCount());
    }

    public function aggregatedChildrenCount(): int
    {
        $this->loadMissing('children');

        return $this->children->sum(fn (Dossier $child): int => 1 + $child->aggregatedChildrenCount());
    }

    public function getTypeLabelAttribute(): string
    {
        return static::typeOptions()[$this->type_dossier] ?? static::typeOptions()[self::TYPE_STANDARD];
    }

    protected static function standardYearBlueprint(): array
    {
        return [
            [
                'label' => 'Courriers et decisions',
                'description' => 'Courriers entrants, sortants et decisions rattaches a l\'annee d\'activite.',
                'children' => ['Entrants', 'Sortants', 'Decisions'],
            ],
            [
                'label' => 'Projets et activites',
                'description' => 'Planification, execution et suivi des activites annuelles.',
                'children' => ['Planification', 'Suivi', 'Livrables'],
            ],
            [
                'label' => 'Finances et marches',
                'description' => 'Budgets, pieces comptables et passation des marches.',
                'children' => ['Budgets', 'Depenses', 'Marches'],
            ],
            [
                'label' => 'Ressources humaines',
                'description' => 'Dossiers du personnel, formations et conges lies a l\'annee.',
                'children' => ['Agents', 'Formations', 'Conges'],
            ],
            [
                'label' => 'Referentiels et rapports',
                'description' => 'Procedures, rapports et archives de travail.',
                'children' => ['Procedures', 'Rapports', 'Archives de travail'],
            ],
        ];
    }

    public function parent()
    {
        return $this->belongsTo(Dossier::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Dossier::class, 'parent_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    protected static function applyVisibleConstraint(Builder $query, User $user): void
    {
        if (! $user->can('ged.dossiers.view')) {
            $query->whereRaw('1 = 0');

            return;
        }

        $canViewConfidential = $user->can('ged.dossiers.view.confidential') || $user->can('ged.dossiers.view.personal');
        $canViewPersonal = $user->can('ged.dossiers.view.personal');

        $query->where(function (Builder $ruleQuery) use ($user, $canViewConfidential, $canViewPersonal): void {
            $ruleQuery->whereNull('confidentialite')
                ->orWhere('confidentialite', 'Standard');

            if ($canViewConfidential) {
                $ruleQuery->orWhere('confidentialite', 'Confidentiel');
            } else {
                $ruleQuery->orWhere(function (Builder $confidentialQuery) use ($user): void {
                    $confidentialQuery
                        ->where('confidentialite', 'Confidentiel')
                        ->where('owner_id', $user->id);
                });
            }

            if ($canViewPersonal) {
                $ruleQuery->orWhere('confidentialite', 'Personnel');
            } else {
                $ruleQuery->orWhere(function (Builder $personalQuery) use ($user): void {
                    $personalQuery
                        ->where('confidentialite', 'Personnel')
                        ->where('owner_id', $user->id);
                });
            }
        });
    }

    protected static function applyForbiddenConstraint(Builder $query, User $user): void
    {
        if (! $user->can('ged.dossiers.view')) {
            $query->whereRaw('1 = 1');

            return;
        }

        $canViewConfidential = $user->can('ged.dossiers.view.confidential') || $user->can('ged.dossiers.view.personal');
        $canViewPersonal = $user->can('ged.dossiers.view.personal');

        $query->where(function (Builder $ruleQuery) use ($user, $canViewConfidential, $canViewPersonal): void {
            if (! $canViewConfidential) {
                $ruleQuery->where(function (Builder $confidentialQuery) use ($user): void {
                    $confidentialQuery
                        ->where('confidentialite', 'Confidentiel')
                        ->where(function (Builder $ownerQuery) use ($user): void {
                            $ownerQuery->whereNull('owner_id')->orWhere('owner_id', '!=', $user->id);
                        });
                });
            }

            if (! $canViewPersonal) {
                $method = ! $canViewConfidential ? 'orWhere' : 'where';

                $ruleQuery->{$method}(function (Builder $personalQuery) use ($user): void {
                    $personalQuery
                        ->where('confidentialite', 'Personnel')
                        ->where(function (Builder $ownerQuery) use ($user): void {
                            $ownerQuery->whereNull('owner_id')->orWhere('owner_id', '!=', $user->id);
                        });
                });
            }
        });
    }

    protected static function passesConfidentiality(Dossier $dossier, User $user): bool
    {
        return match ((string) ($dossier->confidentialite ?? 'Standard')) {
            'Personnel' => $dossier->owner_id === $user->id || $user->can('ged.dossiers.view.personal'),
            'Confidentiel' => $dossier->owner_id === $user->id
                || $user->can('ged.dossiers.view.confidential')
                || $user->can('ged.dossiers.view.personal'),
            default => true,
        };
    }
}
