<?php

namespace Tests\Feature;

use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CourrierSignatureWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sortant_traite_can_be_signed_without_approval_requirement(): void
    {
        $courrier = $this->makeCourrier([
            'type' => 'Sortant',
            'statut' => 'Traité',
            'requires_approval' => false,
            'approval_status' => 'not_required',
            'signed_at' => null,
        ]);

        $this->assertTrue($courrier->canBeSigned());
    }

    public function test_sortant_requires_approved_workflow_before_signature(): void
    {
        $pending = $this->makeCourrier([
            'type' => 'Sortant',
            'statut' => 'Traité',
            'requires_approval' => true,
            'approval_status' => 'pending',
            'signed_at' => null,
        ]);

        $approved = $this->makeCourrier([
            'type' => 'Sortant',
            'reference' => 'SIG-0002',
            'statut' => 'Traité',
            'requires_approval' => true,
            'approval_status' => 'approved',
            'signed_at' => null,
        ]);

        $this->assertFalse($pending->canBeSigned());
        $this->assertTrue($approved->canBeSigned());
    }

    public function test_sign_permission_is_enforced_by_policy_gate(): void
    {
        $courrier = $this->makeCourrier([
            'type' => 'Sortant',
            'statut' => 'Traité',
            'requires_approval' => false,
            'approval_status' => 'not_required',
        ]);

        $userWithoutPermission = User::factory()->create();
        $userWithPermission = User::factory()->create();

        Permission::findOrCreate('courriers.sign', 'web');
        $userWithPermission->givePermissionTo('courriers.sign');

        $this->assertFalse(Gate::forUser($userWithoutPermission)->allows('sign', $courrier));
        $this->assertTrue(Gate::forUser($userWithPermission)->allows('sign', $courrier));
    }

    private function makeCourrier(array $overrides = []): Courrier
    {
        $user = User::factory()->create();
        $correspondant = Correspondant::query()->create([
            'nom_structure' => 'Structure Signature',
        ]);

        return Courrier::query()->create(array_merge([
            'type' => 'Sortant',
            'reference' => 'SIG-0001',
            'date_reception_envoi' => now()->toDateString(),
            'objet' => 'Courrier à signer',
            'priorite' => 'Normale',
            'statut' => 'Traité',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id' => $correspondant->id,
            'user_id' => $user->id,
            'requires_approval' => false,
            'approval_status' => 'not_required',
            'current_approval_level' => null,
            'signed_by' => null,
            'signed_at' => null,
            'signature_comment' => null,
        ], $overrides));
    }
}
