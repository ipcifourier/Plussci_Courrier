<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Dossier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileManagerSyncController extends Controller
{
    /**
     * Process a batch of offline sync operations from the desktop client.
     *
     * Expected payload:
     * {
     *   "ops": [
     *     { "type": "move", "model": "document", "id": 1, "dossier_id": 3 },
     *     { "type": "rename", "model": "dossier", "id": 2, "libelle": "New name" },
     *     { "type": "delete", "model": "document", "id": 5 }
     *   ]
     * }
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_if(! $user instanceof \App\Models\User, 403);
        abort_unless($user->hasRole('Super Admin') || $user->hasPermissionTo('ged.documents.update'), 403);

        $validated = $request->validate([
            'ops'            => ['required', 'array', 'max:200'],
            'ops.*.type'     => ['required', 'string', 'in:move,rename,delete'],
            'ops.*.model'    => ['required', 'string', 'in:document,dossier'],
            'ops.*.id'       => ['required', 'integer', 'min:1'],
        ]);

        $results = [];

        foreach ($validated['ops'] as $index => $op) {
            try {
                $result = $this->processOp($op, $request->input("ops.{$index}"));
                $results[] = ['index' => $index, 'ok' => true, 'result' => $result];
            } catch (\Throwable $e) {
                Log::warning('FileManagerSync op failed', ['op' => $op, 'error' => $e->getMessage()]);
                $results[] = ['index' => $index, 'ok' => false, 'error' => $e->getMessage()];
            }
        }

        $failed = collect($results)->where('ok', false)->count();

        return response()->json([
            'processed' => count($results),
            'failed'    => $failed,
            'results'   => $results,
        ], $failed > 0 ? 207 : 200);
    }

    private function processOp(array $op, array $rawOp): array
    {
        $model = $op['model'];
        $id    = $op['id'];
        $type  = $op['type'];

        $entity = match ($model) {
            'document' => Document::findOrFail($id),
            'dossier'  => Dossier::findOrFail($id),
        };

        return match ($type) {
            'move'   => $this->opMove($entity, $rawOp),
            'rename' => $this->opRename($entity, $rawOp),
            'delete' => $this->opDelete($entity),
        };
    }

    private function opMove(Document|Dossier $entity, array $op): array
    {
        if ($entity instanceof Document) {
            $dossierId = isset($op['dossier_id']) ? (int) $op['dossier_id'] : null;
            $entity->update(['dossier_id' => $dossierId]);
        } else {
            $parentId = isset($op['parent_id']) ? (int) $op['parent_id'] : null;
            $entity->update(['parent_id' => $parentId]);
        }

        return ['id' => $entity->id, 'action' => 'moved'];
    }

    private function opRename(Document|Dossier $entity, array $op): array
    {
        if ($entity instanceof Document && isset($op['titre'])) {
            $entity->update(['titre' => (string) $op['titre']]);
        } elseif ($entity instanceof Dossier && isset($op['libelle'])) {
            $entity->update(['libelle' => (string) $op['libelle']]);
        }

        return ['id' => $entity->id, 'action' => 'renamed'];
    }

    private function opDelete(Document|Dossier $entity): array
    {
        $id = $entity->id;
        $entity->delete();

        return ['id' => $id, 'action' => 'deleted'];
    }

    /**
     * Execute a single offline task payload (used by FileManagerOfflineTaskController::sync).
     */
    public function processTaskPayload(\App\Models\FileManagerOfflineTask $task): void
    {
        $payload = $task->payload ?? [];
        $op      = [
            'type'  => $payload['type'] ?? '',
            'model' => $payload['model'] ?? '',
            'id'    => $payload['id'] ?? 0,
        ];

        $this->processOp($op, $payload);
    }
}
