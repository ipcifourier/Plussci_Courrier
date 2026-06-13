<?php

namespace Tests\Feature;

use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourrier(User $user): Courrier
    {
        $correspondant = Correspondant::query()->create(['nom_structure' => 'Test Org']);

        return Courrier::query()->create([
            'type'                   => 'Entrant',
            'reference'              => 'REF-TASK-001',
            'date_reception_envoi'   => now()->toDateString(),
            'objet'                  => 'Test tâche',
            'priorite'               => 'Normale',
            'statut'                 => 'Nouveau',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id'       => $correspondant->id,
            'user_id'                => $user->id,
        ]);
    }

    public function test_creating_task_sends_notification_to_assignee(): void
    {
        Notification::fake();

        $assigner = User::factory()->create();
        $assignee = User::factory()->create();
        $courrier = $this->makeCourrier($assigner);

        $task = Task::query()->create([
            'taskable_type' => Courrier::class,
            'taskable_id'   => $courrier->id,
            'titre'         => 'Rédiger la réponse',
            'assigner_id'   => $assigner->id,
            'assignee_id'   => $assignee->id,
            'priority'      => 'Normale',
            'status'        => 'todo',
        ]);

        Notification::assertSentTo($assignee, TaskAssignedNotification::class);
    }

    public function test_no_notification_when_assigner_is_assignee(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $courrier = $this->makeCourrier($user);

        Task::query()->create([
            'taskable_type' => Courrier::class,
            'taskable_id'   => $courrier->id,
            'titre'         => 'Tâche auto-assignée',
            'assigner_id'   => $user->id,
            'assignee_id'   => $user->id,
            'priority'      => 'Basse',
            'status'        => 'todo',
        ]);

        Notification::assertNothingSent();
    }

    public function test_status_change_creates_task_history_record(): void
    {
        $assigner = User::factory()->create();
        $assignee = User::factory()->create();
        $courrier = $this->makeCourrier($assigner);

        $task = Task::query()->create([
            'taskable_type' => Courrier::class,
            'taskable_id'   => $courrier->id,
            'titre'         => 'Traitement urgent',
            'assigner_id'   => $assigner->id,
            'assignee_id'   => $assignee->id,
            'priority'      => 'Haute',
            'status'        => 'todo',
        ]);

        // Simulate status change
        $this->actingAs($assignee);
        $task->update(['status' => 'doing']);

        $this->assertDatabaseHas('task_histories', [
            'task_id'     => $task->id,
            'from_status' => 'todo',
            'to_status'   => 'doing',
        ]);
    }

    public function test_no_history_created_when_status_unchanged(): void
    {
        $assigner = User::factory()->create();
        $assignee = User::factory()->create();
        $courrier = $this->makeCourrier($assigner);

        $task = Task::query()->create([
            'taskable_type' => Courrier::class,
            'taskable_id'   => $courrier->id,
            'titre'         => 'Tâche sans changement',
            'assigner_id'   => $assigner->id,
            'assignee_id'   => $assignee->id,
            'priority'      => 'Normale',
            'status'        => 'todo',
        ]);

        $task->update(['titre' => 'Titre modifié mais statut identique']);

        $this->assertDatabaseCount('task_histories', 0);
    }

    public function test_is_overdue_returns_true_for_past_due_active_tasks(): void
    {
        $user = User::factory()->create();

        $task = Task::query()->create([
            'titre'       => 'Tâche en retard',
            'assigner_id' => $user->id,
            'assignee_id' => $user->id,
            'priority'    => 'Urgente',
            'status'      => 'doing',
            'due_date'    => now()->subDays(3)->toDateString(),
        ]);

        $this->assertTrue($task->isOverdue());
    }

    public function test_is_overdue_returns_false_for_completed_tasks(): void
    {
        $user = User::factory()->create();

        $task = Task::query()->create([
            'titre'       => 'Tâche terminée en retard',
            'assigner_id' => $user->id,
            'assignee_id' => $user->id,
            'priority'    => 'Normale',
            'status'      => 'done',
            'due_date'    => now()->subDays(1)->toDateString(),
        ]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_is_overdue_returns_false_when_no_due_date(): void
    {
        $user = User::factory()->create();

        $task = Task::query()->create([
            'titre'       => 'Tâche sans échéance',
            'assigner_id' => $user->id,
            'assignee_id' => $user->id,
            'priority'    => 'Normale',
            'status'      => 'doing',
            'due_date'    => null,
        ]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_task_can_exist_without_a_taskable(): void
    {
        $user = User::factory()->create();

        $task = Task::query()->create([
            'titre'       => 'Tâche standalone',
            'assigner_id' => $user->id,
            'assignee_id' => $user->id,
            'priority'    => 'Basse',
            'status'      => 'todo',
        ]);

        $this->assertNull($task->taskable_type);
        $this->assertNull($task->taskable_id);
    }
}
