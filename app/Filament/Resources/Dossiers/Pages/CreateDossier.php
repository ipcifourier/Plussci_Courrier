<?php

namespace App\Filament\Resources\Dossiers\Pages;

use App\Filament\Resources\Dossiers\DossierResource;
use App\Models\Dossier;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDossier extends CreateRecord
{
    protected static string $resource = DossierResource::class;

    public function mount(): void
    {
        parent::mount();

        $prefilledParentId = request()->integer('parent_id');

        if (! $prefilledParentId) {
            return;
        }

        $parent = Dossier::query()->visibleTo(Auth::user())->find($prefilledParentId);

        if (! $parent) {
            return;
        }

        $prefill = array_filter([
            'parent_id' => $parent->id,
            'annee_activite' => request()->integer('annee_activite') ?: $parent->annee_activite,
            'type_dossier' => request()->query('type_dossier') ?: ($parent->type_dossier === Dossier::TYPE_YEAR ? Dossier::TYPE_CATEGORY : Dossier::TYPE_SUBCATEGORY),
            'ordre_affichage' => request()->integer('ordre_affichage') ?: (((int) Dossier::query()->visibleTo(Auth::user())->where('parent_id', $parent->id)->max('ordre_affichage')) + 10),
            'owner_id' => $parent->owner_id,
            'confidentialite' => $parent->confidentialite,
            'statut' => 'Actif',
        ], fn ($value) => $value !== null && $value !== '');

        $this->form->fill([
            ...($this->data ?? []),
            ...$prefill,
        ]);
    }
}
