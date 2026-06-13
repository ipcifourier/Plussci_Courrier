<?php

namespace App\Services;

use OpenAI;
use Throwable;

/**
 * Service IA pour les courriers.
 * Deux fonctionnalités :
 *  1. generateResume()    — génère un résumé depuis les champs du formulaire
 *  2. extractFromText()   — extrait des métadonnées structurées depuis le texte OCR d'un document
 */
class AiCourrierService
{
    private function client(): \OpenAI\Client
    {
        $key = config('services.openai.key');

        if (blank($key)) {
            throw new \RuntimeException('La clé API OpenAI (OPENAI_API_KEY) n\'est pas configurée.');
        }

        return OpenAI::client($key);
    }

    /**
     * Exécute un appel API avec retry automatique sur les erreurs de rate limit.
     * Backoff exponentiel : 2s, 4s (3 tentatives au total).
     */
    private function callWithRetry(callable $fn, int $maxAttempts = 3): mixed
    {
        $attempt = 0;

        while (true) {
            try {
                return $fn();
            } catch (Throwable $e) {
                $attempt++;

                $isRateLimit = str_contains(strtolower($e->getMessage()), 'rate limit')
                            || str_contains($e->getMessage(), '429');

                if (! $isRateLimit || $attempt >= $maxAttempts) {
                    throw $e;
                }

                // Attente exponentielle avant de réessayer (2s, 4s)
                sleep(2 ** $attempt);
            }
        }
    }

    // ─── 1. Génération de résumé ─────────────────────────────────────────────

    /**
     * Génère un résumé en 2-4 phrases à partir des champs du formulaire.
     */
    public function generateResume(
        string $objet,
        string $type = '',
        ?string $nature = null,
        ?string $priorite = null,
        ?string $motsCles = null,
    ): string {
        $lines = ["- Type : {$type}"];

        if ($nature) {
            $lines[] = "- Nature : {$nature}";
        }

        $lines[] = "- Objet : {$objet}";

        if ($priorite) {
            $lines[] = "- Priorité : {$priorite}";
        }

        if ($motsCles) {
            $lines[] = "- Mots-clés : {$motsCles}";
        }

        $context = implode("\n", $lines);

        $prompt = <<<PROMPT
Tu es un assistant expert en gestion documentaire administrative.
À partir des informations suivantes sur un courrier, rédige un résumé concis et professionnel en 2 à 4 phrases en français.
Ne commence pas par "Ce courrier" ou "Ce document". Va droit au but.

{$context}

Réponds uniquement avec le résumé, sans titre ni introduction.
PROMPT;

        $response = $this->callWithRetry(fn () => $this->client()->chat()->create([
            'model'       => 'gpt-4o-mini',
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'max_tokens'  => 250,
            'temperature' => 0.3,
        ]));

        return trim($response->choices[0]->message->content ?? '');
    }

    // ─── 2. Extraction structurée depuis texte OCR ───────────────────────────

    /**
     * Analyse le texte extrait d'un document et retourne les métadonnées structurées.
     *
     * @return array{
     *   objet: string|null,
     *   expediteur: string|null,
     *   reference_externe: string|null,
     *   date_document: string|null,
     *   nature: string|null,
     *   mots_cles: string|null,
     *   priorite: string|null,
     *   resume: string|null,
     * }
     */
    public function extractFromText(string $text, string $fileName = ''): array
    {
        // Limite à 8 000 caractères pour ne pas dépasser le contexte et maîtriser les coûts
        $text = mb_substr($text, 0, 8000);

        $prompt = <<<PROMPT
Tu es un assistant expert en gestion de courriers et documents administratifs.
Analyse le texte ci-dessous et extrais les informations structurées demandées.

Réponds UNIQUEMENT en JSON valide, avec exactement ces champs (null si non trouvé) :
{
  "objet": "Objet ou sujet principal du document",
  "expediteur": "Nom de l'expéditeur ou de l'organisation émettrice",
  "reference_externe": "Référence ou numéro figurant dans le document (ex: REF-2026-001)",
  "date_document": "Date du document au format YYYY-MM-DD",
  "nature": "Nature parmi : Lettre, Note de service, Circulaire, Décision, Rapport, Facture, Demande, Autre",
  "mots_cles": "3 à 8 mots-clés séparés par des virgules",
  "priorite": "Normale ou Urgente selon l'urgence détectée dans le texte",
  "resume": "Résumé concis et professionnel en 2 à 4 phrases en français"
}

Texte du document :
{$text}
PROMPT;

        try {
            $response = $this->callWithRetry(fn () => $this->client()->chat()->create([
                'model'           => 'gpt-4o-mini',
                'messages'        => [['role' => 'user', 'content' => $prompt]],
                'max_tokens'      => 600,
                'temperature'     => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]));

            $json = trim($response->choices[0]->message->content ?? '{}');

            return json_decode($json, true) ?? [];
        } catch (Throwable $e) {
            throw new \RuntimeException('Erreur lors de l\'analyse IA : ' . $e->getMessage(), 0, $e);
        }
    }
}
