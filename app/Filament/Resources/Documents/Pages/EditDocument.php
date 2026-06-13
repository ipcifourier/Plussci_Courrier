<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Notifications\DocumentEditedNotification;
use App\Services\DocumentLockService;
use App\Services\DocumentPresenceService;
use App\Services\DocumentReferenceService;
use App\Services\DocumentVersioningService;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(DocumentReferenceService::class)->ensureReference($data);
    }

    /** Snapshot of document updated_at when edit session opened. */
    public ?int $editSnapshotAt = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $user     = Auth::user();
        $document = $this->getRecord();
        $isAdminOverride = $user instanceof User && $user->can('admin.roles.manage');

        if ($document->isReadOnlyFinalized() && ! $isAdminOverride) {
            Notification::make()
                ->title('Document en lecture seule')
                ->body('Ce document a été finalisé. Seul un administrateur peut lever le verrou de finalisation.')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(DocumentResource::getUrl('view', ['record' => $document]));

            return;
        }

        $isCollaborative = $document->isCollaborativeEditingEnabled();

        $lockService = app(DocumentLockService::class);

        if (! $isCollaborative && ! $lockService->acquire($document, $user)) {
            $holder    = $lockService->getLockHolder($document);
            $expiresAt = $lockService->lockExpiresAt($document);
            $until     = $expiresAt ? $expiresAt->format('H:i') : '—';

            Notification::make()
                ->title('Document verrouillé')
                ->body('Ce document est actuellement modifié par <strong>' . e($holder?->name ?? 'un autre utilisateur') . '</strong> jusqu\'à ' . $until . '. Votre accès est en lecture seule.')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(DocumentResource::getUrl('view', ['record' => $document]));
            return;
        }

        $this->editSnapshotAt = $document->updated_at?->timestamp;

        app(DocumentPresenceService::class)->join($document, $user, 'edit');

        $heartbeatUrl = route('documents.presence.heartbeat', $document->id);
        $leaveUrl     = route('documents.presence.leave', $document->id);

        $this->js("
            const hbUrl = '{$heartbeatUrl}';
            const leaveUrl = '{$leaveUrl}';
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.content ?? '';
            const hbInterval = setInterval(() => {
                fetch(hbUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
            }, 30000);
            window.addEventListener('beforeunload', () => {
                clearInterval(hbInterval);
                navigator.sendBeacon(leaveUrl + '?_token=' + encodeURIComponent(csrfToken));
            });
        ");
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('release_lock')
                ->label('Libérer le verrou')
                ->icon('heroicon-o-lock-open')
                ->color('primary')
                ->outlined()
                ->extraAttributes(['class' => 'pluss-release-lock-action'])
                ->requiresConfirmation()
                ->modalHeading('Libérer le verrou d\'édition')
                ->modalDescription('Voulez-vous libérer le verrou et quitter le mode édition ?')
                ->visible(fn (): bool => ! $this->getRecord()->isCollaborativeEditingEnabled())
                ->action(function (): void {
                    $user     = Auth::user();
                    $document = $this->getRecord();

                    app(DocumentLockService::class)->release($document, $user);
                    app(DocumentPresenceService::class)->leave($document, $user);

                    Notification::make()->title('Verrou libéré')->success()->send();

                    $this->redirect(DocumentResource::getUrl('view', ['record' => $document]));
                }),

            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        if ($this->editSnapshotAt === null) {
            return;
        }

        $current = $this->getRecord()->fresh();

        if ($current->updated_at?->timestamp !== $this->editSnapshotAt) {
            $who = $current->updated_at?->format('d/m/Y H:i') ?? 'un autre utilisateur';

            Notification::make()
                ->title('Conflit d\'édition détecté')
                ->body('Ce document a été modifié (' . $who . ') pendant votre session. Vos modifications sont sauvegardées, mais vérifiez les changements.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $user   = Auth::user();
        $isCollaborative = $record->isCollaborativeEditingEnabled();

        app(DocumentVersioningService::class)->syncUnversionedMedia($record);

        if (! $isCollaborative) {
            app(DocumentLockService::class)->release($record, $user);
        }

        $presence = app(DocumentPresenceService::class);
        $sessions = $presence->getActiveSessions($record);

        foreach ($sessions as $session) {
            if ($session->user_id !== $user->id && $session->user) {
                $session->user->notify(new DocumentEditedNotification($record, $user));
            }
        }

        if (! $isCollaborative) {
            $presence->leave($record, $user);
        }
    }
}
