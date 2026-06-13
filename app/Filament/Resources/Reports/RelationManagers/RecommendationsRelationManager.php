<?php

namespace App\Filament\Resources\Reports\RelationManagers;

use App\Models\User;
use App\Services\ReportRecommendationExtractionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RecommendationsRelationManager extends RelationManager
{
    protected static string $relationship = 'recommendations';

    protected static ?string $title = 'Recommandations & decisions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Textarea::make('recommendation')
                ->label('Recommandation extraite')
                ->required()
                ->rows(4)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('decision')
                ->label('Decision')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\Select::make('responsible_user_id')
                ->label('Responsable')
                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->native(false),

            Forms\Components\DatePicker::make('due_date')
                ->label('Delai')
                ->native(false)
                ->nullable(),

            Forms\Components\Select::make('implementation_status')
                ->label('Statut')
                ->options([
                    'not_started' => 'Non demarree',
                    'in_progress' => 'En cours',
                    'implemented' => 'Mise en oeuvre',
                    'partially_implemented' => 'Partiellement mise en oeuvre',
                    'blocked' => 'Bloquee',
                    'cancelled' => 'Annulee',
                ])
                ->default('not_started')
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('progress_percent')
                ->label('Avancement (%)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(0)
                ->required(),

            Forms\Components\Textarea::make('implementation_notes')
                ->label('Notes de mise en oeuvre')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\Hidden::make('created_by')
                ->default(fn (): ?int => Auth::id()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recommendation')
                    ->label('Recommandation')
                    ->limit(70)
                    ->searchable(),

                TextColumn::make('responsible.name')
                    ->label('Responsable')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('due_date')
                    ->label('Delai')
                    ->date('d/m/Y')
                    ->sortable(),

                BadgeColumn::make('implementation_status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_started' => 'Non demarree',
                        'in_progress' => 'En cours',
                        'implemented' => 'Mise en oeuvre',
                        'partially_implemented' => 'Partielle',
                        'blocked' => 'Bloquee',
                        'cancelled' => 'Annulee',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'not_started',
                        'info' => 'in_progress',
                        'success' => 'implemented',
                        'warning' => 'partially_implemented',
                        'danger' => 'blocked',
                        'secondary' => 'cancelled',
                    ]),

                TextColumn::make('progress_percent')
                    ->label('Avancement')
                    ->suffix('%')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('extract_semi_auto')
                    ->label('Extraction semi-auto')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('max_recommendations')
                            ->label('Nombre maximum')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->default(10)
                            ->required(),
                        Forms\Components\Toggle::make('include_decisions')
                            ->label('Inclure les decisions detectees')
                            ->default(true),
                    ])
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User && $user->can('reports.recommendations.create');
                    })
                    ->action(function (array $data): void {
                        $report = $this->getOwnerRecord()->loadMissing([
                            'template',
                            'category',
                            'organizer',
                            'missionCourrier',
                            'tdrDocument',
                            'recommendations',
                        ]);

                        $items = app(ReportRecommendationExtractionService::class)->extract(
                            $report,
                            (bool) ($data['include_decisions'] ?? true),
                            (int) ($data['max_recommendations'] ?? 10),
                        );

                        if (empty($items)) {
                            Notification::make()
                                ->title('Aucune recommandation detectee')
                                ->warning()
                                ->send();

                            return;
                        }

                        $existing = $report->recommendations
                            ->pluck('recommendation')
                            ->map(fn (string $text): string => mb_strtolower(trim($text)))
                            ->flip();

                        $created = 0;
                        $skipped = 0;

                        foreach ($items as $item) {
                            $key = mb_strtolower(trim($item['recommendation']));

                            if ($existing->has($key)) {
                                $skipped++;

                                continue;
                            }

                            $report->recommendations()->create([
                                'recommendation' => $item['recommendation'],
                                'decision' => $item['decision'],
                                'implementation_status' => 'not_started',
                                'progress_percent' => 0,
                                'created_by' => Auth::id(),
                            ]);

                            $existing->put($key, true);
                            $created++;
                        }

                        Notification::make()
                            ->title('Extraction terminee')
                            ->body("{$created} creees, {$skipped} deja existantes")
                            ->success()
                            ->send();
                    }),

                CreateAction::make()
                    ->label('Extraire une recommandation'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('due_date', 'asc');
    }
}
