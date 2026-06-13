<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    /**
     * Groups of permissions keyed by virtual field name.
     * Used for both form building and page-hook aggregation.
     */
    public static array $permissionGroups = [
                'perms_agenda' => [
                    'label'   => 'Agenda (Rendez-vous, réunions, visites, diligences)',
                    'options' => [
                        'agenda.viewAny'           => 'Voir la liste',
                        'agenda.view'              => 'Consulter un élément',
                        'agenda.create'            => 'Créer',
                        'agenda.update'            => 'Modifier',
                        'agenda.delete'            => 'Supprimer',
                        'agenda.export'            => 'Exporter (CSV / PDF)',
                        'agenda.meetings.manage'   => 'Gérer les réunions',
                        'agenda.appointments.manage' => 'Gérer les rendez-vous',
                        'agenda.visits.manage'     => 'Gérer les visites',
                        'agenda.diligences.manage' => 'Gérer les diligences',
                        'agenda.planning.view'     => 'Voir le planning & suivi des réunions',
                        'agenda.planning.manage'   => 'Gérer le planning & suivi (statuts, indicateurs, commentaires, documents)',
                    ],
                ],
        'perms_courriers' => [
            'label'   => 'Courriers',
            'options' => [
                'courriers.viewAny' => 'Voir la liste',
                'courriers.view'    => 'Consulter un courrier',
                'courriers.create'  => 'Créer',
                'courriers.update'  => 'Modifier',
                'courriers.delete'  => 'Supprimer',
                'courriers.sign'    => 'Signer',
                'courriers.export'  => 'Exporter (CSV / PDF)',
                'courriers.coedit'  => 'Co-édition collaborative',
            ],
        ],
        'perms_approval' => [
            'label'   => 'Workflow d\'approbation',
            'options' => [
                'courriers.approval.submit'  => 'Soumettre à approbation',
                'courriers.approval.approve' => 'Approuver',
                'courriers.approval.reject'  => 'Rejeter',
            ],
        ],
        'perms_reports' => [
            'label'   => 'Rapports',
            'options' => [
                'reports.viewAny' => 'Voir la liste',
                'reports.view' => 'Consulter un rapport',
                'reports.create' => 'Creer',
                'reports.update' => 'Modifier',
                'reports.delete' => 'Supprimer',
                'reports.export' => 'Exporter (PDF/Word)',
            ],
        ],
        'perms_reports_approval' => [
            'label'   => 'Workflow rapports',
            'options' => [
                'reports.approval.submit' => 'Soumettre a approbation',
                'reports.approval.approve' => 'Approuver',
                'reports.approval.reject' => 'Rejeter',
            ],
        ],
        'perms_reports_templates' => [
            'label'   => 'Modeles rapports',
            'options' => [
                'reports.templates.manage' => 'Gerer categories et modeles institutionnels',
            ],
        ],
        'perms_reports_recommendations' => [
            'label'   => 'Suivi recommandations',
            'options' => [
                'reports.recommendations.viewAny' => 'Consulter la table consolidee',
                'reports.recommendations.create' => 'Creer / extraire des recommandations',
                'reports.recommendations.update' => 'Mettre a jour la mise en oeuvre',
                'reports.recommendations.delete' => 'Supprimer une recommandation',
            ],
        ],
        'perms_audit' => [
            'label'   => 'Audit & traçabilité',
            'options' => [
                'audit.view'   => 'Consulter les logs',
                'audit.export' => 'Exporter les logs',
            ],
        ],
        'perms_ged_dossiers' => [
            'label'   => 'GED — Dossiers',
            'options' => [
                'ged.dossiers.view'              => 'Consulter',
                'ged.dossiers.view.confidential' => 'Voir les dossiers confidentiels',
                'ged.dossiers.view.personal'     => 'Voir les dossiers personnels',
                'ged.dossiers.create'            => 'Créer',
                'ged.dossiers.update'            => 'Modifier',
                'ged.dossiers.archive'           => 'Archiver',
            ],
        ],
        'perms_ged_documents' => [
            'label'   => 'GED — Documents',
            'options' => [
                'ged.documents.view'     => 'Consulter',
                'ged.documents.create'   => 'Ajouter',
                'ged.documents.update'   => 'Modifier',
                'ged.documents.version'  => 'Versionner',
                'ged.documents.download' => 'Télécharger',
                'ged.documents.share'    => 'Partager',
                'ged.documents.delete'   => 'Supprimer',
            ],
        ],
        'perms_gtt' => [
            'label'   => 'GTT',
            'options' => [
                'gtt.documents.view'       => 'Consulter les documents GTT',
                'gtt.documents.manage'     => 'Gérer les documents GTT',
                'gtt.members.view'         => 'Consulter les membres GTT',
                'gtt.members.manage'       => 'Gérer les membres GTT',
                'bureau_members.list'      => 'Lister les membres du bureau',
                'bureau_members.create'    => 'Créer des membres du bureau',
                'bureau_members.edit'      => 'Modifier des membres du bureau',
                'bureau_members.delete'    => 'Supprimer des membres du bureau',
                'bureau_members.activate'  => 'Activer des membres du bureau',
                'bureau_members.deactivate'=> 'Désactiver des membres du bureau',
            ],
        ],
        'perms_collaboration' => [
            'label'   => 'Collaboration',
            'options' => [
                'collaboration.online_users.view' => 'Accéder aux utilisateurs en ligne & chat',
                'collaboration.comments.create' => 'Poster des commentaires',
                'collaboration.comments.delete' => 'Supprimer des commentaires',
                'collaboration.tasks.create'    => 'Créer des tâches',
                'collaboration.tasks.assign'    => 'Assigner des tâches',
                'collaboration.tasks.update'    => 'Mettre à jour des tâches',
                'collaboration.tasks.close'     => 'Clôturer des tâches',
            ],
        ],
        'perms_admin' => [
            'label'   => 'Administration',
            'options' => [
                'admin.users.view'      => 'Consulter les utilisateurs',
                'admin.users.create'    => 'Créer des utilisateurs',
                'admin.users.update'    => 'Modifier des utilisateurs',
                'admin.roles.manage'    => 'Gérer les rôles & permissions',
                'admin.settings.manage' => 'Gérer les paramètres système',
            ],
        ],
    ];

    public static function configure(Schema $schema): Schema
    {
        $sections = [
            Section::make('Identité du rôle')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom du rôle')
                        ->required()
                        ->maxLength(100)
                        ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                        ->helperText('Exemple : Responsable RH, Validateur N3…'),

                    Forms\Components\Hidden::make('guard_name')
                        ->default('web'),

                    // ...existing code...
                ])
                ->columns(1),
        ];

        foreach (self::$permissionGroups as $field => $group) {
            $sections[] = self::makePermissionSection($field, $group);
        }

        return $schema->components($sections);
    }

    /**
     * @param  array{label: string, options: array<string, string>}  $group
     */
    protected static function makePermissionSection(string $field, array $group): Section
    {
        $allPermissions = array_keys($group['options']);

        return Section::make($group['label'])
            ->columns(1)
            ->collapsible()
            ->headerActions([
                Action::make("{$field}_select_all")
                    ->label('Sélectionner tout')
                    ->color('gray')
                    ->action(function (callable $schemaSet) use ($field, $allPermissions): void {
                        $schemaSet($field, $allPermissions);
                    }),
                Action::make("{$field}_deselect_all")
                    ->label('Désélectionner tout')
                    ->color('gray')
                    ->action(function (callable $schemaSet) use ($field): void {
                        $schemaSet($field, []);
                    }),
            ])
            ->schema([
                Forms\Components\CheckboxList::make($field)
                    ->hiddenLabel()
                    ->options($group['options'])
                    ->columns(2),
            ]);
    }

    /**
     * Populate virtual permission fields from the role's current permissions.
     */
    public static function fillPermissions(array $data, \Spatie\Permission\Models\Role $role): array
    {
        $current = $role->permissions->pluck('name')->toArray();

        foreach (self::$permissionGroups as $field => $group) {
            $data[$field] = array_values(
                array_intersect($current, array_keys($group['options']))
            );
        }

        return $data;
    }

    /**
     * Aggregate all virtual permission fields back into a flat array.
     */
    public static function collectPermissions(array $state): array
    {
        $permissions = [];

        foreach (array_keys(self::$permissionGroups) as $field) {
            foreach ($state[$field] ?? [] as $perm) {
                $permissions[] = $perm;
            }
        }

        // Validation stricte : ne garder que les permissions existantes en base
        $existing = \App\Filament\Resources\Roles\Schemas\PermissionHelper::getExistingPermissions();
        $permissions = array_intersect(array_unique($permissions), $existing);

        return array_values($permissions);
    }
}
