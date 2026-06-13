<?php

namespace App\Filament\Pages;

use App\Models\Dossier;
use App\Models\Gtt;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\GedSettingsService;
use App\Services\SearchService;
use BackedEnum;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;
class SearchPage extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Recherche avancée';

    protected static ?string $title = 'Recherche avancée';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.search';

    // ── State ─────────────────────────────────────────────────────────────────

    /** 'documents' | 'courriers' */
    public string $activeTab = 'documents';

    /** Filter bound to the Filament form */
    public ?array $filters = [];

    // ── Boot ─────────────────────────────────────────────────────────────────

    public static function getNavigationGroup(): ?string
    {
        return 'GED';
    }

    public static function canAccess(): bool
{
    $user = Auth::user();

    if (! $user instanceof User) {
        return false;
    }

    return $user->hasRole('Super Admin')
        || $user->hasPermissionTo('ged.documents.view')
        || $user->hasPermissionTo('courriers.viewAny');
}

    public function mount(): void
    {
        $this->form->fill([]);
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        $documentTypes = app(GedSettingsService::class)->documentTypes();

        return $schema
            ->statePath('filters')
            ->components([
                Section::make()
                    ->columns(['sm' => 2, 'lg' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('q')
                            ->label('Recherche')
                            ->placeholder('Titre, référence, texte OCR…')
                            ->prefixIcon('heroicon-o-magnifying-glass')
                            ->live(debounce: 400)
                            ->afterStateUpdated(fn () => $this->resetPage())
                            // Pressing Enter bypasses the debounce by immediately
                            // syncing the typed value to Livewire before re-rendering.
                            ->extraInputAttributes([
                                'x-on:keydown.enter.prevent' => '$wire.set("filters.q", $event.target.value)',
                            ])
                            ->columnSpan(['sm' => 2, 'lg' => 2]),

                        Forms\Components\Select::make('dossier_id')
                            ->label('Dossier')
                            ->options(fn () => Dossier::where('statut', 'Actif')->orderBy('libelle')->pluck('libelle', 'id'))
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'documents'),

                        Forms\Components\Select::make('type_document')
                            ->label('Type de document')
                            ->options($documentTypes)
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'documents'),

                        Forms\Components\Select::make('gtt_id')
                            ->label('GTT')
                            ->options(fn () => Gtt::query()->visibleTo(Auth::user())->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'documents'),

                        Forms\Components\Select::make('etat_cycle_vie')
                            ->label('État')
                            ->options([
                                'Brouillon' => 'Brouillon',
                                'Valide'    => 'Validé',
                                'Archive'   => 'Archivé',
                            ])
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'documents'),

                        Forms\Components\Select::make('confidentiality_level')
                            ->label('Confidentialité')
                            ->options([
                                'Standard'      => 'Standard',
                                'Confidentiel'  => 'Confidentiel',
                                'Personnel'     => 'Personnel',
                            ])
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'documents'),

                        Forms\Components\Select::make('source')
                            ->label('Source')
                            ->options([
                                'upload'      => 'Chargement manuel',
                                'email'       => 'Import e-mail',
                                'scan_folder' => 'Scanner',
                            ])
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'documents'),

                        // ── Courriers filters ─────────────────────────────────
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(['Entrant' => 'Entrant', 'Sortant' => 'Sortant'])
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'courriers'),

                        Forms\Components\Select::make('statut')
                            ->label('Statut')
                            ->options([
                                'Nouveau'   => 'Nouveau',
                                'En cours'  => 'En cours',
                                'Traité'    => 'Traité',
                                'Archivé'   => 'Archivé',
                            ])
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'courriers'),

                        Forms\Components\Select::make('priorite')
                            ->label('Priorité')
                            ->options(['Normale' => 'Normale', 'Urgente' => 'Urgente', 'Très urgente' => 'Très urgente'])
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'courriers'),

                        // ── Shared date range ─────────────────────────────────
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Du')
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage()),

                        Forms\Components\DatePicker::make('date_to')
                            ->label('Au')
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage()),

                        Forms\Components\Select::make('annee')
                            ->label('Annee')
                            ->options(function (): array {
                                $currentYear = (int) now()->year;
                                $years = range($currentYear, $currentYear - 20);

                                return array_combine($years, $years);
                            })
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage()),

                        // ── Author (documents only) ───────────────────────────
                        Forms\Components\Select::make('auteur_id')
                            ->label('Auteur')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPage())
                            ->visible(fn () => $this->activeTab === 'documents'),
                    ]),
            ]);
    }

    // ── Actions ──────────────────────────────────────────────────────────────

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        // Reset tab-specific filters
        $this->filters = array_intersect_key($this->filters ?? [], array_flip(['q', 'date_from', 'date_to', 'annee']));
        $this->form->fill($this->filters);
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->form->fill([]);
        $this->resetPage();
    }

    // ── Results ──────────────────────────────────────────────────────────────

    public function getResults(): LengthAwarePaginator
    {
        $filters = $this->filters ?? [];

        // Only run query when at least a search term or a filter is set
        $hasFilter = collect($filters)->filter(fn ($v) => ! empty($v))->isNotEmpty();

        if (! $hasFilter) {
            return $this->emptyPaginator();
        }

        $service = app(SearchService::class);

        return $this->activeTab === 'documents'
            ? $service->searchDocuments($filters, 12)
            : $service->searchCourriers($filters, 12);
    }

    public function hasActiveFilters(): bool
    {
        return collect($this->filters ?? [])->filter(fn ($v) => ! empty($v))->isNotEmpty();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function emptyPaginator(): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            items: [],
            total: 0,
            perPage: 12,
            currentPage: 1,
        );
    }
}
