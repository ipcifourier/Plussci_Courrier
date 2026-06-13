<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Models\Document;
use App\Models\ReportCategory;
use App\Models\ReportTemplate;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Classification')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('reference')
                        ->label('Reference')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('report_category_id')
                        ->label('Type de rapport')
                        ->options(fn () => ReportCategory::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nouvelle categorie')
                                ->required()
                                ->maxLength(255)
                                ->unique(table: 'report_categories', column: 'name'),
                        ])
                        ->createOptionUsing(fn (array $data): int => ReportCategory::query()->create([
                            'name' => trim($data['name']),
                            'is_active' => true,
                        ])->id),

                    Forms\Components\Select::make('report_template_id')
                        ->label('Modele institutionnel PLUSS')
                        ->options(fn () => ReportTemplate::query()->where('is_validated', true)->orderBy('title')->pluck('title', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false),

                    Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->options([
                            'draft' => 'Brouillon',
                            'validated' => 'Valide',
                            'archived' => 'Archive',
                        ])
                        ->default('draft')
                        ->required()
                        ->native(false),

                    Forms\Components\Toggle::make('requires_approval')
                        ->label('Activer workflow d\'approbation')
                        ->default(false)
                        ->live(),
                ]),

            Section::make('Circuit d\'approbation')
                ->description('Definissez les approbateurs par niveau (N1, N2, ...).')
                ->visible(fn ($get): bool => (bool) $get('requires_approval'))
                ->schema([
                    Forms\Components\Repeater::make('approvals')
                        ->relationship()
                        ->defaultItems(1)
                        ->reorderable(false)
                        ->schema([
                            Forms\Components\TextInput::make('level')
                                ->label('Niveau')
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            Forms\Components\Select::make('approver_id')
                                ->label('Approbateur')
                                ->relationship('approver', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->native(false),

                            Forms\Components\Hidden::make('status')
                                ->default('pending'),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['status'] = $data['status'] ?? 'pending';

                            return $data;
                        })
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Metadonnees rapport')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('objet')
                        ->label('Objet')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('lieu')
                        ->label('Lieu')
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\Select::make('organizer_id')
                        ->label('Organisateur')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false),

                    Forms\Components\DatePicker::make('date_start')
                        ->label('Date debut')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\DatePicker::make('date_end')
                        ->label('Date fin')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\TagsInput::make('participants_json')
                        ->label('Participants')
                        ->separator(',')
                        ->columnSpanFull(),
                ]),

            Section::make('Liens mission / TDR')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('mission_courrier_id')
                        ->label('Courrier de mission')
                        ->relationship('missionCourrier', 'reference')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\Select::make('tdr_document_id')
                        ->label('Document TDR')
                        ->relationship('tdrDocument', 'reference_doc')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),

            Section::make('Pieces et metadonnees libres')
                ->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('files')
                        ->label('Fichiers du rapport')
                        ->collection('reports')
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),

                    Forms\Components\KeyValue::make('metadata_json')
                        ->label('Metadonnees complementaires')
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn (): ?int => Auth::id()),
                ]),
        ]);
    }
}
