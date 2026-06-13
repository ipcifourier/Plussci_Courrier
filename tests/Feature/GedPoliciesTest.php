<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GedPoliciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_ged_view_permission_can_view_resources(): void
    {
        Permission::findOrCreate('ged.dossiers.view', 'web');
        Permission::findOrCreate('ged.documents.view', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('ged.dossiers.view', 'ged.documents.view');

        $dossier = Dossier::query()->create([
            'code' => 'DOS-001',
            'libelle' => 'Dossier test',
            'owner_id' => $user->id,
            'statut' => 'Actif',
        ]);

        $document = Document::query()->create([
            'reference_doc' => 'DOC-001',
            'titre' => 'Document test',
            'type_document' => 'Rapport',
            'auteur_id' => $user->id,
            'etat_cycle_vie' => 'Brouillon',
            'confidentiality_level' => 'Standard',
            'dossier_id' => $dossier->id,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Dossier::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $dossier));
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Document::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $document));
    }

    public function test_user_without_ged_permissions_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Dossier::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Document::class));
    }
}
