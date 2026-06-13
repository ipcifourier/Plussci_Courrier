<?php

namespace Tests\Feature;

use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\CourrierApproval;
use App\Models\User;
use App\Notifications\CourrierApprovalDecisionNotification;
use App\Notifications\CourrierApprovalRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CourrierApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_only_current_level_approvers(): void
    {
        Notification::fake();

        $initiateur = User::factory()->create();
        $approverLevel1 = User::factory()->create();
        $approverLevel2 = User::factory()->create();
        $correspondant = Correspondant::query()->create(['nom_structure' => 'Test Org']);

        $courrier = Courrier::query()->create([
            'type' => 'Sortant',
            'reference' => 'APP-0001',
            'date_reception_envoi' => now()->toDateString(),
            'objet' => 'Objet test',
            'priorite' => 'Normale',
            'statut' => 'En cours',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id' => $correspondant->id,
            'user_id' => $initiateur->id,
            'requires_approval' => true,
            'approval_status' => 'pending',
            'current_approval_level' => 1,
        ]);

        CourrierApproval::query()->create([
            'courrier_id' => $courrier->id,
            'level' => 1,
            'approver_id' => $approverLevel1->id,
            'status' => 'pending',
        ]);

        CourrierApproval::query()->create([
            'courrier_id' => $courrier->id,
            'level' => 2,
            'approver_id' => $approverLevel2->id,
            'status' => 'pending',
        ]);

        $courrier->notifyCurrentApprovers();

        Notification::assertSentTo($approverLevel1, CourrierApprovalRequestedNotification::class);
        Notification::assertNotSentTo($approverLevel2, CourrierApprovalRequestedNotification::class);
    }

    public function test_it_notifies_initiator_on_final_decision(): void
    {
        Notification::fake();

        $initiateur = User::factory()->create();
        $correspondant = Correspondant::query()->create(['nom_structure' => 'Test Org']);

        $courrier = Courrier::query()->create([
            'type' => 'Sortant',
            'reference' => 'APP-0002',
            'date_reception_envoi' => now()->toDateString(),
            'objet' => 'Objet test 2',
            'priorite' => 'Normale',
            'statut' => 'En cours',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id' => $correspondant->id,
            'user_id' => $initiateur->id,
            'requires_approval' => true,
            'approval_status' => 'pending',
            'current_approval_level' => 1,
        ]);

        $courrier->notifyInitiatorDecision('rejected', 'Motif de test');

        Notification::assertSentTo($initiateur, CourrierApprovalDecisionNotification::class);
    }
}
