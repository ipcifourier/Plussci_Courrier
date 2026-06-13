<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Dossier;
use App\Services\DocumentReferenceService;
use App\Services\DocumentVersioningService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    public function mount(): void
    {
        parent::mount();

        $dossierId = request()->integer('dossier_id');

        if (! $dossierId) {
            return;
        }

        $dossier = Dossier::query()->visibleTo(Auth::user())->find($dossierId);

        if (! $dossier) {
            return;
        }

        $this->form->fill([
            ...($this->data ?? []),
            'dossier_id' => $dossier->id,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(DocumentReferenceService::class)->ensureReference($data);
    }

    /**
     * After the document record is created, create v1.0 (and v1.1, v1.2…)
     * for every file that was uploaded via the SpatieMediaLibraryFileUpload field.
     */
    protected function afterCreate(): void
    {
        app(DocumentVersioningService::class)
            ->syncUnversionedMedia($this->getRecord());
    }
}
