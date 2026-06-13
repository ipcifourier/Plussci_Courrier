<?php

namespace App\Filament\Resources\Courriers\Pages;

use App\Filament\Resources\Courriers\CourrierResource;
use App\Services\AiCourrierService;
use App\Services\OcrService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCourrier extends EditRecord
{
    protected static string $resource = CourrierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ─── Analyse IA des documents joints ──────────────────────────
            Action::make('ai_analyse_documents')
                ->label('🔍 Analyser les documents avec l\'IA')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Analyse IA des pièces jointes')
                ->modalDescription('L\'IA va extraire le texte de la première pièce jointe et pré-remplir automatiquement les champs du courrier (objet, résumé, mots-clés, nature, priorité). Les champs déjà remplis seront remplacés.')
                ->modalSubmitActionLabel('Lancer l\'analyse')
                ->action(function () {
                    /** @var \App\Models\Courrier $record */
                    $record = $this->record;

                    $media = $record->getMedia('pieces_jointes')->first();

                    if (! $media) {
                        Notification::make()
                            ->warning()
                            ->title('Aucun document joint')
                            ->body('Ajoutez au moins une pièce jointe avant de lancer l\'analyse.')
                            ->send();

                        return;
                    }

                    $filePath = $media->getPath();
                    $mimeType = $media->mime_type ?? 'application/octet-stream';

                    // Extraction du texte
                    $ocr    = app(OcrService::class);
                    $result = $ocr->extract($filePath, $mimeType);

                    if (($result['status'] ?? 'failed') === 'failed' || blank($result['text'] ?? null)) {
                        Notification::make()
                            ->danger()
                            ->title('Extraction impossible')
                            ->body($result['error'] ?? 'Le texte n\'a pas pu être extrait de ce document.')
                            ->send();

                        return;
                    }

                    // Analyse IA
                    try {
                        $ai        = app(AiCourrierService::class);
                        $extracted = $ai->extractFromText($result['text'], $media->file_name ?? '');
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Erreur IA')
                            ->body($e->getMessage())
                            ->send();

                        return;
                    }

                    if (empty($extracted)) {
                        Notification::make()
                            ->danger()
                            ->title('Analyse échouée')
                            ->body('L\'IA n\'a pas pu retourner de résultats structurés.')
                            ->send();

                        return;
                    }

                    // Mise à jour du modèle en base
                    $updates = array_filter([
                        'objet'           => $extracted['objet'] ?? null,
                        'resume'          => $extracted['resume'] ?? null,
                        'mots_cles'       => $extracted['mots_cles'] ?? null,
                        'nature_courrier' => $this->mapNature($extracted['nature'] ?? null),
                        'priorite'        => in_array($extracted['priorite'] ?? '', ['Normale', 'Urgente'])
                                                ? $extracted['priorite']
                                                : null,
                    ]);

                    if (! empty($updates)) {
                        $record->fill($updates)->save();
                    }

                    // Pré-remplir aussi le formulaire Livewire (sans rechargement)
                    $this->form->fill(array_merge($this->form->getState(), $updates));

                    $fields = implode(', ', array_keys($updates));

                    Notification::make()
                        ->success()
                        ->title('Analyse terminée')
                        ->body("Champs mis à jour : {$fields}.")
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    /**
     * Normalise la nature extraite vers les valeurs acceptées par le formulaire.
     */
    private function mapNature(?string $nature): ?string
    {
        if (blank($nature)) {
            return null;
        }

        $map = [
            'lettre'          => 'Lettre',
            'note de service' => 'Note de service',
            'circulaire'      => 'Circulaire',
            'décision'        => 'Décision',
            'decision'        => 'Décision',
            'rapport'         => 'Rapport',
            'facture'         => 'Facture',
            'demande'         => 'Demande',
        ];

        return $map[mb_strtolower(trim($nature))] ?? 'Autre';
    }
}

