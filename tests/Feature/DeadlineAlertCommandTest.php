<?php

namespace Tests\Feature;

use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\Imputation;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ImputationAssignedNotification;
use App\Notifications\ImputationRelanceNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskDeadlineReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeadlineAlertCommandTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeCourrier(User $user): Courrier
    {
        $correspondant = Correspondant::query()->firstOrCreate(
            ['nom_structure' => 'Org Deadline'],
            [],
        );

        return Courrier::query()->create([
            'type'                   => 'Entrant',
            'reference'              => 'REF-DL-' . uniqid(),
            'date_reception_envoi'   => now()->toDateString(),
            'objet'                  => 'Courrier deadline test',
            'priorite'               => 'Normale',
            'statut'                 => 'Nouveau',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id'       => $correspondant->id,
            'user_id'                => $user->id,
        ]);
    }

    private function makeTask(User $assignee, User $assigner, string $status, ?string $dueDate, ?string $alerteSentAt = null): Task
    {
        $task = Task::query()->create([
            'titre'              => 'Tâche DL ' . uniqid(),
            'assigner_id'        => $assigner->id,
            'assignee_id'        => $assignee->id,
            'priority'           => 'Normale',
            'status'             => $status,
            'due_date'           => $dueDate,
            'alerte_envoyee_at'  => $alerteSentAt,
        ]);

        $assignee->notifications()->where('type', TaskAssignedNotification::class)->delete();

        return $task;
    }

    private function makeImputation(User $destinataire, User $expediteur, Courrier $courrier, string $statut, ?string $delai, ?string $relanceAt = null): Imputation
    {
        $imp = Imputation::query()->create([
            'courrier_id'         => $courrier->id,
            'expediteur_id'       => $expediteur->id,
            'destinataire_id'     => $destinataire->id,
            'instructions'        => 'À traiter',
            'statut_traitement'   => $statut,
            'delai_traitement'    => $delai,
            'relance_envoyee_at'  => $relanceAt,
        ]);

        $destinataire->notifications()->where('type', ImputationAssignedNotification::class)->delete();

        return $imp;
    }

    private function assertNotificationStoredFor(User $user, string $type): void
    {
        $this->assertGreaterThan(0, $user->notifications()->where('type', $type)->count());
    }

    private function assertNotificationNotStoredFor(User $user, string $type): void
    {
        $this->assertSame(0, $user->notifications()->where('type', $type)->count());
    }

    // ─── Task deadline alerts ────────────────────────────────────────────────────

    public function test_sends_alert_for_task_due_within_24h(): void
    {
        $assignee = $this->makeUser();
        $assigner = $this->makeUser();
        $dueDate  = now()->addHours(12)->toDateString();

        $task = $this->makeTask($assignee, $assigner, 'doing', $dueDate);

        $this->artisan('deadline:send-alerts')->assertSuccessful();

        $this->assertNotificationStoredFor($assignee, TaskDeadlineReminderNotification::class);
        $this->assertNotNull($task->fresh()->alerte_envoyee_at);
    }

    public function test_sends_alert_for_overdue_task(): void
    {
        $assignee = $this->makeUser();
        $assigner = $this->makeUser();
        $dueDate  = now()->subDays(2)->toDateString();

        $task = $this->makeTask($assignee, $assigner, 'todo', $dueDate);

        $this->artisan('deadline:send-alerts')->assertSuccessful();

        $this->assertNotificationStoredFor($assignee, TaskDeadlineReminderNotification::class);
    }

    public function test_does_not_send_alert_if_already_sent_today(): void
    {
        $assignee = $this->makeUser();
        $assigner = $this->makeUser();
        $dueDate  = now()->subDays(1)->toDateString();
        $sentAt   = now()->toDateTimeString();

        $this->makeTask($assignee, $assigner, 'todo', $dueDate, $sentAt);

        $this->artisan('deadline:send-alerts')->assertSuccessful();

        $this->assertNotificationNotStoredFor($assignee, TaskDeadlineReminderNotification::class);
    }

    public function test_does_not_send_alert_for_done_task(): void
    {
        $assignee = $this->makeUser();
        $assigner = $this->makeUser();
        $dueDate  = now()->subDays(2)->toDateString();

        $this->makeTask($assignee, $assigner, 'done', $dueDate);

        $this->artisan('deadline:send-alerts')->assertSuccessful();

        $this->assertNotificationNotStoredFor($assignee, TaskDeadlineReminderNotification::class);
    }

    public function test_does_not_send_alert_for_cancelled_task(): void
    {
        $assignee = $this->makeUser();
        $assigner = $this->makeUser();
        $dueDate  = now()->subDays(2)->toDateString();

        $this->makeTask($assignee, $assigner, 'cancelled', $dueDate);

        $this->artisan('deadline:send-alerts')->assertSuccessful();

        $this->assertNotificationNotStoredFor($assignee, TaskDeadlineReminderNotification::class);
    }

    public function test_does_not_send_alert_for_future_task_beyond_window(): void
    {
        $assignee = $this->makeUser();
        $assigner = $this->makeUser();
        $dueDate  = now()->addDays(5)->toDateString();

        $this->makeTask($assignee, $assigner, 'todo', $dueDate);

        $this->artisan('deadline:send-alerts', ['--hours' => 24])->assertSuccessful();

        $this->assertNotificationNotStoredFor($assignee, TaskDeadlineReminderNotification::class);
    }

    public function test_dry_run_does_not_send_task_notification(): void
    {
        $assignee = $this->makeUser();
        $assigner = $this->makeUser();
        $dueDate  = now()->subDays(1)->toDateString();

        $task = $this->makeTask($assignee, $assigner, 'todo', $dueDate);

        $this->artisan('deadline:send-alerts', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('[DRY-RUN]');

        $this->assertNotificationNotStoredFor($assignee, TaskDeadlineReminderNotification::class);
        $this->assertNull($task->fresh()->alerte_envoyee_at);
    }

    // ─── Imputation relance alerts ───────────────────────────────────────────────

    public function test_sends_relance_for_overdue_imputation(): void
    {
        $expediteur  = $this->makeUser();
        $destinataire = $this->makeUser();
        $courrier    = $this->makeCourrier($expediteur);
        $yesterday   = now()->subDay()->toDateString();

        $imp = $this->makeImputation($destinataire, $expediteur, $courrier, 'En attente', $yesterday);

        $this->artisan('deadline:send-alerts')->assertSuccessful();

        $this->assertNotificationStoredFor($destinataire, ImputationRelanceNotification::class);
        $this->assertNotNull($imp->fresh()->relance_envoyee_at);
    }

    public function test_does_not_relance_if_already_sent_today(): void
    {
        $expediteur  = $this->makeUser();
        $destinataire = $this->makeUser();
        $courrier    = $this->makeCourrier($expediteur);
        $yesterday   = now()->subDay()->toDateString();
        $sentAt      = now()->toDateTimeString();

        $this->makeImputation($destinataire, $expediteur, $courrier, 'En cours', $yesterday, $sentAt);

        $this->artisan('deadline:send-alerts')->assertSuccessful();

        $this->assertNotificationNotStoredFor($destinataire, ImputationRelanceNotification::class);
    }

    public function test_does_not_relance_traite_imputation(): void
    {
        $expediteur  = $this->makeUser();
        $destinataire = $this->makeUser();
        $courrier    = $this->makeCourrier($expediteur);
        $yesterday   = now()->subDay()->toDateString();

        $this->makeImputation($destinataire, $expediteur, $courrier, 'Traité', $yesterday);

        $this->artisan('deadline:send-alerts')->assertSuccessful();

        $this->assertNotificationNotStoredFor($destinataire, ImputationRelanceNotification::class);
    }

    public function test_dry_run_does_not_send_imputation_relance(): void
    {
        $expediteur  = $this->makeUser();
        $destinataire = $this->makeUser();
        $courrier    = $this->makeCourrier($expediteur);
        $yesterday   = now()->subDay()->toDateString();

        $imp = $this->makeImputation($destinataire, $expediteur, $courrier, 'En attente', $yesterday);

        $this->artisan('deadline:send-alerts', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertNotificationNotStoredFor($destinataire, ImputationRelanceNotification::class);
        $this->assertNull($imp->fresh()->relance_envoyee_at);
    }

    // ─── Model helpers ──────────────────────────────────────────────────────────

    public function test_task_is_approaching_deadline_when_within_hours(): void
    {
        $user = $this->makeUser();
        $task = $this->makeTask($user, $user, 'doing', now()->addHours(6)->toDateString());

        $this->assertTrue($task->isApproachingDeadline(24));
    }

    public function test_task_is_not_approaching_when_far_future(): void
    {
        $user = $this->makeUser();
        $task = $this->makeTask($user, $user, 'doing', now()->addDays(10)->toDateString());

        $this->assertFalse($task->isApproachingDeadline(24));
    }

    public function test_imputation_is_overdue_when_delai_passed(): void
    {
        $expediteur  = $this->makeUser();
        $destinataire = $this->makeUser();
        $courrier    = $this->makeCourrier($expediteur);

        $imp = $this->makeImputation($destinataire, $expediteur, $courrier, 'En attente', now()->subDay()->toDateString());

        $this->assertTrue($imp->isOverdue());
    }

    public function test_imputation_is_not_overdue_when_treated(): void
    {
        $expediteur  = $this->makeUser();
        $destinataire = $this->makeUser();
        $courrier    = $this->makeCourrier($expediteur);

        $imp = $this->makeImputation($destinataire, $expediteur, $courrier, 'Traité', now()->subDay()->toDateString());

        $this->assertFalse($imp->isOverdue());
    }

    public function test_imputation_is_not_overdue_when_no_delai(): void
    {
        $expediteur  = $this->makeUser();
        $destinataire = $this->makeUser();
        $courrier    = $this->makeCourrier($expediteur);

        $imp = $this->makeImputation($destinataire, $expediteur, $courrier, 'En attente', null);

        $this->assertFalse($imp->isOverdue());
    }
}
