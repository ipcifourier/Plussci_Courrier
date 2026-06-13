<?php

namespace App\Http\Controllers;

use App\Models\FileManagerOfflineTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileManagerOfflineTaskController extends Controller
{
    /**
     * List pending offline tasks for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_if(! $user, 403);

        $tasks = FileManagerOfflineTask::query()
            ->where('user_id', $user->id)
            ->where('status', FileManagerOfflineTask::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    /**
     * Store a new offline task.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_if(! $user instanceof \App\Models\User, 403);

        $validated = $request->validate([
            'type'    => ['required', 'string', 'max:64'],
            'payload' => ['nullable', 'array'],
        ]);

        $task = FileManagerOfflineTask::create([
            'user_id' => $user->id,
            'type'    => $validated['type'],
            'payload' => $validated['payload'] ?? null,
            'status'  => FileManagerOfflineTask::STATUS_PENDING,
        ]);

        return response()->json(['data' => $task], 201);
    }

    /**
     * Update the status of an offline task.
     */
    public function update(Request $request, FileManagerOfflineTask $task): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_if(! $user instanceof \App\Models\User, 403);
        abort_unless((int) $task->user_id === (int) $user->id || $user->hasRole('Super Admin'), 403);

        $validated = $request->validate([
            'status'        => ['required', 'string', 'in:pending,done,failed'],
            'error_message' => ['nullable', 'string', 'max:500'],
        ]);

        $task->update([
            'status'        => $validated['status'],
            'error_message' => $validated['error_message'] ?? null,
            'executed_at'   => in_array($validated['status'], ['done', 'failed'], true) ? now() : null,
        ]);

        return response()->json(['data' => $task]);
    }

    /**
     * Delete an offline task.
     */
    public function destroy(Request $request, FileManagerOfflineTask $task): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_if(! $user instanceof \App\Models\User, 403);
        abort_unless((int) $task->user_id === (int) $user->id || $user->hasRole('Super Admin'), 403);

        $task->delete();

        return response()->json(null, 204);
    }

    /**
     * Sync and execute all pending tasks for the authenticated user.
     */
    public function sync(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_if(! $user instanceof \App\Models\User, 403);

        $tasks = FileManagerOfflineTask::query()
            ->where('user_id', $user->id)
            ->where('status', FileManagerOfflineTask::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        $processed = 0;
        $failed    = 0;

        foreach ($tasks as $task) {
            try {
                // Delegate actual execution to the sync controller
                app(FileManagerSyncController::class)->processTaskPayload($task);

                $task->update([
                    'status'      => FileManagerOfflineTask::STATUS_DONE,
                    'executed_at' => now(),
                ]);

                $processed++;
            } catch (\Throwable $e) {
                $task->update([
                    'status'        => FileManagerOfflineTask::STATUS_FAILED,
                    'executed_at'   => now(),
                    'error_message' => mb_substr($e->getMessage(), 0, 500),
                ]);

                $failed++;
            }
        }

        return response()->json([
            'processed' => $processed,
            'failed'    => $failed,
        ]);
    }
}
