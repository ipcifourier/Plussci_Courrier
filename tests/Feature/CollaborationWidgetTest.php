<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaborationWidgetTest extends TestCase
{
    use RefreshDatabase;

    private function createTask(User $assignee, User $assigner, string $status, ?string $dueDate = null): Task
    {
        return Task::query()->create([
            'titre'       => 'Tâche ' . $status,
            'assigner_id' => $assigner->id,
            'assignee_id' => $assignee->id,
            'priority'    => 'Normale',
            'status'      => $status,
            'due_date'    => $dueDate,
        ]);
    }

    public function test_my_tasks_counts_are_correct_per_status(): void
    {
        $me    = User::factory()->create();
        $other = User::factory()->create();

        // My tasks
        $this->createTask($me, $other, 'todo');
        $this->createTask($me, $other, 'todo');
        $this->createTask($me, $other, 'doing');
        $this->createTask($me, $other, 'done');

        // Someone else's tasks — should NOT count for me
        $this->createTask($other, $me, 'todo');
        $this->createTask($other, $me, 'doing');

        $myTodo  = Task::where('assignee_id', $me->id)->where('status', 'todo')->count();
        $myDoing = Task::where('assignee_id', $me->id)->where('status', 'doing')->count();
        $myDone  = Task::where('assignee_id', $me->id)->where('status', 'done')->count();

        $this->assertEquals(2, $myTodo);
        $this->assertEquals(1, $myDoing);
        $this->assertEquals(1, $myDone);
    }

    public function test_overdue_tasks_count_excludes_done_and_cancelled(): void
    {
        $me    = User::factory()->create();
        $other = User::factory()->create();

        $pastDate   = now()->subDays(2)->toDateString();
        $futureDate = now()->addDays(5)->toDateString();

        // Overdue active tasks
        $this->createTask($me, $other, 'todo',      $pastDate);
        $this->createTask($me, $other, 'doing',     $pastDate);

        // Done with past due — NOT overdue
        $this->createTask($me, $other, 'done',      $pastDate);
        $this->createTask($me, $other, 'cancelled', $pastDate);

        // Future due — NOT overdue
        $this->createTask($me, $other, 'todo', $futureDate);

        // No due date — NOT overdue
        $this->createTask($me, $other, 'doing', null);

        $overdue = Task::where('assignee_id', $me->id)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        $this->assertEquals(2, $overdue);
    }

    public function test_global_overdue_count_across_all_users(): void
    {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $pastDate = now()->subDays(1)->toDateString();

        $this->createTask($alice, $bob, 'todo',  $pastDate);
        $this->createTask($bob,   $alice, 'doing', $pastDate);
        $this->createTask($alice, $bob, 'done',  $pastDate); // excluded

        $allOverdue = Task::whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        $this->assertEquals(2, $allOverdue);
    }
}
