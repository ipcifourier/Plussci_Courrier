<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentWorkflow;
use App\Models\DocumentWorkflowStep;
use App\Models\User;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTemplateStep;
use App\Notifications\WorkflowCompletedNotification;
use App\Notifications\WorkflowStepRequestedNotification;
use App\Services\DocumentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(DocumentWorkflowService::class)]
class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private DocumentWorkflowService $service;
    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DocumentWorkflowService::class);
        $this->author  = User::factory()->create();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // startWorkflow
    // ─────────────────────────────────────────────────────────────────────────

    public function test_start_workflow_creates_workflow_and_steps(): void
    {
        Notification::fake();

        [$template, $approver1, $approver2] = $this->makeTemplate(steps: 2);
        $document  = $this->makeDocument();
        $initiator = User::factory()->create();

        $workflow = $this->service->startWorkflow($document, $template, $initiator);

        $this->assertInstanceOf(DocumentWorkflow::class, $workflow);
        $this->assertEquals('pending', $workflow->status);
        $this->assertEquals(1, $workflow->current_step_order);
        $this->assertEquals($template->name, $workflow->template_name);
        $this->assertEquals($initiator->id, $workflow->initiated_by);
        $this->assertEquals(2, $workflow->steps()->count());
    }

    public function test_start_workflow_notifies_first_approver(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 2);
        $document = $this->makeDocument();

        $this->service->startWorkflow($document, $template, User::factory()->create());

        Notification::assertSentTo($approver1, WorkflowStepRequestedNotification::class);
    }

    public function test_start_workflow_throws_if_active_workflow_exists(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1);
        $document   = $this->makeDocument();
        $initiator  = User::factory()->create();

        $this->service->startWorkflow($document, $template, $initiator);

        $this->expectException(\RuntimeException::class);
        $this->service->startWorkflow($document, $template, $initiator);
    }

    public function test_start_workflow_throws_if_template_has_no_steps(): void
    {
        $template = WorkflowTemplate::create([
            'name'       => 'Vide',
            'is_active'  => true,
            'created_by' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->service->startWorkflow($this->makeDocument(), $template, User::factory()->create());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // approve
    // ─────────────────────────────────────────────────────────────────────────

    public function test_approve_step_advances_to_next_step(): void
    {
        Notification::fake();

        [$template, $approver1, $approver2] = $this->makeTemplate(steps: 2);
        $document = $this->makeDocument();

        $workflow = $this->service->startWorkflow($document, $template, User::factory()->create());
        $step1    = $workflow->steps()->where('step_order', 1)->first();

        $this->service->approve($step1, $approver1, 'OK niveau 1');

        $workflow->refresh();

        $this->assertEquals(2, $workflow->current_step_order);
        $this->assertEquals('pending', $workflow->status);
        $this->assertEquals('approved', $step1->fresh()->status);
    }

    public function test_approve_first_step_notifies_second_approver(): void
    {
        Notification::fake();

        [$template, $approver1, $approver2] = $this->makeTemplate(steps: 2);
        $document = $this->makeDocument();

        $workflow = $this->service->startWorkflow($document, $template, User::factory()->create());
        $step1    = $workflow->steps()->where('step_order', 1)->first();

        // Clear notifications recorded during startWorkflow
        Notification::fake();

        $this->service->approve($step1, $approver1);

        Notification::assertSentTo($approver2, WorkflowStepRequestedNotification::class);
    }

    public function test_approve_last_step_closes_workflow_as_approved(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $initiator = User::factory()->create();
        $document  = $this->makeDocument();

        $workflow = $this->service->startWorkflow($document, $template, $initiator);
        $step     = $workflow->steps()->first();

        $this->service->approve($step, $approver1);

        $workflow->refresh();

        $this->assertEquals('approved', $workflow->status);
        $this->assertNotNull($workflow->completed_at);
    }

    public function test_approve_last_step_notifies_initiator(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $initiator = User::factory()->create();
        $document  = $this->makeDocument();

        $workflow = $this->service->startWorkflow($document, $template, $initiator);
        $step     = $workflow->steps()->first();

        Notification::fake();

        $this->service->approve($step, $approver1);

        Notification::assertSentTo($initiator, WorkflowCompletedNotification::class);
    }

    public function test_approve_throws_if_wrong_approver(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $document  = $this->makeDocument();
        $workflow  = $this->service->startWorkflow($document, $template, User::factory()->create());
        $step      = $workflow->steps()->first();
        $wrongUser = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->service->approve($step, $wrongUser);
    }

    public function test_approve_throws_if_step_already_decided(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $document = $this->makeDocument();
        $workflow = $this->service->startWorkflow($document, $template, User::factory()->create());
        $step     = $workflow->steps()->first();

        $this->service->approve($step, $approver1, 'premier');

        $this->expectException(\RuntimeException::class);
        $this->service->approve($step->fresh(), $approver1, 'deuxieme');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // reject
    // ─────────────────────────────────────────────────────────────────────────

    public function test_reject_closes_workflow_as_rejected(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 2);
        $document  = $this->makeDocument();
        $initiator = User::factory()->create();
        $workflow  = $this->service->startWorkflow($document, $template, $initiator);
        $step      = $workflow->steps()->where('step_order', 1)->first();

        $this->service->reject($step, $approver1, 'Non conforme');

        $workflow->refresh();

        $this->assertEquals('rejected', $workflow->status);
        $this->assertEquals('Non conforme', $workflow->final_comment);
        $this->assertNotNull($workflow->completed_at);
    }

    public function test_reject_notifies_initiator(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $initiator = User::factory()->create();
        $document  = $this->makeDocument();
        $workflow  = $this->service->startWorkflow($document, $template, $initiator);
        $step      = $workflow->steps()->first();

        Notification::fake();
        $this->service->reject($step, $approver1, 'Refusé');

        Notification::assertSentTo($initiator, WorkflowCompletedNotification::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // cancel
    // ─────────────────────────────────────────────────────────────────────────

    public function test_cancel_skips_remaining_steps_and_closes_workflow(): void
    {
        Notification::fake();

        [$template, $approver1, $approver2] = $this->makeTemplate(steps: 2);
        $initiator = User::factory()->create();
        $document  = $this->makeDocument();
        $workflow  = $this->service->startWorkflow($document, $template, $initiator);

        $this->service->cancel($workflow, $initiator, 'Annulé car obsolète');

        $workflow->refresh();

        $this->assertEquals('cancelled', $workflow->status);
        $this->assertNotNull($workflow->completed_at);

        // Both steps pending -> skipped
        $skipped = $workflow->steps()->where('status', 'skipped')->count();
        $this->assertEquals(2, $skipped);
    }

    public function test_cancel_notifies_initiator(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1);
        $initiator  = User::factory()->create();
        $document   = $this->makeDocument();
        $workflow   = $this->service->startWorkflow($document, $template, $initiator);

        Notification::fake();
        $this->service->cancel($workflow, $initiator);

        Notification::assertSentTo($initiator, WorkflowCompletedNotification::class);
    }

    public function test_cancel_throws_if_workflow_already_completed(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $document  = $this->makeDocument();
        $initiator = User::factory()->create();
        $workflow  = $this->service->startWorkflow($document, $template, $initiator);
        $step      = $workflow->steps()->first();

        $this->service->approve($step, $approver1);

        $this->expectException(\RuntimeException::class);
        $this->service->cancel($workflow->fresh(), $initiator);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getActiveWorkflow / getPendingStepsForUser
    // ─────────────────────────────────────────────────────────────────────────

    public function test_get_active_workflow_returns_pending_workflow(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1);
        $document   = $this->makeDocument();
        $workflow   = $this->service->startWorkflow($document, $template, User::factory()->create());

        $active = $this->service->getActiveWorkflow($document);

        $this->assertNotNull($active);
        $this->assertTrue($active->is($workflow));
    }

    public function test_get_active_workflow_returns_null_when_none(): void
    {
        $document = $this->makeDocument();

        $this->assertNull($this->service->getActiveWorkflow($document));
    }

    public function test_get_active_workflow_returns_null_after_completion(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $document  = $this->makeDocument();
        $workflow  = $this->service->startWorkflow($document, $template, User::factory()->create());
        $step      = $workflow->steps()->first();

        $this->service->approve($step, $approver1);

        $this->assertNull($this->service->getActiveWorkflow($document));
    }

    public function test_get_pending_steps_for_user_returns_correct_steps(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $document = $this->makeDocument();
        $this->service->startWorkflow($document, $template, User::factory()->create());

        $pendingSteps = $this->service->getPendingStepsForUser($approver1);

        $this->assertCount(1, $pendingSteps);
        $this->assertEquals(1, $pendingSteps->first()->step_order);
    }

    public function test_get_pending_steps_excludes_other_users(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1);
        $document   = $this->makeDocument();
        $otherUser  = User::factory()->create();
        $this->service->startWorkflow($document, $template, User::factory()->create());

        $pendingSteps = $this->service->getPendingStepsForUser($otherUser);

        $this->assertCount(0, $pendingSteps);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DocumentWorkflow model helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function test_is_pending_true_for_new_workflow(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1);
        $document   = $this->makeDocument();
        $workflow   = $this->service->startWorkflow($document, $template, User::factory()->create());

        $this->assertTrue($workflow->isPending());
        $this->assertFalse($workflow->isCompleted());
        $this->assertFalse($workflow->isApproved());
        $this->assertFalse($workflow->isRejected());
        $this->assertFalse($workflow->isCancelled());
    }

    public function test_status_label_pending(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1);
        $workflow   = $this->service->startWorkflow($this->makeDocument(), $template, User::factory()->create());

        $this->assertStringContainsStringIgnoringCase('cours', $workflow->statusLabel());
    }

    public function test_progress_percent_zero_at_start(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 2);
        $workflow   = $this->service->startWorkflow($this->makeDocument(), $template, User::factory()->create());

        $this->assertEquals(0, $workflow->progressPercent());
    }

    public function test_progress_percent_fifty_after_first_of_two_approved(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 2);
        $document  = $this->makeDocument();
        $workflow  = $this->service->startWorkflow($document, $template, User::factory()->create());
        $step1     = $workflow->steps()->where('step_order', 1)->first();

        $this->service->approve($step1, $approver1);

        $this->assertEquals(50, $workflow->fresh()->progressPercent());
    }

    public function test_progress_percent_hundred_after_all_approved(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $document  = $this->makeDocument();
        $workflow  = $this->service->startWorkflow($document, $template, User::factory()->create());
        $step      = $workflow->steps()->first();

        $this->service->approve($step, $approver1);

        $this->assertEquals(100, $workflow->fresh()->progressPercent());
    }

    public function test_current_step_returns_step_at_current_order(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 2);
        $workflow   = $this->service->startWorkflow($this->makeDocument(), $template, User::factory()->create());

        $current = $workflow->currentStep();

        $this->assertNotNull($current);
        $this->assertEquals(1, $current->step_order);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DocumentWorkflowStep model helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function test_step_is_pending_by_default(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1);
        $workflow   = $this->service->startWorkflow($this->makeDocument(), $template, User::factory()->create());
        $step       = $workflow->steps()->first();

        $this->assertTrue($step->isPending());
        $this->assertFalse($step->isApproved());
        $this->assertFalse($step->isRejected());
    }

    public function test_step_action_label_approve(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1, action: 'approve');
        $workflow   = $this->service->startWorkflow($this->makeDocument(), $template, User::factory()->create());
        $step       = $workflow->steps()->first();

        $this->assertEquals('Approbation', $step->actionLabel());
    }

    public function test_step_action_label_review(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1, action: 'review');
        $workflow   = $this->service->startWorkflow($this->makeDocument(), $template, User::factory()->create());
        $step       = $workflow->steps()->first();

        $this->assertEquals('Revue', $step->actionLabel());
    }

    public function test_step_action_label_validate(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1, action: 'validate');
        $workflow   = $this->service->startWorkflow($this->makeDocument(), $template, User::factory()->create());
        $step       = $workflow->steps()->first();

        $this->assertEquals('Validation', $step->actionLabel());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Document model relationship
    // ─────────────────────────────────────────────────────────────────────────

    public function test_document_workflows_relation_returns_workflows(): void
    {
        Notification::fake();

        [$template] = $this->makeTemplate(steps: 1);
        $document   = $this->makeDocument();
        $this->service->startWorkflow($document, $template, User::factory()->create());

        $this->assertCount(1, $document->workflows);
    }

    public function test_document_active_workflow_returns_null_after_completion(): void
    {
        Notification::fake();

        [$template, $approver1] = $this->makeTemplate(steps: 1);
        $document  = $this->makeDocument();
        $workflow  = $this->service->startWorkflow($document, $template, User::factory()->create());

        $this->service->approve($workflow->steps()->first(), $approver1);

        $document->unsetRelation('workflows');
        $this->assertNull($document->activeWorkflow());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private static int $seq = 0;

    /**
     * Creates a WorkflowTemplate with $steps approver steps.
     *
     * Returns [WorkflowTemplate, User $approver1, User $approver2, ...]
     */
    private function makeTemplate(int $steps = 2, string $action = 'approve'): array
    {
        self::$seq++;

        $template = WorkflowTemplate::create([
            'name'       => 'Modèle ' . self::$seq,
            'is_active'  => true,
            'created_by' => null,
        ]);

        $users = [];
        for ($i = 1; $i <= $steps; $i++) {
            $approver = User::factory()->create();
            WorkflowTemplateStep::create([
                'workflow_template_id' => $template->id,
                'step_order'           => $i,
                'label'                => 'Étape ' . $i,
                'approver_user_id'     => $approver->id,
                'action'               => $action,
            ]);
            $users[] = $approver;
        }

        return [$template, ...$users];
    }

    private function makeDocument(array $overrides = []): Document
    {
        self::$seq++;

        return Document::create(array_merge([
            'reference_doc'         => 'WF-' . self::$seq,
            'titre'                 => 'Document workflow ' . self::$seq,
            'type_document'         => 'Facture',
            'etat_cycle_vie'        => 'Brouillon',
            'auteur_id'             => $this->author->id,
            'confidentiality_level' => 'Standard',
            'parapheur_status'      => 'not_required',
        ], $overrides));
    }
}
