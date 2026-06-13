<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\DocumentType;
use App\Models\Gtt;
use App\Models\InterventionDomain;
use App\Models\InterventionSubdomain;
use App\Services\DocumentReferenceService;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\SpatieMediaLibraryFileUpload::make('fichiers')
                    ->label('Fichiers')
                    ->collection('documents')
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'image/jpeg',
                        'image/png',
                        'text/plain',
                        'text/csv',
                        'application/zip',
                    ])
                    ->maxSize(20480)
                    ->downloadable()
                    ->openable()
                    ->previewable(false)
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('cloud_links')
                    ->label('Liens cloud associés')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'OneDrive' => 'OneDrive',
                                'Google Drive' => 'Google Drive',
                                'Dropbox' => 'Dropbox',
                                'Autre' => 'Autre',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('url')
                            ->label('URL du lien')
                            ->url()
                            ->required(),
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->nullable(),
                    ])
                    ->addActionLabel('Ajouter un lien')
                    ->columnSpanFull(),

                Forms\Components\Radio::make('reference_mode')
                    ->label('Mode de reference')
                    ->options([
                        DocumentReferenceService::MODE_MANUAL => 'Saisir',
                        DocumentReferenceService::MODE_GENERATE => 'Generer automatiquement',
                    ])
                    ->default(fn (): string => app(DocumentReferenceService::class)->defaultMode())
                    ->inline()
                    ->live()
                    ->afterStateUpdated(function ($state, $set): void {
                        if ($state === DocumentReferenceService::MODE_GENERATE) {
                            $set('reference_doc', null);
                        }
                    })
                    ->required(),

                Forms\Components\TextInput::make('reference_doc')
                    ->label('Reference document')
                    ->required(function ($get): bool {
                        return ! app(DocumentReferenceService::class)->shouldGenerate(
                            (string) $get('reference_mode'),
                            (string) $get('type_document'),
                        );
                    })
                    ->readOnly(function ($get): bool {
                        return app(DocumentReferenceService::class)->shouldGenerate(
                            (string) $get('reference_mode'),
                            (string) $get('type_document'),
                        );
                    })
                    ->placeholder('Generee automatiquement a l\'enregistrement')
                    ->helperText(function ($get): string {
                        $service = app(DocumentReferenceService::class);

                        if (! $service->shouldGenerate((string) $get('reference_mode'), (string) $get('type_document'))) {
                            return 'Saisissez la reference transmise avec le document.';
                        }

                        return 'Apercu: ' . $service->preview((string) $get('type_document'));
                    })
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('titre')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('type_document')
                    ->label('Type de document')
                    ->required()
                    ->options(fn () => DocumentType::query()->orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, $set): void {
                        $service = app(DocumentReferenceService::class);
                        $mode = $service->modeForType((string) $state);
                        $set('reference_mode', $mode);

                        if ($mode === DocumentReferenceService::MODE_GENERATE) {
                            $set('reference_doc', null);
                        }
                    })
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nouveau type de document')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'document_types', column: 'name'),
                    ])
                    ->createOptionUsing(fn (array $data): string => DocumentType::query()->create([
                        'name' => trim($data['name']),
                    ])->name),

                Section::make('Affectation metier')
                    ->icon('heroicon-o-squares-plus')
                    ->schema([
                        Forms\Components\Select::make('intervention_domain_id')
                            ->label('Domaine d\'intervention')
                            ->options(fn () => InterventionDomain::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nouveau domaine')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(table: 'intervention_domains', column: 'name'),
                            ])
                            ->createOptionUsing(fn (array $data): int => InterventionDomain::query()->create([
                                'name' => strtoupper(trim($data['name'])),
                            ])->id)
                            ->live()
                            ->afterStateUpdated(function ($set): void {
                                $set('intervention_subdomain_id', null);
                            }),

                        Forms\Components\Select::make('intervention_subdomain_id')
                            ->label('Sous-domaine')
                            ->options(function ($get) {
                                $domainId = $get('intervention_domain_id');

                                if (! $domainId) {
                                    return [];
                                }

                                return InterventionSubdomain::query()
                                    ->where('intervention_domain_id', $domainId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('gtt_id')
                            ->label('GTT associé')
                            ->options(fn () => Gtt::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable(),
                    ]),
            ]);
    }
}