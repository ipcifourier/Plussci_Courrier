<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\User;
use App\Services\DocumentLockService;
use App\Services\OnlyOfficeService;
use App\Services\DocumentPresenceService;
use App\Services\DocumentShareService;
use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ViewDocument extends ViewRecord
{


    protected function getInfolistSchema(): array
    {
        return [
            \Filament\Infolists\Components\RepeatableEntry::make('cloud_links')
                ->label('Liens cloud associés')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('type')
                        ->label('Type'),
                    \Filament\Infolists\Components\TextEntry::make('url')
                        ->label('URL'),
                    \Filament\Infolists\Components\TextEntry::make('description')
                        ->label('Description'),
                ]),
        ];
    }
    protected static string $resource = DocumentResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $user     = Auth::user();
        $document = $this->getRecord();

        if ($user) {
            app(DocumentPresenceService::class)->join($document, $user, 'view');

            $heartbeatUrl = route('documents.presence.heartbeat', $document->id);
            $leaveUrl     = route('documents.presence.leave', $document->id);

            $this->js("
                const hbUrl    = '{$heartbeatUrl}';
                const leaveUrl = '{$leaveUrl}';
                const csrfToken = document.querySelector('meta[name=csrf-token]')?.content ?? '';
                const hbInterval = setInterval(() => {
                    fetch(hbUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    });
                }, 30000);
                window.addEventListener('beforeunload', () => {
                    clearInterval(hbInterval);
                    navigator.sendBeacon(leaveUrl + '?_token=' + encodeURIComponent(csrfToken));
                });
            ");
        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        /** @var \App\Models\Document $record */
        $record = $this->getRecord();
        $user   = Auth::user();
        $currentUser = $user instanceof User ? $user : null;

        // ── Presence indicator ─────────────────────────────────────────────────
        $presenceService = app(DocumentPresenceService::class);
        $sessions        = $presenceService->getActiveSessions($record);
        $others          = $sessions->where('user_id', '!=', $user?->id);

        if ($others->isNotEmpty()) {
            $names = $others
                ->map(fn ($s) => $s->user?->name . ($s->mode === 'edit' ? ' (\u{270E})' : ''))
                ->filter()
                ->implode(', ');

            $actions[] = Action::make('presence')
                ->label('Sur ce document : ' . $names)
                ->icon('heroicon-o-users')
                ->color('gray')
                ->disabled();
        }

        $lockService = app(DocumentLockService::class);
        $isSuperAdmin = $currentUser?->hasRole('Super Admin') ?? false;
        $officeService = app(OnlyOfficeService::class);
        $officeMedia = $officeService->getPrimaryOfficeMedia($record);

        if ($officeService->isEnabled() && $officeMedia) {
            $actions[] = Action::make('coedit_office')
                ->label('SYSGED Share - Editer (OnlyOffice)')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->action(function () use ($record): void {
                    $url = route('onlyoffice.editor', ['document' => $record->id]);

                    app(AuditLogger::class)->log(
                        action: 'documents.onlyoffice.open',
                        entity: $record,
                        meta: ['source' => 'document.view.header_action'],
                    );

                    $this->js("window.open('{$url}', '_blank')");
                });
        }

        // ── Lock-related actions ───────────────────────────────────────────────

        if ($record->isLockedByOther($user)) {
            $holder    = $lockService->getLockHolder($record);
            $expiresAt = $lockService->lockExpiresAt($record);

            $actions[] = Action::make('lock_info')
                ->label('Verrouillé par ' . ($holder?->name ?? '…') . ' jusqu\'à ' . ($expiresAt?->format('H:i') ?? '—'))
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->disabled();

            // Super Admin can force-release the lock
            if ($isSuperAdmin) {
            $actions[] = Action::make('force_release_lock')
                ->label('Forcer le déverrouillage')
                ->icon('heroicon-o-lock-open')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () use ($record): void {
                    app(DocumentLockService::class)->forceRelease($record);

                    Notification::make()
                        ->title('Verrou forcé libéré')
                        ->success()
                        ->send();

                    $this->redirect(DocumentResource::getUrl('view', ['record' => $record]));
                });
        }
        }

        $actions[] = EditAction::make();

        // ── Share action ────────────────────────────────────────────────────────

        $actions[] = Action::make('partager')
            ->label('Partager')
            ->icon('heroicon-o-share')
            ->color('info')
            ->form([
                Forms\Components\Radio::make('type')
                    ->label('Type de partage')
                    ->options([
                        'internal' => 'Collaborateur interne',
                        'external' => 'Collaborateur externe (e-mail)',
                    ])
                    ->default('internal')
                    ->inline()
                    ->live()
                    ->required(),

                Forms\Components\Select::make('recipient_user_id')
                    ->label('Utilisateur destinataire')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->visible(fn ($get) => $get('type') === 'internal'),

                Forms\Components\TextInput::make('recipient_email')
                    ->label('Adresse e-mail externe')
                    ->email()
                    ->required()
                    ->visible(fn ($get) => $get('type') === 'external'),

                Forms\Components\Toggle::make('can_download')
                    ->label('Autoriser le téléchargement')
                    ->default(false)
                    ->inline(false),

                Forms\Components\Toggle::make('can_comment')
                    ->label('Autoriser les commentaires')
                    ->default(false)
                    ->inline(false),

                Forms\Components\Toggle::make('can_edit')
                    ->label('Autoriser la co-édition')
                    ->default(false)
                    ->inline(false)
                    ->visible(fn ($get) => $get('type') === 'internal' && $record->isCollaborativeEditingEnabled())
                    ->helperText('Disponible seulement si la co-édition est activée sur le document.'),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Date d\'expiration')
                    ->nullable()
                    ->native(false),
            ])
            ->action(function (array $data) use ($record, $user): void {
                $service   = app(DocumentShareService::class);
                $expiresAt = isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

                if ($data['type'] === 'internal') {
                    $recipient = User::find($data['recipient_user_id']);
                    if (! $recipient) {
                        Notification::make()->title('Utilisateur introuvable')->danger()->send();
                        return;
                    }
                    $service->shareWithUser(
                        $record,
                        $recipient,
                        $user,
                        (bool) ($data['can_download'] ?? false),
                        (bool) ($data['can_comment'] ?? false),
                        (bool) ($data['can_edit'] ?? false),
                        $expiresAt,
                    );
                } else {
                    $service->shareWithEmail(
                        $record,
                        $data['recipient_email'],
                        $user,
                        (bool) ($data['can_download'] ?? false),
                        (bool) ($data['can_comment'] ?? false),
                        false,
                        $expiresAt,
                    );
                }

                Notification::make()->title('Document partagé — notification envoyée')->success()->send();
            });

        // ── Download actions ───────────────────────────────────────────────────

        $mediaItems = $record->getMedia('documents');

        foreach ($mediaItems as $index => $media) {
            $label = $mediaItems->count() === 1
                ? 'Télécharger'
                : 'Télécharger · ' . $media->file_name;

            $actions[] = Action::make('download_file_' . $index)
                ->label($label)
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url($media->getUrl())
                ->openUrlInNewTab();
        }

        return $actions;
    }
}
