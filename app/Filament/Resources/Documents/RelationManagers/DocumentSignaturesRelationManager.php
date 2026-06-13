<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Models\User;
use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class DocumentSignaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'signatures';

    protected static ?string $title = 'Parapheur';

    public function isReadOnly(): bool
    {
        return false;
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Forms\Components\TextInput::make('level')
                ->label('Ordre (niveau)')
                ->numeric()
                ->minValue(1)
                ->maxValue(10)
                ->required()
                ->helperText('1 = premier à signer, 2 = deuxième, etc.'),

            Forms\Components\Select::make('signataire_id')
                ->label('Signataire')
                ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('role_signature')
                ->label('Rôle dans le circuit')
                ->options([
                    'Visa'        => 'Visa',
                    'Approbation' => 'Approbation',
                    'Signature'   => 'Signature',
                ])
                ->default('Signature')
                ->required()
                ->native(false),

            Forms\Components\Hidden::make('status')
                ->default('pending'),

        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->heading('Circuit de parapheur')
            ->description('Définissez les signataires et leur ordre avant de lancer le circuit.')
            ->emptyStateHeading('Aucun signataire configuré')
            ->emptyStateDescription('Ajoutez des signataires pour activer le parapheur électronique.')
            ->emptyStateIcon('heroicon-o-pencil')
            ->defaultSort('level')

            ->columns([
                TextColumn::make('level')
                    ->label('Ordre')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('signataire.name')
                    ->label('Signataire')
                    ->searchable(),

                BadgeColumn::make('role_signature')
                    ->label('Rôle')
                    ->colors([
                        'gray'    => 'Visa',
                        'warning' => 'Approbation',
                        'success' => 'Signature',
                    ]),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'  => 'En attente',
                        'signed'   => 'Signé',
                        'rejected' => 'Rejeté',
                        default    => $state,
                    })
                    ->colors([
                        'gray'    => 'pending',
                        'success' => 'signed',
                        'danger'  => 'rejected',
                    ]),

                TextColumn::make('signed_at')
                    ->label('Signé le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                TextColumn::make('comment')
                    ->label('Commentaire')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un signataire')
                    ->disabled(fn (): bool => $this->getOwnerRecord()->parapheur_status === 'pending'),
            ])

            ->actions([
                // ── Signer ───────────────────────────────────────────────────
                Action::make('signer')
                    ->label('Signer')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(function ($record): bool {
                        $doc = $this->getOwnerRecord();

                        return $doc->parapheur_status === 'pending'
                            && $record->status === 'pending'
                            && $record->signataire_id === Auth::id()
                            && $record->level === $doc->current_signature_level;
                    })
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Commentaire (optionnel)')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data): void {
                        /** @var \App\Models\Document $doc */
                        $doc = $this->getOwnerRecord();

                        $record->update([
                            'status'     => 'signed',
                            'comment'    => $data['comment'] ?? null,
                            'signed_at'  => now(),
                            'ip_address' => Request::ip(),
                        ]);

                        app(AuditLogger::class)->log(
                            action: 'documents.parapheur.sign',
                            entity: $doc,
                            meta: ['level' => $record->level, 'signataire_id' => Auth::id()],
                        );

                        // Vérifier si tous les signataires du niveau ont signé
                        $pendingAtLevel = $doc->signatures()
                            ->where('level', $doc->current_signature_level)
                            ->where('status', 'pending')
                            ->count();

                        if ($pendingAtLevel > 0) {
                            Notification::make()->title('Signature enregistrée')->success()->send();
                            return;
                        }

                        // Passer au niveau suivant ou clôturer
                        $nextLevel = $doc->signatures()
                            ->where('status', 'pending')
                            ->min('level');

                        if ($nextLevel) {
                            $doc->update(['current_signature_level' => $nextLevel]);
                            $doc->notifyCurrentSignataires();
                        } else {
                            $doc->update([
                                'parapheur_status'        => 'completed',
                                'current_signature_level' => null,
                                'etat_cycle_vie'          => 'Valide',
                            ]);
                            $doc->notifyAuteurDecision('completed');
                        }

                        Notification::make()->title('Signature enregistrée')->success()->send();
                    }),

                // ── Rejeter ──────────────────────────────────────────────────
                Action::make('rejeter')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function ($record): bool {
                        $doc = $this->getOwnerRecord();

                        return $doc->parapheur_status === 'pending'
                            && $record->status === 'pending'
                            && $record->signataire_id === Auth::id()
                            && $record->level === $doc->current_signature_level;
                    })
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data): void {
                        /** @var \App\Models\Document $doc */
                        $doc = $this->getOwnerRecord();

                        $record->update([
                            'status'    => 'rejected',
                            'comment'   => $data['comment'],
                            'signed_at' => now(),
                        ]);

                        $doc->update([
                            'parapheur_status'        => 'rejected',
                            'current_signature_level' => null,
                        ]);

                        $doc->notifyAuteurDecision('rejected', $data['comment']);

                        app(AuditLogger::class)->log(
                            action: 'documents.parapheur.reject',
                            entity: $doc,
                            meta: ['level' => $record->level, 'signataire_id' => Auth::id(), 'comment' => $data['comment']],
                        );

                        Notification::make()->title('Circuit rejeté')->danger()->send();
                    }),

                DeleteAction::make()
                    ->disabled(fn ($record): bool => $record->status !== 'pending'
                        || $this->getOwnerRecord()->parapheur_status === 'pending'),
            ]);
    }
}
