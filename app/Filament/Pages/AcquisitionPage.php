<?php

namespace App\Filament\Pages;

use App\Models\Dossier;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\DocumentVersion;
use App\Services\AutoClassificationService;
use App\Services\DocumentImportService;
use App\Services\EmailImportService;
use App\Services\GedSettingsService;
use App\Services\OcrService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AcquisitionPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Acquisition & OCR';

    protected static ?string $title = 'Acquisition & Numérisation';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.acquisition';

    public ?array $data = [];

    public string $scanBrowserCurrentPath = '';

    public static function getNavigationGroup(): ?string
    {
        return 'GED';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('ged.documents.create')
        );
    }

    public function mount(): void
    {
        $requestedDossierId = request()->integer('dossier_id');
        $visibleDossierId = $requestedDossierId
            ? Dossier::query()->visibleTo(Auth::user())->whereKey($requestedDossierId)->value('id')
            : null;

        $this->form->fill([
            'dossier_id' => $visibleDossierId,
            'type_document' => '__auto__',
            'auto_detect_type' => true,
            'auto_suggest_dossier' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $settings = app(GedSettingsService::class);
        $maxMb = $settings->maxFileSizeMb();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Déposer ou glisser des fichiers')
                    ->description('Glissez vos fichiers ici ou cliquez pour sélectionner. Les documents seront indexés et analysés par OCR.')
                    ->icon('heroicon-o-document-arrow-up')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('files')
                            ->label('Fichiers')
                            ->multiple()
                            ->maxFiles(20)
                            ->maxSize($maxMb * 1024)
                            ->disk('local')
                            ->directory('tmp/acquisition')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'image/tiff',
                                'text/plain',
                            ])
                            ->helperText("Formats acceptés : PDF, Word, Excel, JPG, PNG, TIFF, TXT — Max {$maxMb} Mo par fichier")
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\Select::make('dossier_id')
                            ->label('Dossier cible')
                            ->options(fn () => $this->getActiveDossierOptions())
                            ->searchable()
                            ->nullable()
                            ->helperText('Laisser vide pour un document non classé'),

                        Forms\Components\Select::make('type_document')
                            ->label('Type de document')
                            ->options(fn () => collect(['__auto__' => 'Détection automatique'])
                                ->union(DocumentType::query()->orderBy('name')->pluck('name', 'name')))
                            ->required()
                            ->default('__auto__')
                            ->native(false)
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

                        Forms\Components\TextInput::make('titre_prefixe')
                            ->label('Préfixe du titre (optionnel)')
                            ->maxLength(100)
                            ->helperText('Sera ajouté devant le nom de chaque fichier'),

                        Forms\Components\Toggle::make('auto_detect_type')
                            ->label('Reconnaissance automatique du type')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Détecte automatiquement les factures, contrats, rapports, courriers et autres types à partir du nom et de l\'OCR.'),

                        Forms\Components\Toggle::make('auto_suggest_dossier')
                            ->label('Classement intelligent')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Propose automatiquement le meilleur dossier cible si aucun dossier n\'est sélectionné manuellement.'),

                        Forms\Components\Select::make('confidentiality_level')
                            ->label('Confidentialité')
                            ->options([
                                'Standard'      => 'Standard',
                                'Confidentiel'  => 'Confidentiel',
                                'Personnel'     => 'Personnel',
                            ])
                            ->default('Standard')
                            ->required(),
                    ]),
            ]);
    }

    // -------------------------------------------------------------------------
    // Submit — upload form
    // -------------------------------------------------------------------------

    public function save(DocumentImportService $importer): void
    {
        $state = $this->form->getState();
        $files = $state['files'] ?? [];

        if (empty($files)) {
            Notification::make()->title('Aucun fichier sélectionné')->warning()->send();
            return;
        }

        // ── Quota check ───────────────────────────────────────────────────────
        $quotaMb    = app(\App\Services\GedSettingsService::class)->uploadQuotaMb();
        $quotaBytes = $quotaMb * 1024 * 1024;
        $batchBytes = 0;

        foreach ($files as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                $batchBytes += (int) $file->getSize();
            } elseif (is_string($file)) {
                $abs = Storage::disk('local')->path($file);
                if (file_exists($abs)) {
                    $batchBytes += (int) filesize($abs);
                }
            }
        }

        if ($batchBytes > $quotaBytes) {
            Notification::make()
                ->title('Quota dépassé')
                ->body("Le volume total du lot (" . $this->formatBytes($batchBytes) . ") dépasse le quota autorisé ({$quotaMb} Mo).")
                ->danger()
                ->send();
            return;
        }
        // ─────────────────────────────────────────────────────────────────────

        $selectedType = $state['type_document'] ?? '__auto__';
        $autoDetectType = (bool) ($state['auto_detect_type'] ?? true);
        $autoSuggestDossier = (bool) ($state['auto_suggest_dossier'] ?? true);
        $defaultDossierId = $state['dossier_id'] ?? null;
        $classifier = app(AutoClassificationService::class);

        $prefix  = trim($state['titre_prefixe'] ?? '');
        $count   = 0;
        $failed  = 0;

        foreach ($files as $file) {
            try {
                // After form submission, FileUpload returns stored paths (strings)
                // on the configured disk. TemporaryUploadedFile is a fallback.
                if ($file instanceof TemporaryUploadedFile) {
                    $originalName = $file->getClientOriginalName();
                    $uploadedFile = $file;
                } elseif (is_string($file)) {
                    $absolutePath = Storage::disk('local')->path($file);
                    if (! file_exists($absolutePath)) {
                        $failed++;
                        continue;
                    }
                    $originalName = basename($file);
                    $uploadedFile = new UploadedFile(
                        $absolutePath,
                        $originalName,
                        mime_content_type($absolutePath) ?: null,
                        null,
                        true // already stored, test mode = skip is_uploaded_file check
                    );
                } else {
                    $failed++;
                    continue;
                }

                $titre = $prefix
                    ? $prefix . ' — ' . pathinfo($originalName, PATHINFO_FILENAME)
                    : pathinfo($originalName, PATHINFO_FILENAME);

                $detectedType = $this->resolveDetectedType(
                    classifier: $classifier,
                    title: $titre,
                    originalName: $originalName,
                    selectedType: $selectedType,
                    autoDetectType: $autoDetectType,
                );

                $targetDossierId = $defaultDossierId ?: (
                    $autoSuggestDossier
                        ? $this->suggestDossierId($detectedType, $originalName)
                        : null
                );

                $importer->import($uploadedFile, [
                    'type_document' => $detectedType,
                    'dossier_id' => $targetDossierId,
                    'confidentiality_level' => $state['confidentiality_level'] ?? 'Standard',
                    'titre'  => $titre,
                    'source' => 'upload',
                    'source_meta' => json_encode([
                        'auto_detect_type' => $autoDetectType,
                        'auto_suggest_dossier' => $autoSuggestDossier,
                        'detected_type' => $detectedType,
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                $count++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        if ($count > 0) {
            Notification::make()
                ->title("{$count} document(s) importé(s) avec succès")
                ->body($failed > 0 ? "{$failed} échec(s) — consultez les logs." : 'OCR, détection de type et classement intelligent en cours.')
                ->success()
                ->send();

            $this->form->fill([
                'type_document' => '__auto__',
                'auto_detect_type' => true,
                'auto_suggest_dossier' => true,
            ]);
        } else {
            Notification::make()->title('Aucun document importé')->danger()->send();
        }
    }

    // -------------------------------------------------------------------------
    // Header actions
    // -------------------------------------------------------------------------

    protected function getHeaderActions(): array
    {
        return [
            $this->emailImportAction(),
            $this->imapScheduleAction(),
            $this->configureScanFolderAction(),
            $this->scanFolderPreviewAction(),
            $this->scanFolderAction(),
            $this->ocrStatusAction(),
        ];
    }

    private function scanFolderPreviewAction(): Action
    {
        return Action::make('scan_folder_preview')
            ->label('Aperçu dossier scanner')
            ->icon('heroicon-o-queue-list')
            ->color('gray')
            ->extraAttributes(['class' => 'pluss-scan-folder-preview-action'])
            ->modalHeading('Panier scanner par lot')
            ->modalContent(fn () => view('filament.pages.partials.scan-folder-preview', [
                'scanFolder' => $this->scanInboxPath,
                'files' => $this->scanInboxFiles,
                'count' => $this->scanInboxCount,
            ]))
            ->modalSubmitAction(false);
    }

    private function emailImportAction(): Action
    {
        return Action::make('import_email')
            ->label('Importer depuis e-mail')
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('info')
            ->modalHeading('Importation depuis une boîte e-mail (IMAP)')
            ->modalDescription('Les pièces jointes des e-mails non lus seront importées comme documents.')
            ->form([
                Forms\Components\TextInput::make('host')
                    ->label('Serveur IMAP')
                    ->placeholder('imap.gmail.com')
                    ->required()
                    ->default(config('acquisition.imap.host')),

                Forms\Components\TextInput::make('port')
                    ->label('Port')
                    ->numeric()
                    ->default(config('acquisition.imap.port', 993))
                    ->required(),

                Forms\Components\Select::make('encryption')
                    ->label('Chiffrement')
                    ->options(['ssl' => 'SSL', 'tls' => 'TLS', '' => 'Aucun'])
                    ->default(config('acquisition.imap.encryption', 'ssl'))
                    ->required(),

                Forms\Components\TextInput::make('username')
                    ->label('Adresse e-mail')
                    ->email()
                    ->required()
                    ->default(config('acquisition.imap.username')),

                Forms\Components\TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->required(),

                Forms\Components\TextInput::make('folder')
                    ->label('Dossier IMAP')
                    ->default(config('acquisition.imap.folder', 'INBOX'))
                    ->required(),

                Forms\Components\Select::make('dossier_id')
                    ->label('Dossier GED cible')
                    ->options(fn () => $this->getActiveDossierOptions())
                    ->searchable()
                    ->nullable(),

                Forms\Components\Toggle::make('validate_cert')
                    ->label('Valider le certificat SSL')
                    ->default(false),
            ])
            ->action(function (array $data, EmailImportService $service): void {
                $result = $service->importFromMailbox([
                    'host'          => $data['host'],
                    'port'          => (int) $data['port'],
                    'encryption'    => $data['encryption'],
                    'validate_cert' => (bool) $data['validate_cert'],
                    'username'      => $data['username'],
                    'password'      => $data['password'],
                    'folder'        => $data['folder'],
                    'protocol'      => 'imap',
                ], $data['dossier_id'] ?? null);

                $count = count($result['imported']);

                if ($count > 0) {
                    Notification::make()
                        ->title("{$count} document(s) importé(s) depuis la boîte mail")
                        ->body(empty($result['errors']) ? null : implode("\n", $result['errors']))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Aucun document importé')
                        ->body(empty($result['errors'])
                            ? 'Aucun e-mail non lu avec pièce jointe.'
                            : implode("\n", $result['errors'])
                        )
                        ->warning()
                        ->send();
                }
            })
            ->visible(function (): bool {
                $user = Auth::user();

                return $user instanceof User && (
                    $user->hasRole('Super Admin')
                    || $user->hasPermissionTo('ged.documents.create')
                );
            });
    }

    private function scanFolderAction(): Action
    {
        return Action::make('process_scan_folder')
            ->label('Traiter dossier scanner')
            ->icon('heroicon-o-folder-arrow-down')
            ->color('warning')
            ->modalHeading('Dossier de numérisation')
            ->modalDescription(fn () => 'Importe tous les fichiers présents dans : **' . $this->scanInboxPath . "**\n\nDéposez vos scans dans ce dossier puis cliquez sur Importer.")
            ->form([
                Forms\Components\Select::make('dossier_id')
                    ->label('Dossier GED cible')
                    ->options(fn () => $this->getActiveDossierOptions())
                    ->searchable()
                    ->nullable(),
            ])
            ->action(function (array $data): void {
                $exitCode = Artisan::call('acquisition:process-scan-folder', [
                    '--dossier' => $data['dossier_id'] ?? null,
                    '--folder'  => $this->scanInboxPath,
                ]);

                $output = Artisan::output();

                if ($exitCode === 0) {
                    Notification::make()->title('Dossier traité')->body($output)->success()->send();
                } else {
                    Notification::make()->title('Traitement partiel')->body($output)->warning()->send();
                }
            })
            ->visible(function (): bool {
                $user = Auth::user();

                return $user instanceof User && (
                    $user->hasRole('Super Admin')
                    || $user->hasPermissionTo('ged.documents.create')
                );
            });
    }

    private function isSuperAdmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('Super Admin');
    }

    private function configureScanFolderAction(): Action
    {
        return Action::make('configure_scan_folder')
            ->label(fn () => $this->isSuperAdmin() ? 'Configurer dossier scan (serveur)' : 'Dossier scan-inbox')
            ->icon('heroicon-o-folder-open')
            ->color('gray')
            ->visible(function (): bool {
                $user = Auth::user();

                return $user instanceof User && (
                    $user->hasRole('Super Admin')
                    || $user->hasPermissionTo('ged.documents.create')
                );
            })
            ->modalHeading(fn () => $this->isSuperAdmin() ? 'Sélectionner le dossier scan-inbox (serveur)' : 'Dossier scan-inbox')
            ->modalDescription(
                fn () => $this->isSuperAdmin()
                    ? "Naviguez dans l'arborescence du **serveur** et validez le dossier où le scanner réseau dépose les fichiers. Ce chemin est enregistré pour votre compte administrateur."
                    : null
            )
            ->modalContent(function () {
                $isSuperAdmin = $this->isSuperAdmin();

                if (! $isSuperAdmin) {
                    return view('filament.pages.partials.scan-folder-cloud-hint', [
                        'scanInboxPath' => $this->scanInboxPath,
                    ]);
                }

                $this->initScanBrowserIfNeeded();
                $currentPath = $this->scanBrowserCurrentPath;
                $parent      = dirname($currentPath);

                $directories = (is_dir($currentPath) && is_readable($currentPath))
                    ? collect(File::directories($currentPath))
                        ->map(fn (string $d): array => ['name' => basename($d), 'path' => $d])
                        ->sortBy('name')
                        ->values()
                        ->all()
                    : [];

                return view('filament.pages.partials.scan-folder-browser', [
                    'currentPath' => $currentPath,
                    'directories' => $directories,
                    'canGoUp'     => is_dir($parent) && $parent !== $currentPath,
                ]);
            })
            ->modalSubmitActionLabel('Sélectionner ce dossier')
            ->modalSubmitAction(fn ($action) => $this->isSuperAdmin() ? $action : false)
            ->action(function (): void {
                $this->initScanBrowserIfNeeded();
                $path = $this->scanBrowserCurrentPath;
                $user = Auth::user();

                if (! $user instanceof User) {
                    return;
                }

                if (! is_dir($path)) {
                    Notification::make()->title('Dossier introuvable')->body("Le chemin « {$path} » n'existe pas sur le serveur.")->danger()->send();
                    return;
                }

                $prefs                    = $user->preferences ?? [];
                $prefs['scan_inbox_path'] = $path;
                $user->preferences        = $prefs;
                $user->save();

                $this->scanBrowserCurrentPath = '';

                Notification::make()
                    ->title('Dossier scan-inbox mis à jour')
                    ->body("Chemin configuré : {$path}")
                    ->success()
                    ->send();
            });
    }

    protected function initScanBrowserIfNeeded(): void
    {
        if ($this->scanBrowserCurrentPath === '') {
            $this->scanBrowserCurrentPath = $this->scanInboxPath ?: storage_path('app');
        }
    }

    public function scanBrowserNavigate(string $encodedPath): void
    {
        $path = base64_decode($encodedPath, strict: true);

        if ($path === false) {
            return;
        }

        $real = realpath($path);

        if ($real === false || ! is_dir($real) || ! is_readable($real)) {
            return;
        }

        $this->scanBrowserCurrentPath = $real;
    }

    public function scanBrowserGoUp(): void
    {
        $this->initScanBrowserIfNeeded();
        $parent = dirname($this->scanBrowserCurrentPath);

        if (is_dir($parent) && $parent !== $this->scanBrowserCurrentPath) {
            $this->scanBrowserCurrentPath = $parent;
        }
    }

    public function getAcquisitionBasketProperty(): array
    {
        $files = $this->data['files'] ?? [];
        $items = [];

        foreach ($files as $file) {
            $descriptor = $this->describePendingFile($file);

            if ($descriptor === null) {
                continue;
            }

            $detectedType = $this->resolveDetectedType(
                classifier: app(AutoClassificationService::class),
                title: pathinfo($descriptor['name'], PATHINFO_FILENAME),
                originalName: $descriptor['name'],
                selectedType: $this->data['type_document'] ?? '__auto__',
                autoDetectType: (bool) ($this->data['auto_detect_type'] ?? true),
            );

            $suggestedDossierId = ($this->data['dossier_id'] ?? null)
                ?: ((bool) ($this->data['auto_suggest_dossier'] ?? true)
                    ? $this->suggestDossierId($detectedType, $descriptor['name'])
                    : null);

            $items[] = [
                'name' => $descriptor['name'],
                'size' => $descriptor['size'],
                'size_human' => $this->formatBytes($descriptor['size']),
                'mime_type' => $descriptor['mime_type'],
                'extension' => strtoupper(pathinfo($descriptor['name'], PATHINFO_EXTENSION) ?: 'FILE'),
                'detected_type' => $detectedType,
                'target_dossier' => $suggestedDossierId ? Dossier::query()->visibleTo(Auth::user())->find($suggestedDossierId)?->libelle : null,
                'target_dossier_path' => $suggestedDossierId ? Dossier::query()->visibleTo(Auth::user())->find($suggestedDossierId)?->selectionLabel() : null,
                'target_dossier_documents' => $suggestedDossierId ? Dossier::query()->visibleTo(Auth::user())->find($suggestedDossierId)?->aggregatedDocumentsCount() : null,
                'ocr_ready' => app(OcrService::class)->isAvailableFor($descriptor['mime_type']),
            ];
        }

        return $items;
    }

    public function getAcquisitionStatsProperty(): array
    {
        return [
            'pending_ocr' => DocumentVersion::query()->where('ocr_status', 'pending')->count(),
            'processing_ocr' => DocumentVersion::query()->where('ocr_status', 'processing')->count(),
            'completed_ocr' => DocumentVersion::query()->where('ocr_status', 'completed')->count(),
            'scan_inbox' => $this->scanInboxCount,
        ];
    }

    public function getScanInboxPathProperty(): string
    {
        $user = Auth::user();
        $prefs = $user instanceof User ? ($user->preferences ?? []) : [];

        return (string) ($prefs['scan_inbox_path'] ?? config('acquisition.scan_folder'));
    }

    public function getScanInboxFilesProperty(): array
    {
        $scanFolder = $this->scanInboxPath;

        if ($scanFolder === '' || ! is_dir($scanFolder)) {
            return [];
        }

        return collect(File::files($scanFolder))
            ->sortBy(fn (\SplFileInfo $file): string => $file->getFilename())
            ->take(12)
            ->map(fn (\SplFileInfo $file): array => [
                'name' => $file->getFilename(),
                'size' => $this->formatBytes($file->getSize()),
                'extension' => strtoupper($file->getExtension() ?: 'FILE'),
                'detected_type' => $this->resolveDetectedType(
                    classifier: app(AutoClassificationService::class),
                    title: pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    originalName: $file->getFilename(),
                    selectedType: '__auto__',
                    autoDetectType: true,
                ),
            ])
            ->values()
            ->all();
    }

    public function getScanInboxCountProperty(): int
    {
        $scanFolder = $this->scanInboxPath;

        if ($scanFolder === '' || ! is_dir($scanFolder)) {
            return 0;
        }

        return count(File::files($scanFolder));
    }

    public function getScannerConnectionHintsProperty(): array
    {
        return [
            'Configurez votre scanner multifonction pour déposer automatiquement les fichiers dans le dossier ' . $this->scanInboxPath . '.',
            'Privilégiez le PDF multipage pour le scan par lot, ou JPG/PNG/TIFF pour les pièces visuelles.',
            'Utilisez ensuite le panier scanner ou le bouton “Traiter dossier scanner” pour transférer rapidement vers la GED.',
        ];
    }

    public function getSelectedDossierContextProperty(): ?array
    {
        $dossierId = $this->data['dossier_id'] ?? null;

        if (! $dossierId) {
            return null;
        }

        $dossier = Dossier::query()->visibleTo(Auth::user())->find($dossierId);

        if (! $dossier) {
            return null;
        }

        return [
            'label' => $dossier->libelle,
            'path' => $dossier->selectionLabel(),
            'type' => $dossier->type_label,
            'documents' => $dossier->aggregatedDocumentsCount(),
            'children' => $dossier->aggregatedChildrenCount(),
        ];
    }

    private function getActiveDossierOptions(): array
    {
        return Dossier::query()
            ->visibleTo(Auth::user())
            ->withHierarchyContext()
            ->where('statut', 'Actif')
            ->orderByDesc('annee_activite')
            ->orderBy('parent_id')
            ->orderBy('ordre_affichage')
            ->orderBy('libelle')
            ->get()
            ->mapWithKeys(fn (Dossier $dossier): array => [
                $dossier->id => $dossier->selectionLabel(),
            ])
            ->all();
    }

    private function describePendingFile(mixed $file): ?array
    {
        if ($file instanceof TemporaryUploadedFile) {
            return [
                'name' => $file->getClientOriginalName(),
                'size' => (int) $file->getSize(),
                'mime_type' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
            ];
        }

        if (is_string($file)) {
            $absolutePath = Storage::disk('local')->path($file);

            if (! is_file($absolutePath)) {
                return null;
            }

            return [
                'name' => basename($file),
                'size' => (int) filesize($absolutePath),
                'mime_type' => (string) (mime_content_type($absolutePath) ?: 'application/octet-stream'),
            ];
        }

        return null;
    }

    private function resolveDetectedType(
        AutoClassificationService $classifier,
        string $title,
        string $originalName,
        string $selectedType,
        bool $autoDetectType,
    ): string {
        if (! $autoDetectType && $selectedType !== '__auto__') {
            return $selectedType;
        }

        $detected = $classifier->detectType('', $title, $originalName);

        if ($selectedType !== '__auto__' && ! $autoDetectType) {
            return $selectedType;
        }

        return $detected['type'] !== 'Autre'
            ? $detected['type']
            : ($selectedType !== '__auto__' ? $selectedType : 'Document');
    }

    private function suggestDossierId(string $detectedType, string $originalName): ?int
    {
        $keywords = collect([
            $detectedType,
            pathinfo($originalName, PATHINFO_FILENAME),
            Str::contains(mb_strtolower($detectedType), 'facture') ? 'compta' : null,
            Str::contains(mb_strtolower($detectedType), 'contrat') ? 'contrat' : null,
            Str::contains(mb_strtolower($detectedType), 'courrier') ? 'courrier' : null,
            Str::contains(mb_strtolower($detectedType), 'rapport') ? 'rapport' : null,
        ])->filter()->map(fn (string $value): string => mb_strtolower($value));

        if ($keywords->isEmpty()) {
            return null;
        }

        return Dossier::query()
            ->visibleTo(Auth::user())
            ->where('statut', 'Actif')
            ->get(['id', 'libelle'])
            ->sortByDesc(function (Dossier $dossier) use ($keywords): int {
                $label = mb_strtolower((string) $dossier->libelle);

                return $keywords->sum(fn (string $keyword): int => Str::contains($label, mb_strtolower($keyword)) ? 1 : 0);
            })
            ->first(function (Dossier $dossier) use ($keywords): bool {
                $label = mb_strtolower((string) $dossier->libelle);

                return $keywords->contains(fn (string $keyword): bool => Str::contains($label, $keyword));
            })
            ?->id;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'Ko', 'Mo', 'Go'];
        $value = $bytes;
        $index = 0;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return number_format($value, $index === 0 ? 0 : 1, ',', ' ') . ' ' . $units[$index];
    }
    private function imapScheduleAction(): Action
    {
        return Action::make('imap_schedule')
            ->label('Planification IMAP')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->visible(fn () => $this->isSuperAdmin())
            ->modalHeading('Planification de l\'import IMAP')
            ->modalDescription('Active ou désactive l\'import automatique des e-mails toutes les 15 minutes via le planificateur Laravel.')
            ->form([
                Forms\Components\Toggle::make('enabled')
                    ->label('Import IMAP planifié (toutes les 15 min)')
                    ->helperText('Requiert que le serveur IMAP soit configuré (ACQUISITION_IMAP_HOST).')
                    ->default(fn () => app(\App\Services\GedSettingsService::class)->imapScheduleEnabled()),
            ])
            ->modalSubmitActionLabel('Enregistrer')
            ->action(function (array $data): void {
                app(\App\Services\GedSettingsService::class)
                    ->set('acquisition.imap_schedule_enabled', (bool) $data['enabled']);

                Notification::make()
                    ->title($data['enabled'] ? 'Import IMAP activé' : 'Import IMAP désactivé')
                    ->body($data['enabled']
                        ? 'L\'import IMAP s\'exécutera automatiquement toutes les 15 minutes.'
                        : 'L\'import IMAP planifié est suspendu.'
                    )
                    ->success()
                    ->send();
            });
    }

    private function ocrStatusAction(): Action
    {
        return Action::make('ocr_status')
            ->label('Statut OCR')
            ->icon('heroicon-o-magnifying-glass-circle')
            ->color('gray')
            ->modalHeading('Statut de l\'OCR')
            ->modalContent(fn (OcrService $ocr) => view('filament.pages.partials.ocr-status', [
                'tesseractPath'   => $ocr->findTesseract(),
                'pendingCount'    => \App\Models\DocumentVersion::where('ocr_status', 'pending')->count(),
                'processingCount' => \App\Models\DocumentVersion::where('ocr_status', 'processing')->count(),
                'completedCount'  => \App\Models\DocumentVersion::where('ocr_status', 'completed')->count(),
                'failedCount'     => \App\Models\DocumentVersion::where('ocr_status', 'failed')->count(),
            ]))
            ->modalSubmitAction(false);
    }
}
