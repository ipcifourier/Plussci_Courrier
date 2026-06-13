<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    // Activez ou désactivez la permission collaboration.tasks.view pour tous les rôles ici :
    protected bool $enableCollaborationTasksView = true;

    public function run(): void
    {
        // Permissions spécifiques pour GTT Responsable
        $gttResponsablePerms = [
            'gtt.documents.view',
            'gtt.documents.manage',
            'gtt.members.view',
            'gtt.members.manage',
            'bureau_members.create',
            'bureau_members.edit',
            'bureau_members.delete',
            'bureau_members.activate',
            'bureau_members.deactivate',
            'bureau_members.list',
            'ged.documents.view',
            'ged.documents.create',
            'ged.documents.update',
            'ged.documents.delete',
            'ged.dossiers.view',
            'ged.dossiers.view.confidential',
            'ged.dossiers.create',
            'ged.dossiers.update',
            'courriers.view',
            'courriers.create',
            'courriers.update',
            'courriers.delete',
        ];
        foreach ($gttResponsablePerms as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Courriers
            'courriers.viewAny',
            'courriers.view',
            'courriers.create',
            'courriers.update',
            'courriers.delete',
            'courriers.sign',
            'courriers.export',
            'courriers.coedit',
            'courriers.approval.submit',
            'courriers.approval.approve',
            'courriers.approval.reject',
            // Rapports
            'reports.viewAny',
            'reports.view',
            'reports.create',
            'reports.update',
            'reports.delete',
            'reports.export',
            'reports.approval.submit',
            'reports.approval.approve',
            'reports.approval.reject',
            'reports.templates.manage',
            'reports.recommendations.viewAny',
            'reports.recommendations.create',
            'reports.recommendations.update',
            'reports.recommendations.delete',
            // Audit
            'audit.view',
            'audit.export',
            // GED
            'ged.dossiers.view',
            'ged.dossiers.view.confidential',
            'ged.dossiers.view.personal',
            'ged.dossiers.create',
            'ged.dossiers.update',
            'ged.dossiers.archive',
            'ged.documents.view',
            'ged.documents.create',
            'ged.documents.update',
            'ged.documents.version',
            'ged.documents.download',
            'ged.documents.share',
            'ged.documents.delete',
            // Collaboration
            'collaboration.online_users.view',
            'collaboration.comments.create',
            'collaboration.comments.delete',
            'collaboration.tasks.view',
            'collaboration.tasks.create',
            'collaboration.tasks.assign',
            'collaboration.tasks.update',
            'collaboration.tasks.close',
            // Admin
            'admin.users.view',
            'admin.users.create',
            'admin.users.update',
            'admin.roles.manage',
            'admin.settings.manage',
            // Agenda
            'agenda.viewAny',
            'agenda.view',
            'agenda.create',
            'agenda.update',
            'agenda.delete',
            'agenda.export',
            'agenda.meetings.manage',
            'agenda.appointments.manage',
            'agenda.visits.manage',
            'agenda.diligences.manage',
            // Planning & Suivi des Réunions
            'agenda.planning.view',
            'agenda.planning.manage',
        ]; // <-- Fermeture correcte du tableau

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin    = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminMetier    = Role::firstOrCreate(['name' => 'Admin Métier', 'guard_name' => 'web']);
        $gestionnaire   = Role::firstOrCreate(['name' => 'Gestionnaire Courrier', 'guard_name' => 'web']);
        $approbateurN1  = Role::firstOrCreate(['name' => 'Approbateur N1', 'guard_name' => 'web']);
        $approbateurN2  = Role::firstOrCreate(['name' => 'Approbateur N2', 'guard_name' => 'web']);
        $lecteur        = Role::firstOrCreate(['name' => 'Lecteur Courrier', 'guard_name' => 'web']);
        $archiviste     = Role::firstOrCreate(['name' => 'Archiviste GED', 'guard_name' => 'web']);
        $collaborateur  = Role::firstOrCreate(['name' => 'Collaborateur', 'guard_name' => 'web']);
        $auditeur       = Role::firstOrCreate(['name' => 'Auditeur', 'guard_name' => 'web']);
        $adminRole      = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $assistanteRole = Role::firstOrCreate(['name' => 'Assistante', 'guard_name' => 'web']);
        $managerRole    = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);

        $allRoles = [$superAdmin, $adminMetier, $gestionnaire, $approbateurN1, $approbateurN2, $lecteur, $archiviste, $collaborateur, $auditeur, $adminRole, $assistanteRole, $managerRole];

        $superAdmin->syncPermissions($permissions);

        $adminMetier->syncPermissions([
            'admin.users.view',
            'admin.users.create',
            'admin.users.update',
            'admin.roles.manage',
            'admin.settings.manage',
            'courriers.viewAny',
            'courriers.view',
            'courriers.create',
            'courriers.update',
            'courriers.delete',
            'courriers.sign',
            'courriers.export',
            'reports.viewAny',
            'reports.view',
            'reports.create',
            'reports.update',
            'reports.delete',
            'reports.export',
            'reports.approval.submit',
            'reports.approval.approve',
            'reports.approval.reject',
            'reports.templates.manage',
            'reports.recommendations.viewAny',
            'reports.recommendations.create',
            'reports.recommendations.update',
            'reports.recommendations.delete',
            'audit.view',
            'ged.dossiers.view',
            'ged.dossiers.view.confidential',
            'ged.dossiers.view.personal',
            'ged.dossiers.create',
            'ged.dossiers.update',
            'ged.documents.view',
            'ged.documents.create',
            'ged.documents.update',
            'ged.documents.version',
            'ged.documents.download',
            'reports.viewAny',
            'reports.view',
            'reports.create',
            'reports.update',
            'reports.export',
            'reports.approval.submit',
            'reports.templates.manage',
            'reports.recommendations.viewAny',
            'reports.recommendations.create',
            'reports.recommendations.update',
            'collaboration.online_users.view',
            // Planning & Suivi (lecture + gestion)
            'agenda.planning.view',
            'agenda.planning.manage',
        ]);
        $gestionnaire->syncPermissions([
            'courriers.viewAny',
            'courriers.view',
            'courriers.create',
            'courriers.update',
            'courriers.sign',
            'courriers.export',
            'courriers.approval.submit',
            'reports.viewAny',
            'reports.view',
            'reports.create',
            'reports.update',
            'reports.export',
            'reports.approval.submit',
            'reports.recommendations.viewAny',
            'reports.recommendations.create',
            'reports.recommendations.update',
            'collaboration.online_users.view',
            'collaboration.comments.create',
            'collaboration.tasks.create',
            'collaboration.tasks.assign',
            'collaboration.tasks.update',
            'ged.dossiers.view',
            'ged.dossiers.view.confidential',
            'ged.documents.view',
            // Agenda
            'agenda.viewAny',
            'agenda.view',
            'agenda.create',
            'agenda.update',
            'agenda.export',
            'agenda.meetings.manage',
            'agenda.appointments.manage',
            // Planning & Suivi
            'agenda.planning.view',
            'agenda.planning.manage',
        ]);
                $collaborateur->syncPermissions([
                    'ged.documents.view',
                    'reports.viewAny',
                    'reports.view',
                    'reports.recommendations.viewAny',
                    'collaboration.online_users.view',
                    'collaboration.comments.create',
                    'collaboration.tasks.update',
                    'collaboration.tasks.close',
                    // Agenda
                    'agenda.viewAny',
                    'agenda.view',
                    'agenda.create',
                    'agenda.update',
                    // Planning (lecture seule)
                    'agenda.planning.view',
                ]);
                $auditeur->syncPermissions([
                    'courriers.viewAny',
                    'courriers.view',
                    'reports.viewAny',
                    'reports.view',
                    'reports.export',
                    'reports.recommendations.viewAny',
                    'audit.view',
                    'audit.export',
                    'ged.dossiers.view',
                    'ged.documents.view',
                    // Agenda
                    'agenda.viewAny',
                    'agenda.view',
                    'agenda.export',
                    // Planning (lecture seule)
                    'agenda.planning.view',
                ]);
        $approbateurN1->syncPermissions([
            'courriers.viewAny',
            'courriers.view',
            'courriers.approval.approve',
            'courriers.approval.reject',
            'reports.viewAny',
            'reports.view',
            'reports.approval.approve',
            'reports.approval.reject',
            'reports.recommendations.viewAny',
            'reports.recommendations.update',
        ]);
        $approbateurN2->syncPermissions([
            'courriers.viewAny',
            'courriers.view',
            'courriers.approval.approve',
            'courriers.approval.reject',
            'reports.viewAny',
            'reports.view',
            'reports.approval.approve',
            'reports.approval.reject',
            'reports.recommendations.viewAny',
            'reports.recommendations.update',
        ]);
        $lecteur->syncPermissions([
            'courriers.viewAny',
            'courriers.view',
            'reports.viewAny',
            'reports.view',
            'reports.recommendations.viewAny',
            'ged.dossiers.view',
            'ged.documents.view',
        ]);
        $archiviste->syncPermissions([
            'ged.dossiers.view',
            'ged.dossiers.view.confidential',
            'ged.dossiers.view.personal',
            'ged.dossiers.create',
            'ged.dossiers.update',
            'ged.dossiers.archive',
            'ged.documents.view',
            'ged.documents.create',
            'ged.documents.update',
            'ged.documents.version',
            'ged.documents.download',
            'ged.documents.share',
            'ged.documents.delete',
        ]);
        $auditeur->syncPermissions([
            'courriers.viewAny',
            'courriers.view',
            'reports.viewAny',
            'reports.view',
            'reports.export',
            'reports.recommendations.viewAny',
            'audit.view',
            'audit.export',
            'ged.dossiers.view',
            'ged.documents.view',
            // Planning (lecture seule)
            'agenda.planning.view',
        ]);
        $adminRole->syncPermissions([
            'admin.users.view',
            'admin.users.create',
            'admin.users.update',
            'admin.roles.manage',
            'admin.settings.manage',
            'courriers.viewAny',
            'courriers.view',
            'courriers.create',
            'courriers.update',
            'courriers.delete',
            'courriers.sign',
            'courriers.export',
            'courriers.coedit',
            'courriers.approval.submit',
            'courriers.approval.approve',
            'audit.view',
            'ged.dossiers.view',
            'ged.dossiers.view.confidential',
            'ged.dossiers.view.personal',
            'ged.dossiers.create',
            'ged.documents.view',
            'ged.documents.create',
            'ged.documents.update',
            'ged.documents.version',
            'ged.documents.download',
            'ged.documents.share',
            'ged.documents.delete',
            'collaboration.online_users.view',
            'collaboration.comments.create',
            'collaboration.comments.delete',
            'collaboration.tasks.create',
            'collaboration.tasks.assign',
            'collaboration.tasks.update',
            'collaboration.tasks.close',
            'reports.viewAny',
            'reports.view',
            'reports.create',
            'reports.update',
            'reports.delete',
            'reports.export',
            'reports.approval.submit',
            'reports.approval.approve',
            'reports.approval.reject',
            'reports.templates.manage',
            'reports.recommendations.viewAny',
            'reports.recommendations.create',
            'agenda.viewAny',
            'agenda.view',
            'agenda.create',
            'agenda.update',
            'agenda.delete',
            'agenda.export',
            'agenda.meetings.manage',
            'agenda.appointments.manage',
            'agenda.visits.manage',
            'agenda.diligences.manage',
            'agenda.planning.view',
            'agenda.planning.manage',
        ]);
        $assistanteRole->syncPermissions([
            'courriers.viewAny',
            'courriers.view',
            'courriers.create',
            'courriers.update',
            'courriers.export',
            'courriers.coedit',
            'ged.documents.view',
            'ged.documents.create',
            'ged.documents.update',
            'ged.documents.version',
            'reports.viewAny',
            'reports.view',
            'reports.create',
            'reports.update',
            'reports.export',
            // Planning (lecture seule)
            'agenda.planning.view',
        ]);
        $managerRole->syncPermissions([
            'ged.documents.view',
            'courriers.viewAny',
            'courriers.view',
            'reports.viewAny',
            'reports.view',
            'reports.approval.approve',
            'reports.approval.reject',
            // Planning (lecture seule)
            'agenda.planning.view',
        ]);
        $collaborateur->syncPermissions([
            'ged.documents.view',
            'reports.viewAny',
            'reports.view',
            'reports.recommendations.viewAny',
            'collaboration.online_users.view',
            'collaboration.comments.create',
            'collaboration.tasks.update',
            'collaboration.tasks.close',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@plussci.ci'],
            [
                'name' => 'Administrateur PLUSSCI',
                'password' => bcrypt('password'),
            ],
        );

        $admin->assignRole($superAdmin);

        // Correction assignation rôle et permission pour GTT Responsable
        $gttResponsableRole = Role::firstOrCreate([
            'name' => 'GTT Responsable',
            'guard_name' => 'web',
        ]);
        // Assigner le rôle aux utilisateurs GTT Responsable existants
        $gttResponsables = User::whereHas('roles', function ($q) {
            $q->where('name', 'GTT Responsable');
        })->get();
        foreach ($gttResponsables as $user) {
            $user->assignRole('GTT Responsable');
        }

        if ($this->enableCollaborationTasksView) {
            foreach ($allRoles as $role) {
                if (! $role->hasPermissionTo('collaboration.tasks.view')) {
                    $role->givePermissionTo('collaboration.tasks.view');
                }
            }
        } else {
            foreach ($allRoles as $role) {
                if ($role->hasPermissionTo('collaboration.tasks.view')) {
                    $role->revokePermissionTo('collaboration.tasks.view');
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
