<?php

namespace App\Filament\Resources\Shared\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Commentaires';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Textarea::make('body')
                ->label('Commentaire')
                ->placeholder('Utilisez @nom pour mentionner un collègue…')
                ->required()
                ->rows(4)
                ->maxLength(5000)
                ->columnSpanFull(),

            Forms\Components\Select::make('kind')
                ->label('Type')
                ->options([
                    'comment' => 'Commentaire',
                    'annotation' => 'Annotation',
                ])
                ->default('comment')
                ->required()
                ->native(false)
                ->live(),

            Forms\Components\TextInput::make('annotation_data.page')
                ->label('Page (annotation)')
                ->numeric()
                ->minValue(1)
                ->visible(fn ($get): bool => $get('kind') === 'annotation'),

            Forms\Components\TextInput::make('annotation_data.selection')
                ->label('Zone / texte ciblé')
                ->maxLength(255)
                ->placeholder('Ex: Paragraphe 2, section objet')
                ->visible(fn ($get): bool => $get('kind') === 'annotation'),

            Forms\Components\Toggle::make('is_internal')
                ->label('Note interne (visible uniquement par les agents)')
                ->default(false),

            Forms\Components\Hidden::make('user_id')
                ->default(fn (): int => Auth::id()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Auteur')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('body')
                    ->label('Commentaire')
                    ->limit(120)
                    ->wrap()
                    ->searchable(),

                BadgeColumn::make('is_internal')
                    ->label('Type')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Interne' : 'Public')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success'),

                BadgeColumn::make('kind')
                    ->label('Nature')
                    ->formatStateUsing(fn (?string $state): string => $state === 'annotation' ? 'Annotation' : 'Commentaire')
                    ->color(fn (?string $state): string => $state === 'annotation' ? 'info' : 'gray'),

                TextColumn::make('annotation_data')
                    ->label('Ancrage')
                    ->formatStateUsing(function ($state, $record): string {
                        if (($record->kind ?? 'comment') !== 'annotation') {
                            return '—';
                        }

                        $page      = data_get($record, 'annotation_data.page');
                        $selection = data_get($record, 'annotation_data.selection');

                        $parts = [];
                        if ($page) {
                            $parts[] = 'p.' . $page;
                        }
                        if ($selection) {
                            $parts[] = (string) $selection;
                        }

                        return empty($parts) ? 'Annotation libre' : implode(' | ', $parts);
                    })
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un commentaire / annotation')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
                        $data['kind'] ??= 'comment';

                        return $data;
                    }),
            ])
            ->actions([
                DeleteAction::make()
                    ->visible(function ($record): bool {
                        $currentUser = Auth::user();

                        return $record->user_id === Auth::id()
                            || ($currentUser instanceof \App\Models\User && $currentUser->hasRole('Super Admin'));
                    }),
            ])
            ->emptyStateHeading('Aucun commentaire pour l\'instant.');
    }
}
