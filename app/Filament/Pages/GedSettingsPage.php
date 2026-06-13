<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\GedSettingsService;
use App\Services\DocumentReferenceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class GedSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Parametres GED';

    protected static ?string $title = 'Parametres GED';

    protected static ?int $navigationSort = 23;

    protected string $view = 'filament.pages.ged-settings';

    public ?array $data = [];

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

    public function mount(GedSettingsService $settings): void
    {
        $lifecycle = $settings->lifecycle();
        $reference = $settings->referenceConfig();

        $typeModesRows = [];
        foreach (($reference['type_modes'] ?? []) as $type => $mode) {
            $type = trim((string) $type);
            if ($type === '') {
                continue;
            }

            $typeModesRows[] = [
                'type_document' => $type,
                'mode' => $mode === DocumentReferenceService::MODE_GENERATE
                    ? DocumentReferenceService::MODE_GENERATE
                    : DocumentReferenceService::MODE_MANUAL,
            ];
        }

        $this->form->fill([
            'max_file_size_mb' => $settings->maxFileSizeMb(),
            'upload_quota_mb' => $settings->uploadQuotaMb(),
            'courrier_archive_after_days' => (int) ($lifecycle['courrier_archive_after_days'] ?? 90),
            'document_archive_after_days' => (int) ($lifecycle['document_archive_after_days'] ?? 365),
            'retention_by_type' => $settings->retentionByType(),
            'reference_auto_enabled' => (bool) ($reference['auto_enabled'] ?? true),
            'reference_default_mode' => (string) ($reference['default_mode'] ?? DocumentReferenceService::MODE_MANUAL),
            'reference_format' => (string) ($reference['format'] ?? 'DOC/{TYPE_CODE}/{YYYY}/{SEQ}'),
            'reference_sequence_scope' => (string) ($reference['sequence_scope'] ?? 'yearly'),
            'reference_sequence_padding' => (int) ($reference['sequence_padding'] ?? 4),
            'reference_type_modes' => $typeModesRows,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Capacites et limites')
                    ->description('Quotas et tailles maximales en megaoctets (Mo).')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('max_file_size_mb')
                            ->label('Taille max fichier (Mo)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1024)
                            ->required(),

                        Forms\Components\TextInput::make('upload_quota_mb')
                            ->label('Quota utilisateur (Mo)')
                            ->numeric()
                            ->minValue(50)
                            ->maxValue(102400)
                            ->required(),
                    ]),

                Section::make('Cycle de vie')
                    ->description('Delais automatiques d\'archivage en jours.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('courrier_archive_after_days')
                            ->label('Archivage courrier traite (jours)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('document_archive_after_days')
                            ->label('Archivage document valide (jours)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),

                Section::make('Retention par type')
                    ->description('Nombre d\'annees de conservation par type de document.')
                    ->schema([
                        Forms\Components\KeyValue::make('retention_by_type')
                            ->label('Regles de retention')
                            ->keyLabel('Type')
                            ->valueLabel('Annees')
                            ->columnSpanFull(),
                    ]),

                Section::make('Codification des references documentaires')
                    ->description('Choisissez entre saisie manuelle ou generation automatique, puis definissez le format de reference.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('reference_auto_enabled')
                            ->label('Activer la generation automatique')
                            ->inline(false)
                            ->default(true),

                        Forms\Components\Select::make('reference_default_mode')
                            ->label('Mode par defaut')
                            ->options([
                                DocumentReferenceService::MODE_MANUAL => 'Saisir',
                                DocumentReferenceService::MODE_GENERATE => 'Generer',
                            ])
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make('reference_format')
                            ->label('Format de reference')
                            ->placeholder('DOC/{TYPE_CODE}/{YYYY}/{SEQ}')
                            ->helperText('Variables disponibles: {TYPE}, {TYPE_CODE}, {YYYY}, {YY}, {MM}, {SEQ}')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('reference_sequence_scope')
                            ->label('Portee du compteur')
                            ->options([
                                'global' => 'Global (jamais reinitialise)',
                                'yearly' => 'Annuel',
                                'monthly' => 'Mensuel',
                            ])
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make('reference_sequence_padding')
                            ->label('Longueur de sequence')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->required(),

                        Forms\Components\Repeater::make('reference_type_modes')
                            ->label('Modes par type de document')
                            ->helperText('Surcharge du mode par type. Exemple: Courrier entrant = Saisir, PV reunion = Generer.')
                            ->schema([
                                Forms\Components\TextInput::make('type_document')
                                    ->label('Type de document')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Select::make('mode')
                                    ->label('Mode')
                                    ->options([
                                        DocumentReferenceService::MODE_MANUAL => 'Saisir',
                                        DocumentReferenceService::MODE_GENERATE => 'Generer',
                                    ])
                                    ->native(false)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->default([])
                            ->columnSpanFull()
                            ->addActionLabel('Ajouter une regle de type'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settings = app(GedSettingsService::class);

        $retentionRaw = $state['retention_by_type'] ?? [];
        $retention = [];

        foreach ($retentionRaw as $type => $years) {
            $type = trim((string) $type);
            $years = (int) $years;

            if ($type === '' || $years <= 0) {
                continue;
            }

            $retention[$type] = $years;
        }

        $typeModesRaw = $state['reference_type_modes'] ?? [];
        $typeModes = [];

        foreach ($typeModesRaw as $row) {
            $type = trim((string) ($row['type_document'] ?? ''));
            $mode = (string) ($row['mode'] ?? DocumentReferenceService::MODE_MANUAL);

            if ($type === '') {
                continue;
            }

            $typeModes[$type] = $mode === DocumentReferenceService::MODE_GENERATE
                ? DocumentReferenceService::MODE_GENERATE
                : DocumentReferenceService::MODE_MANUAL;
        }

        $settings->set('ged.max_file_size_mb', (int) ($state['max_file_size_mb'] ?? 50));
        $settings->set('ged.upload_quota_mb', (int) ($state['upload_quota_mb'] ?? 500));
        $settings->set('ged.lifecycle', [
            'courrier_archive_after_days' => (int) ($state['courrier_archive_after_days'] ?? 90),
            'document_archive_after_days' => (int) ($state['document_archive_after_days'] ?? 365),
        ]);
        $settings->set('ged.retention_by_type', $retention);
        $settings->set('ged.reference', [
            'auto_enabled' => (bool) ($state['reference_auto_enabled'] ?? true),
            'default_mode' => (string) ($state['reference_default_mode'] ?? DocumentReferenceService::MODE_MANUAL),
            'format' => trim((string) ($state['reference_format'] ?? 'DOC/{TYPE_CODE}/{YYYY}/{SEQ}')),
            'sequence_scope' => (string) ($state['reference_sequence_scope'] ?? 'yearly'),
            'sequence_padding' => (int) ($state['reference_sequence_padding'] ?? 4),
            'type_modes' => $typeModes,
        ]);

        Notification::make()
            ->title('Parametres GED enregistres')
            ->success()
            ->send();
    }
}
