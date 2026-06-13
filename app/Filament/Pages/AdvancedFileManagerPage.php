<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use App\Services\AutoClassificationService;
use App\Services\DocumentImportService;
use App\Services\OcrService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AdvancedFileManagerPage extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $navigationLabel = 'Gestionnaire fichiers';

    protected static ?string $title = 'Gestionnaire de fichiers avancé';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.advanced-file-manager';

    public string $search = '';

    public ?int $dossierFilter = null;

    public ?string $typeFilter = null;

    public ?string $confidentialityFilter = null;

    public ?string $lifecycleFilter = null;

    public ?string $ocrStatusFilter = null;

    public ?string $dossierStateFilter = null;

    public ?string $courrierLinkFilter = null;

    public string $quickView = 'all';

    public ?int $dropTargetYear = null;

    public ?int $dropTargetMonth = null;

    public ?int $moveTargetDossierId = null;

    public int $perPage = 10;

    public int $currentPage = 1;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public string $viewMode = 'list';

    /** @var array<int, int> */
    public array $selectedDocumentIds = [];

    /** @var array<int, int> */
    public array $clipboardDocumentIds = [];

    public string $clipboardMode = 'copy';

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploadFiles = [];

    public ?int $uploadDossierId = null;

    public string $uploadTypeDocument = 'Document';

    public string $uploadConfidentiality = 'Standard';

    public bool $uploadAutoDetectType = true;

    public bool $uploadAutoSuggestDossier = true;

    public static function getNavigationGroup(): ?string
    {
        return 'GED';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('ged.documents.view')
        );
    }

    public function mount(): void
    {
        $this->uploadDossierId = null;
        $this->moveTargetDossierId = null;
    }

    public function updatedSearch(): void
    {
        $this->selectedDocumentIds = [];
        $this->currentPage = 1;
    }

    public function updatedDossierFilter(): void
    {
        $this->currentPage = 1;

        if ($this->moveTargetDossierId === null && $this->dossierFilter !== null) {
            $this->moveTargetDossierId = $this->dossierFilter;
        }
    }

    public function updatedTypeFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedConfidentialityFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedLifecycleFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedOcrStatusFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedDossierStateFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedCourrierLinkFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, [10, 20, 50, 100], true) ? $this->perPage : 10;
        $this->currentPage = 1;
    }

    public function getDossiersProperty(): Collection
    {
        return Dossier::query()
            ->visibleTo(Auth::user())
            ->withHierarchyContext()
            ->orderByDesc('annee_activite')
            ->orderBy('parent_id')
            ->orderBy('ordre_affichage')
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'parent_id', 'annee_activite', 'type_dossier'])
            ->map(function (Dossier $dossier): Dossier {
                $dossier->setAttribute('display_label', $dossier->selectionLabel());

                return $dossier;
            });
    }

    public function getAvailableTypesProperty(): Collection
    {
        return Document::query()
            ->visibleTo(Auth::user())
            ->select('type_document')
            ->whereNotNull('type_document')
            ->distinct()
            ->orderBy('type_document')
            ->pluck('type_document');
    }

    public function getAvailableConfidentialityLevelsProperty(): Collection
    {
        return Document::query()
            ->visibleTo(Auth::user())
            ->select('confidentiality_level')
            ->whereNotNull('confidentiality_level')
            ->distinct()
            ->orderBy('confidentiality_level')
            ->pluck('confidentiality_level');
    }

    public function getAvailableLifecycleStatesProperty(): Collection
    {
        return Document::query()
            ->visibleTo(Auth::user())
            ->select('etat_cycle_vie')
            ->whereNotNull('etat_cycle_vie')
            ->distinct()
            ->orderBy('etat_cycle_vie')
            ->pluck('etat_cycle_vie');
    }

    public function getAvailableOcrStatusesProperty(): Collection
    {
        return collect([
            'pending' => 'OCR en attente',
            'processing' => 'OCR en cours',
            'completed' => 'OCR indexe',
            'failed' => 'OCR en echec',
            'unavailable' => 'OCR indisponible',
        ]);
    }

    public function getAvailableYearsProperty(): Collection
    {
        return Document::query()
            ->visibleTo(Auth::user())
            ->selectRaw('YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();
    }

    public function getAvailableMonthsProperty(): Collection
    {
        return collect([
            1 => 'Janvier',
            2 => 'Fevrier',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Aout',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Decembre',
        ]);
    }

    public function getDropTargetDossiersProperty(): Collection
    {
        $dossiers = Dossier::query()
            ->visibleTo(Auth::user())
            ->withHierarchyContext()
            ->orderByDesc('annee_activite')
            ->orderBy('parent_id')
            ->orderBy('ordre_affichage')
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'parent_id', 'annee_activite', 'type_dossier'])
            ->map(function (Dossier $dossier): Dossier {
                $dossier->setAttribute('display_label', $dossier->selectionLabel());

                return $dossier;
            });

        if (! $this->dropTargetYear && ! $this->dropTargetMonth) {
            return $dossiers;
        }

        $countsByDossier = Document::query()
            ->visibleTo(Auth::user())
            ->selectRaw('dossier_id, COUNT(*) as total')
            ->whereNotNull('dossier_id')
            ->when($this->dropTargetYear, fn (Builder $q): Builder => $q->whereYear('created_at', $this->dropTargetYear))
            ->when($this->dropTargetMonth, fn (Builder $q): Builder => $q->whereMonth('created_at', $this->dropTargetMonth))
            ->groupBy('dossier_id')
            ->pluck('total', 'dossier_id');

        return $dossiers
            ->filter(fn (Dossier $dossier): bool => $countsByDossier->has($dossier->id))
            ->map(function (Dossier $dossier) use ($countsByDossier): Dossier {
                $dossier->setAttribute('documents_count', (int) $countsByDossier->get($dossier->id));
                $dossier->setAttribute('display_label', $dossier->selectionLabel());

                return $dossier;
            })
            ->values();
    }

    public function getDocumentsQueryProperty(): Builder
    {
        $query = Document::query()
            ->visibleTo(Auth::user())
            ->with(['dossier.parent.parent.parent.parent', 'auteur:id,name', 'currentVersion.media'])
            ->when(filled($this->search), function (Builder $q): void {
                $term = trim($this->search);
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('reference_doc', 'like', '%' . $term . '%')
                        ->orWhere('titre', 'like', '%' . $term . '%')
                        ->orWhere('keywords', 'like', '%' . $term . '%')
                        ->orWhereHas('dossier', fn (Builder $dossierQuery): Builder => $dossierQuery->where('libelle', 'like', '%' . $term . '%'))
                        ->orWhereHas('currentVersion', fn (Builder $versionQuery): Builder => $versionQuery->where('ocr_text', 'like', '%' . $term . '%'));
                });
            })
            ->when($this->dossierFilter, fn (Builder $q): Builder => $q->where('dossier_id', $this->dossierFilter))
            ->when(filled($this->typeFilter), fn (Builder $q): Builder => $q->where('type_document', $this->typeFilter))
            ->when(filled($this->confidentialityFilter), fn (Builder $q): Builder => $q->where('confidentiality_level', $this->confidentialityFilter))
            ->when(filled($this->lifecycleFilter), fn (Builder $q): Builder => $q->where('etat_cycle_vie', $this->lifecycleFilter))
            ->when(filled($this->ocrStatusFilter), fn (Builder $q): Builder => $q->whereHas('currentVersion', fn (Builder $versionQuery): Builder => $versionQuery->where('ocr_status', $this->ocrStatusFilter)))
            ->when($this->dossierStateFilter === 'with', fn (Builder $q): Builder => $q->whereNotNull('dossier_id'))
            ->when($this->dossierStateFilter === 'without', fn (Builder $q): Builder => $q->whereNull('dossier_id'))
            ->when($this->courrierLinkFilter === 'with', fn (Builder $q): Builder => $q->whereNotNull('courrier_id'))
            ->when($this->courrierLinkFilter === 'without', fn (Builder $q): Builder => $q->whereNull('courrier_id'));

        $query = match ($this->quickView) {
            'unclassified' => $query->whereNull('dossier_id'),
            'ocr_pending' => $query->whereHas('currentVersion', fn (Builder $versionQuery): Builder => $versionQuery->whereIn('ocr_status', ['pending', 'processing'])),
            'ocr_ready' => $query->whereHas('currentVersion', fn (Builder $versionQuery): Builder => $versionQuery->where('ocr_status', 'completed')),
            'linked_to_courrier' => $query->whereNotNull('courrier_id'),
            'sensitive' => $query->whereIn('confidentiality_level', ['Confidentiel', 'Personnel']),
            default => $query,
        };

        if (in_array($this->sortBy, ['created_at', 'titre', 'reference_doc', 'type_document'], true)) {
            $query->orderBy($this->sortBy, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query;
    }

    public function getDocumentsTotalProperty(): int
    {
        return (clone $this->documentsQuery)->count();
    }

    public function getLastPageProperty(): int
    {
        return max(1, (int) ceil($this->documentsTotal / max(1, $this->perPage)));
    }

    public function getDocumentsProperty(): Collection
    {
        if ($this->currentPage > $this->lastPage) {
            $this->currentPage = $this->lastPage;
        }

        return (clone $this->documentsQuery)
            ->forPage($this->currentPage, $this->perPage)
            ->get();
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['list', 'gallery'], true)) {
            return;
        }

        $this->viewMode = $mode;
    }

    public function setSorting(string $sortBy, string $direction = 'desc'): void
    {
        if (! in_array($sortBy, ['created_at', 'titre', 'reference_doc', 'type_document'], true)) {
            return;
        }

        $this->sortBy = $sortBy;
        $this->sortDirection = $direction === 'asc' ? 'asc' : 'desc';
        $this->currentPage = 1;
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = max(1, min($page, $this->lastPage));
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->lastPage) {
            $this->currentPage++;
        }
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->dossierFilter = null;
        $this->typeFilter = null;
        $this->confidentialityFilter = null;
        $this->lifecycleFilter = null;
        $this->ocrStatusFilter = null;
        $this->dossierStateFilter = null;
        $this->courrierLinkFilter = null;
        $this->quickView = 'all';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage = 10;
        $this->currentPage = 1;
        $this->clearSelection();
    }

    public function setQuickView(string $quickView): void
    {
        if (! in_array($quickView, ['all', 'unclassified', 'ocr_pending', 'ocr_ready', 'linked_to_courrier', 'sensitive'], true)) {
            return;
        }

        $this->quickView = $quickView;
        $this->currentPage = 1;
    }

    public function toggleSelection(int $documentId): void
    {
        if (in_array($documentId, $this->selectedDocumentIds, true)) {
            $this->selectedDocumentIds = array_values(array_filter(
                $this->selectedDocumentIds,
                fn (int $id): bool => $id !== $documentId,
            ));

            return;
        }

        $this->selectedDocumentIds[] = $documentId;
    }

    public function clearSelection(): void
    {
        $this->selectedDocumentIds = [];
    }

    public function moveSelectionToTarget(): void
    {
        if (! $this->moveTargetDossierId) {
            Notification::make()
                ->title('Destination requise')
                ->body('Choisissez un dossier cible explicite avant de déplacer la sélection.')
                ->warning()
                ->send();

            return;
        }

        $this->moveDocumentsToDossier($this->moveTargetDossierId, $this->selectedDocumentIds);
    }

    /**
     * @param array<int, int> $documentIds
     */
    public function moveDocumentsToDossier(int $dossierId, array $documentIds = []): void
    {
        if (! Gate::allows('ged.documents.update')) {
            Notification::make()
                ->title('Action non autorisée')
                ->body('Vous n\'avez pas la permission de déplacement.')
                ->danger()
                ->send();

            return;
        }

        if (! Dossier::query()->visibleTo(Auth::user())->whereKey($dossierId)->exists()) {
            Notification::make()
                ->title('Dossier introuvable')
                ->warning()
                ->send();

            return;
        }

        $ids = array_values(array_unique(array_map('intval', $documentIds)));

        if ($ids === []) {
            $ids = $this->selectedDocumentIds;
        }

        if ($ids === []) {
            return;
        }

        $moved = Document::query()
            ->visibleTo(Auth::user())
            ->whereIn('id', $ids)
            ->update(['dossier_id' => $dossierId]);

        $this->selectedDocumentIds = [];

        Notification::make()
            ->title('Déplacement glisser-déposer')
            ->body("{$moved} document(s) déplacé(s).")
            ->success()
            ->send();
    }

    public function copySelection(): void
    {
        if ($this->selectedDocumentIds === []) {
            return;
        }

        $this->clipboardMode = 'copy';
        $this->clipboardDocumentIds = $this->selectedDocumentIds;

        Notification::make()
            ->title('Presse-papiers')
            ->body(count($this->clipboardDocumentIds) . ' document(s) copiés.')
            ->success()
            ->send();
    }

    public function cutSelection(): void
    {
        if ($this->selectedDocumentIds === []) {
            return;
        }

        $this->clipboardMode = 'cut';
        $this->clipboardDocumentIds = $this->selectedDocumentIds;

        Notification::make()
            ->title('Presse-papiers')
            ->body(count($this->clipboardDocumentIds) . ' document(s) prêts à être déplacés.')
            ->warning()
            ->send();
    }

    public function pasteClipboard(): void
    {
        if ($this->clipboardDocumentIds === []) {
            return;
        }

        if (! $this->dossierFilter) {
            Notification::make()
                ->title('Cible manquante')
                ->body('Choisissez un dossier de destination dans le filtre Dossier avant de coller.')
                ->warning()
                ->send();

            return;
        }

        if ($this->clipboardMode === 'cut') {
            $moved = Document::query()
                ->visibleTo(Auth::user())
                ->whereIn('id', $this->clipboardDocumentIds)
                ->update(['dossier_id' => $this->dossierFilter]);

            $this->clipboardDocumentIds = [];
            $this->selectedDocumentIds = [];

            Notification::make()
                ->title('Déplacement effectué')
                ->body("{$moved} document(s) déplacé(s).")
                ->success()
                ->send();

            return;
        }

        $importer = app(DocumentImportService::class);
        $copied = 0;

        $documents = Document::query()
            ->visibleTo(Auth::user())
            ->with(['currentVersion.media'])
            ->whereIn('id', $this->clipboardDocumentIds)
            ->get();

        foreach ($documents as $document) {
            $media = $document->currentVersion?->media;

            if (! $media || ! is_file($media->getPath())) {
                continue;
            }

            $importer->import($media->getPath(), [
                'titre' => ($document->titre ?? 'Document') . ' (copie)',
                'type_document' => $document->type_document ?? 'Document',
                'dossier_id' => $this->dossierFilter,
                'confidentiality_level' => $document->confidentiality_level ?? 'Standard',
                'source' => 'upload',
            ]);

            $copied++;
        }

        Notification::make()
            ->title('Copie effectuée')
            ->body("{$copied} document(s) copié(s).")
            ->success()
            ->send();
    }

    public function deleteSelection(): void
    {
        if (! Gate::allows('delete', Document::class)) {
            Notification::make()
                ->title('Action non autorisée')
                ->body('Vous n\'avez pas la permission de suppression.')
                ->danger()
                ->send();

            return;
        }

        if ($this->selectedDocumentIds === []) {
            return;
        }

        $count = 0;

        foreach (Document::query()->visibleTo(Auth::user())->whereIn('id', $this->selectedDocumentIds)->get() as $document) {
            $document->delete();
            $count++;
        }

        $this->selectedDocumentIds = [];

        Notification::make()
            ->title('Suppression effectuée')
            ->body("{$count} document(s) supprimé(s).")
            ->success()
            ->send();
    }

    public function uploadDroppedFiles(DocumentImportService $importer): void
    {
        if (! Gate::allows('create', Document::class)) {
            Notification::make()
                ->title('Action non autorisée')
                ->body('Vous n\'avez pas la permission d\'ajout de documents.')
                ->danger()
                ->send();

            return;
        }

        if ($this->uploadFiles === []) {
            return;
        }

        $ok = 0;
        $failed = 0;

        foreach ($this->uploadFiles as $file) {
            try {
                if (! $file instanceof TemporaryUploadedFile) {
                    $failed++;
                    continue;
                }

                $importer->import($file, [
                    'titre' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'type_document' => $this->resolveUploadDetectedType($file),
                    'dossier_id' => $this->resolveUploadTargetDossierId($file),
                    'confidentiality_level' => $this->uploadConfidentiality,
                    'source' => 'upload',
                ]);

                $ok++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        $this->uploadFiles = [];

        Notification::make()
            ->title('Import terminé')
            ->body("{$ok} succès, {$failed} échec(s).")
            ->success()
            ->send();
    }

    public function getSelectedDocumentPreviewProperty(): ?array
    {
        $documentId = $this->selectedDocumentIds[0] ?? null;

        if (! $documentId) {
            return null;
        }

        $document = Document::query()
            ->visibleTo(Auth::user())
            ->with(['dossier.parent.parent.parent.parent', 'auteur:id,name', 'currentVersion.media'])
            ->find($documentId);

        if (! $document) {
            return null;
        }

        $media = $document->currentVersion?->media;
        $mime = $media?->mime_type;
        $isImage = is_string($mime) && str_starts_with($mime, 'image/');

        return [
            'id' => $document->id,
            'titre' => $document->titre,
            'reference' => $document->reference_doc,
            'type' => $document->type_document,
            'confidentiality' => $document->confidentiality_level,
            'lifecycle' => $document->etat_cycle_vie,
            'dossier' => $document->dossier?->libelle,
            'dossier_path' => $document->dossier?->selectionLabel(),
            'auteur' => $document->auteur?->name,
            'created_at' => $document->created_at?->format('d/m/Y H:i'),
            'ocr_status' => $document->currentVersion?->ocrStatusLabel() ?? 'Inconnu',
            'has_ocr' => (bool) $document->currentVersion?->hasOcrText(),
            'ocr_text_excerpt' => $document->currentVersion?->hasOcrText()
                ? mb_substr((string) $document->currentVersion->ocr_text, 0, 500)
                : null,
            'media_url' => $media?->getUrl(),
            'is_image' => $isImage,
            'extension' => strtoupper(pathinfo((string) $media?->file_name, PATHINFO_EXTENSION) ?: 'FILE'),
            'view_url' => route('filament.admin.resources.documents.view', $document),
            'acquisition_url' => AcquisitionPage::getUrl(array_filter([
                'dossier_id' => $document->dossier_id ? (string) $document->dossier_id : null,
            ])),
        ];
    }

    public function getUploadPreviewItemsProperty(): array
    {
        $items = [];

        foreach ($this->uploadFiles as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            $detectedType = $this->resolveUploadDetectedType($file);
            $dossierId = $this->resolveUploadTargetDossierId($file, $detectedType);
            $dossier = $dossierId
                ? Dossier::query()->visibleTo(Auth::user())->withHierarchyContext()->find($dossierId)
                : null;

            $items[] = [
                'name' => $file->getClientOriginalName(),
                'size_human' => $this->formatBytes((int) $file->getSize()),
                'mime_type' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
                'detected_type' => $detectedType,
                'ocr_ready' => app(OcrService::class)->isAvailableFor((string) ($file->getMimeType() ?? 'application/octet-stream')),
                'target_dossier' => $dossier?->libelle,
                'target_dossier_path' => $dossier?->selectionLabel(),
            ];
        }

        return $items;
    }

    public function getUploadTargetContextProperty(): ?array
    {
        if (! $this->uploadDossierId) {
            return null;
        }

        $dossier = Dossier::query()->visibleTo(Auth::user())->withHierarchyContext()->find($this->uploadDossierId);

        if (! $dossier) {
            return null;
        }

        return [
            'path' => $dossier->selectionLabel(),
            'documents' => $dossier->aggregatedDocumentsCount(),
            'children' => $dossier->aggregatedChildrenCount(),
        ];
    }

    public function getUploadAcquisitionUrlProperty(): string
    {
        return AcquisitionPage::getUrl(array_filter([
            'dossier_id' => $this->uploadDossierId ? (string) $this->uploadDossierId : null,
        ]));
    }

    private function resolveUploadDetectedType(TemporaryUploadedFile $file): string
    {
        if (! $this->uploadAutoDetectType) {
            return $this->uploadTypeDocument;
        }

        $detected = app(AutoClassificationService::class)->detectType(
            '',
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            $file->getClientOriginalName(),
        );

        return $detected['type'] !== 'Autre'
            ? $detected['type']
            : ($this->uploadTypeDocument ?: 'Document');
    }

    private function resolveUploadTargetDossierId(TemporaryUploadedFile $file, ?string $detectedType = null): ?int
    {
        if ($this->uploadDossierId) {
            return $this->uploadDossierId;
        }

        if (! $this->uploadAutoSuggestDossier) {
            return null;
        }

        $type = $detectedType ?: $this->resolveUploadDetectedType($file);

        return $this->suggestUploadDossierId($type, $file->getClientOriginalName());
    }

    private function suggestUploadDossierId(string $detectedType, string $originalName): ?int
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

                return $keywords->sum(fn (string $keyword): int => Str::contains($label, $keyword) ? 1 : 0);
            })
            ->first(function (Dossier $dossier) use ($keywords): bool {
                $label = mb_strtolower((string) $dossier->libelle);

                return $keywords->contains(fn (string $keyword): bool => Str::contains($label, $keyword));
            })
            ?->id;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' octets';
        }

        $units = ['Ko', 'Mo', 'Go', 'To'];
        $value = $bytes / 1024;

        foreach ($units as $index => $unit) {
            $isLastUnit = $index === array_key_last($units);

            if ($value < 1024 || $isLastUnit) {
                return number_format($value, $value >= 10 ? 0 : 1, ',', ' ').' '.$unit;
            }

            $value /= 1024;
        }

        return $bytes.' octets';
    }
}
