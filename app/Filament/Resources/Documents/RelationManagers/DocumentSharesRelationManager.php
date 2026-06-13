<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Models\DocumentShare;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DocumentShareService;
use App\Services\OnlyOfficeService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DocumentSharesRelationManager extends RelationManager
{
    protected static string $relationship = 'shares';

    protected static ?string $title = 'SYSGED Share - Liens';

    // ── Form ─────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

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
                ->visible(fn ($get) => $get('type') === 'internal' && $this->getOwnerRecord()->isCollaborativeEditingEnabled())
                ->helperText('Disponible seulement si la co-édition est activée sur le document.'),

            Forms\Components\DateTimePicker::make('expires_at')
                ->label('Date d\'expiration')
                ->nullable()
                ->native(false)
                ->helperText('Laisser vide = pas d\'expiration (interne uniquement recommandé).'),

        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->heading('SYSGED Share - Liens de partage du document')
            ->description('Espace unifie des acces partages (interne/externe) avec actions de copie, renouvellement, revocation et ouverture editeur.')
            ->emptyStateHeading('Aucun partage actif')
            ->emptyStateDescription('Partagez ce document avec un collaborateur en cliquant sur « Nouveau partage ».')
            ->emptyStateIcon('heroicon-o-share')

            ->columns([
                BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'internal' => 'Interne',
                        'external' => 'Externe',
                        default    => $state,
                    })
                    ->colors(['primary' => 'internal', 'warning' => 'external']),

                TextColumn::make('recipientUser.name')
                    ->label('Destinataire')
                    ->placeholder(fn (DocumentShare $record): string => $record->recipient_email ?? '—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('recipient_email')
                    ->label('E-mail externe')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('can_download')
                    ->label('Télécharg.')
                    ->boolean()
                    ->trueIcon('heroicon-m-arrow-down-tray')
                    ->falseIcon('heroicon-m-minus'),

                IconColumn::make('can_comment')
                    ->label('Commentaires')
                    ->boolean()
                    ->trueIcon('heroicon-m-chat-bubble-left')
                    ->falseIcon('heroicon-m-minus'),

                IconColumn::make('can_edit')
                    ->label('Co-édition')
                    ->boolean()
                    ->trueIcon('heroicon-m-pencil-square')
                    ->falseIcon('heroicon-m-minus'),

                TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->color(fn (DocumentShare $record): ?string => $record->isExpired() ? 'danger' : null)
                    ->sortable(),

                BadgeColumn::make('statut')
                    ->label('Statut')
                    ->state(fn (DocumentShare $record): string => match (true) {
                        $record->isRevoked() => 'Révoqué',
                        $record->isExpired() => 'Expiré',
                        default              => 'Actif',
                    })
                    ->colors([
                        'success' => 'Actif',
                        'danger'  => 'Révoqué',
                        'warning' => 'Expiré',
                    ]),

                TextColumn::make('access_count')
                    ->label('Accès')
                    ->sortable(),

                TextColumn::make('last_accessed_at')
                    ->label('Dernier accès')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sharedBy.name')
                    ->label('Partagé par')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->headerActions([
                \Filament\Actions\Action::make('open_editor')
                    ->label('Editer (OnlyOffice)')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(function (): bool {
                        $document = $this->getOwnerRecord();
                        $onlyOffice = app(OnlyOfficeService::class);

                        return $onlyOffice->isEnabled() && (bool) $onlyOffice->getPrimaryOfficeMedia($document);
                    })
                    ->action(function (): void {
                        $document = $this->getOwnerRecord();
                        $url = route('onlyoffice.editor', ['document' => $document->id]);

                        app(AuditLogger::class)->log(
                            action: 'documents.onlyoffice.open',
                            entity: $document,
                            meta: ['source' => 'sysged_share.links'],
                        );

                        $this->js("window.open('{$url}', '_blank')");
                    }),

                \Filament\Actions\Action::make('new_share')
                    ->label('Nouveau partage')
                    ->icon('heroicon-o-share')
                    ->color('primary')
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
                            ->visible(fn ($get) => $get('type') === 'internal' && $this->getOwnerRecord()->isCollaborativeEditingEnabled())
                            ->helperText('Disponible seulement si la co-édition est activée sur le document.'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Date d\'expiration')
                            ->nullable()
                            ->native(false),
                    ])
                    ->action(function (array $data): void {
                        $document = $this->getOwnerRecord();
                        $user     = Auth::user();
                        $service  = app(DocumentShareService::class);

                        $expiresAt = isset($data['expires_at'])
                            ? Carbon::parse($data['expires_at'])
                            : null;

                        if ($data['type'] === 'internal') {
                            $recipient = User::find($data['recipient_user_id']);
                            if (! $recipient) {
                                Notification::make()->title('Utilisateur introuvable')->danger()->send();
                                return;
                            }
                            $service->shareWithUser(
                                $document,
                                $recipient,
                                $user,
                                (bool) ($data['can_download'] ?? false),
                                (bool) ($data['can_comment'] ?? false),
                                (bool) ($data['can_edit'] ?? false),
                                $expiresAt,
                            );
                        } else {
                            $service->shareWithEmail($document, $data['recipient_email'], $user, (bool) ($data['can_download'] ?? false), (bool) ($data['can_comment'] ?? false), false, $expiresAt);
                        }

                        Notification::make()->title('Partage créé et notification envoyée')->success()->send();
                    }),
            ])

            ->actions([
                Action::make('open_editor')
                    ->label('Editer')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(function (): bool {
                        $document = $this->getOwnerRecord();
                        $onlyOffice = app(OnlyOfficeService::class);

                        return $onlyOffice->isEnabled() && (bool) $onlyOffice->getPrimaryOfficeMedia($document);
                    })
                    ->action(function (): void {
                        $document = $this->getOwnerRecord();
                        $url = route('onlyoffice.editor', ['document' => $document->id]);

                        app(AuditLogger::class)->log(
                            action: 'documents.onlyoffice.open',
                            entity: $document,
                            meta: ['source' => 'sysged_share.links.row_action'],
                        );

                        $this->js("window.open('{$url}', '_blank')");
                    }),

                Action::make('copy_link')
                    ->label('Copier le lien')
                    ->icon('heroicon-o-clipboard')
                    ->color('info')
                    ->visible(fn (DocumentShare $record): bool => $record->isExternal() && $record->isValid())
                    ->action(function (DocumentShare $record): void {
                        $url = $record->shareUrl();
                        $this->js("
                            navigator.clipboard.writeText('{$url}')
                                .then(() => { })
                                .catch(() => { });
                        ");
                        Notification::make()
                            ->title('Lien copié dans le presse-papiers')
                            ->body($url)
                            ->success()
                            ->send();
                    }),

                Action::make('extend_expiry')
                    ->label('Renouveler')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn (DocumentShare $record): bool => $record->isValid())
                    ->form([
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Nouvelle date d\'expiration')
                            ->nullable()
                            ->native(false)
                            ->helperText('Laisser vide pour supprimer l\'expiration (accès permanent).'),
                    ])
                    ->fillForm(fn (DocumentShare $record): array => [
                        'expires_at' => $record->expires_at,
                    ])
                    ->action(function (DocumentShare $record, array $data): void {
                        $newExpiry = isset($data['expires_at']) && $data['expires_at']
                            ? Carbon::parse($data['expires_at'])
                            : null;

                        app(DocumentShareService::class)->extendExpiry($record, $newExpiry);

                        Notification::make()
                            ->title('Expiration mise à jour')
                            ->body($newExpiry ? 'Nouveau terme : ' . $newExpiry->format('d/m/Y à H:i') : 'Partage sans expiration.')
                            ->success()
                            ->send();
                    }),

                Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (DocumentShare $record): bool => $record->isValid())
                    ->requiresConfirmation()
                    ->modalHeading('Révoquer ce partage ?')
                    ->modalDescription('Le destinataire ne pourra plus accéder au document via ce partage. Pour les collaborateurs internes, la règle d\'accès associée est également supprimée.')
                    ->action(function (DocumentShare $record): void {
                        app(DocumentShareService::class)->revokeWithAccessRule($record);
                        Notification::make()->title('Partage révoqué')->success()->send();
                    }),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
