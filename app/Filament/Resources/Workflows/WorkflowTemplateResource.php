<?php

namespace App\Filament\Resources\Workflows;

use App\Filament\Resources\Workflows\Pages\CreateWorkflowTemplate;
use App\Filament\Resources\Workflows\Pages\EditWorkflowTemplate;
use App\Filament\Resources\Workflows\Pages\ListWorkflowTemplates;
use App\Models\User;
use App\Models\WorkflowTemplate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WorkflowTemplateResource extends Resource
{
    protected static ?string $model = WorkflowTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Circuits de validation';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('admin.roles.manage')
        );
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        /** @var \Filament\Schemas\Schema $schema */
        return $schema->columns(2)->components([

            Forms\Components\TextInput::make('name')
                ->label('Nom du circuit')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->maxLength(1000)
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\Select::make('trigger_types')
                ->label('Types de documents déclencheurs')
                ->multiple()
                ->searchable()
                ->options(fn () => \App\Models\DocumentType::query()->orderBy('name')->pluck('name', 'name')->toArray())
                ->native(false)
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                        ->label('Nouveau type de document')
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'document_types', column: 'name'),
                ])
                ->createOptionUsing(fn (array $data): string => \App\Models\DocumentType::query()->create([
                    'name' => trim($data['name']),
                ])->name)
                ->columnSpanFull(),

            Forms\Components\CheckboxList::make('trigger_confidentiality_levels')
                ->label('Niveaux de confidentialité déclencheurs')
                ->hint('Laissez vide pour tous niveaux. Pour la règle V1, cochez Confidentiel.')
                ->options([
                    'Standard'     => 'Standard',
                    'Confidentiel' => 'Confidentiel',
                    'Personnel'    => 'Personnel',
                ])
                ->columns(3)
                ->gridDirection('row')
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\Toggle::make('auto_start')
                ->label('Démarrage automatique')
                ->helperText('Si actif, ce circuit peut être lancé automatiquement selon les règles de déclenchement.')
                ->default(false)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_active')
                ->label('Circuit actif')
                ->helperText('Seuls les circuits actifs peuvent être démarrés.')
                ->default(true)
                ->columnSpanFull(),

            // ── Steps repeater ────────────────────────────────────────────────
            Forms\Components\Repeater::make('steps')
                ->label('Étapes')
                ->relationship('steps')
                ->orderColumn('step_order')
                ->columnSpanFull()
                ->addActionLabel('Ajouter une étape')
                ->collapsible()
                ->cloneable()
                ->minItems(1)
                ->defaultItems(1)
                ->schema([
                    Forms\Components\TextInput::make('step_order')
                        ->label('Ordre')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(fn ($get) => 1)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('label')
                        ->label('Libellé')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ex : Visa DRH, Approbation directeur…')
                        ->columnSpan(3),

                    Forms\Components\Select::make('approver_user_id')
                        ->label('Approbateur')
                        ->options(
                            User::orderBy('name')->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->columnSpan(2),

                    Forms\Components\Select::make('escalation_user_id')
                        ->label('Escalade vers')
                        ->options(User::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->nullable()
                        ->helperText('Optionnel: utilisateur notifié si SLA dépassé.')
                        ->columnSpan(2),

                    Forms\Components\Select::make('action')
                        ->label('Action requise')
                        ->options([
                            'review'   => 'Revue',
                            'approve'  => 'Approbation',
                            'validate' => 'Validation',
                        ])
                        ->required()
                        ->native(false)
                        ->default('approve')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('sla_hours')
                        ->label('SLA (heures)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(720)
                        ->default(24)
                        ->required()
                        ->helperText('Délai maximum avant escalade automatique.')
                        ->columnSpan(2),
                ])
                ->columns(8),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('steps_count')
                    ->label('Étapes')
                    ->state(fn ($record): int => $record->stepCount())
                    ->sortable(false),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Actif')
                    ->formatStateUsing(fn ($state): string => $state ? 'Actif' : 'Inactif')
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),

                Tables\Columns\BadgeColumn::make('auto_start')
                    ->label('Auto')
                    ->formatStateUsing(fn ($state): string => $state ? 'Oui' : 'Non')
                    ->color(fn ($state): string => $state ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('trigger_types')
                    ->label('Types déclencheurs')
                    ->formatStateUsing(fn ($record): string => $record->triggersList() ?: '—')
                    ->wrap(),

                Tables\Columns\TextColumn::make('trigger_confidentiality_levels')
                    ->label('Confidentialité')
                    ->formatStateUsing(fn ($record): string => $record->confidentialityTriggersList() ?: '—')
                    ->wrap(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])

            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('name');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListWorkflowTemplates::route('/'),
            'create' => CreateWorkflowTemplate::route('/create'),
            'edit'   => EditWorkflowTemplate::route('/{record}/edit'),
        ];
    }
}
