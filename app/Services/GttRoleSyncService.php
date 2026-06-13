<?php

namespace App\Services;

use App\Models\Gtt;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GttRoleSyncService
{
    public const RESPONSABLE_ROLE = 'GTT Responsable';
    public const MEMBER_ROLE = 'GTT Membre';

    /** @return list<string> */
    public static function responsablePermissions(): array
    {
        return [
            'gtt.documents.view',
            'gtt.documents.manage',
            'gtt.members.view',
            'gtt.members.manage',
            'ged.documents.view',
            'ged.documents.create',
            'ged.documents.update',
            'ged.documents.delete',
            'ged.dossiers.view',
            'ged.dossiers.create',
            'ged.dossiers.update',
            'courriers.viewAny',
            'courriers.view',
            'courriers.create',
            'courriers.update',
            'courriers.delete',
            'bureau_members.create',
            'bureau_members.edit',
            'bureau_members.delete',
            'bureau_members.activate',
            'bureau_members.deactivate',
            'bureau_members.list',
        ];
    }

    /** @return list<string> */
    public static function memberPermissions(): array
    {
        return [
            'gtt.documents.view',
            'gtt.members.view',
            'ged.documents.view',
            'bureau_members.list',
        ];
    }

    /** @return list<string> */
    public function normalizeRolePermissions(string $roleName, array $permissions): array
    {
        $normalized = array_values(array_unique(array_filter($permissions)));

        return match ($roleName) {
            self::RESPONSABLE_ROLE => array_values(array_unique(array_merge($normalized, self::responsablePermissions()))),
            self::MEMBER_ROLE => array_values(array_unique(array_merge($normalized, self::memberPermissions()))),
            default => $normalized,
        };
    }

    public function ensureRoleSetup(string $roleName): void
    {
        $permissions = match ($roleName) {
            self::RESPONSABLE_ROLE => self::responsablePermissions(),
            self::MEMBER_ROLE => self::memberPermissions(),
            default => [],
        };

        if ($permissions === []) {
            return;
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function syncGttResponsable(Gtt $gtt, mixed $previousResponsable = null): void
    {
        $newResponsableId = $gtt->responsable ? (int) $gtt->responsable : null;
        $oldResponsableId = $previousResponsable !== null && $previousResponsable !== '' ? (int) $previousResponsable : null;

        if ($newResponsableId) {
            $newResponsable = User::query()->find($newResponsableId);

            if ($newResponsable) {
                if ((int) ($newResponsable->gtt_id ?? 0) !== (int) $gtt->id) {
                    $newResponsable->forceFill(['gtt_id' => $gtt->id])->save();
                }

                $this->syncUserResponsibility($newResponsable);
            }
        }

        if ($oldResponsableId && $oldResponsableId !== $newResponsableId) {
            $oldResponsable = User::query()->find($oldResponsableId);

            if ($oldResponsable) {
                $this->syncUserResponsibility($oldResponsable);
            }
        }
    }

    public function syncUserResponsibility(User $user): void
    {
        $user = $user->fresh(['roles']) ?? $user;

        $responsibleGtts = Gtt::query()
            ->where('responsable', $user->getKey())
            ->pluck('id');

        $roleNames = $user->getRoleNames()
            ->filter(fn (string $roleName): bool => $roleName !== self::RESPONSABLE_ROLE)
            ->values()
            ->all();

        if ($responsibleGtts->isNotEmpty()) {
            $this->ensureRoleSetup(self::RESPONSABLE_ROLE);

            $roleNames[] = self::RESPONSABLE_ROLE;
            $user->syncRoles(array_values(array_unique($roleNames)));

            if ($user->hasRole('Lecteur Courrier')) {
                $user->removeRole('Lecteur Courrier');
            }

            if (! $user->gtt_id || ! $responsibleGtts->contains((int) $user->gtt_id)) {
                $user->forceFill(['gtt_id' => (int) $responsibleGtts->first()])->save();
            }

            return;
        }

        $user->syncRoles($roleNames);
    }
}