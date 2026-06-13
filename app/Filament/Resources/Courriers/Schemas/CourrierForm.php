<?php

namespace App\Filament\Resources\Courriers\Schemas;

use App\Services\AiCourrierService;
use Filament\Actions\Action as FilamentAction;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Auth;
class CourrierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                //
                
                 Forms\Components\Select::make('type')
                ->options([
                    'Entrant' => 'Entrant',
                    'Sortant' => 'Sortant',
                ])
                ->required()
                ->native(false)
                ->live()
                ->columnSpan(1),

            Forms\Components\Select::make('canal')
                ->label('Canal')
                ->options([
                    'Physique' => 'Physique',
                    'Email'    => 'Email',
                    'Portail'  => 'Portail',
                    'Fax'      => 'Fax',
                ])
                ->default('Physique')
                ->required()
                ->native(false)
                ->live()
                ->columnSpan(1),

            Forms\Components\Select::make('nature_courrier')
                ->label('Nature du courrier')
                ->options([
                    'Lettre'          => 'Lettre',
                    'Note de service' => 'Note de service',
                    'Circulaire'      => 'Circulaire',
                    'Décision'        => 'Décision',
                    'Rapport'         => 'Rapport',
                    'Facture'         => 'Facture',
                    'Demande'         => 'Demande',
                    'Autre'           => 'Autre',
                ])
                ->nullable()
                ->native(false)
                ->columnSpan(1),

            // Référence unique (générée automatiquement à la création)
            Forms\Components\TextInput::make('reference')
                ->label('Référence')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->disabledOn('edit') // Optionnel : empêcher modification après création
                ->columnSpan(1),

            // Date de réception/envoi
            Forms\Components\DatePicker::make('date_reception_envoi')
                ->label('Date de réception/envoi')
                ->required()
                ->default(now())
                ->columnSpan(1),

            Forms\Components\DatePicker::make('delai_reponse')
                ->label('Délai de réponse')
                ->native(false)
                ->columnSpan(1),

            Forms\Components\Toggle::make('accuse_reception')
                ->label('Accusé de réception')
                ->visible(fn ($get): bool => $get('type') === 'Entrant')
                ->live()
                ->columnSpan(1),

            Forms\Components\DateTimePicker::make('date_accuse')
                ->label('Date de l\'accusé')
                ->visible(fn ($get): bool => $get('type') === 'Entrant' && (bool) $get('accuse_reception'))
                ->columnSpan(1),

            // Objet
            Forms\Components\TextInput::make('objet')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            // Résumé (textarea)
            Forms\Components\Textarea::make('resume')
                ->rows(3)
                ->columnSpanFull(),

            // ─── Bouton IA : Générer résumé ────────────────────────────────
            SchemaActions::make([
                FilamentAction::make('ai_generate_resume')
                    ->label('✨ Générer résumé avec l\'IA')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->size(Size::Small)
                    ->requiresConfirmation(false)
                    ->action(function (Get $get, Set $set) {
                        $objet = trim((string) $get('objet'));

                        if (blank($objet)) {
                            Notification::make()
                                ->warning()
                                ->title('Champ manquant')
                                ->body('Veuillez renseigner l\'objet avant de générer un résumé.')
                                ->send();

                            return;
                        }

                        try {
                            $service = app(AiCourrierService::class);
                            $resume  = $service->generateResume(
                                objet:    $objet,
                                type:     (string) ($get('type') ?? ''),
                                nature:   $get('nature_courrier'),
                                priorite: $get('priorite'),
                                motsCles: $get('mots_cles'),
                            );

                            $set('resume', $resume);

                            Notification::make()
                                ->success()
                                ->title('Résumé généré')
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Erreur IA')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])->columnSpanFull(),

            Forms\Components\TextInput::make('mots_cles')
                ->label('Mots-clés (indexation)')
                ->placeholder('budget, marché, décret — séparés par des virgules')
                ->helperText('Séparez par des virgules pour faciliter la recherche.')
                ->columnSpanFull(),

            // Priorité
            Forms\Components\Select::make('priorite')
                ->options([
                    'Normale' => 'Normale',
                    'Urgente' => 'Urgente',
                ])
                ->default('Normale')
                ->required()
                ->native(false)
                ->columnSpan(1),

            // Statut
            Forms\Components\Select::make('statut')
                ->options([
                    'Nouveau' => 'Nouveau',
                    'En cours' => 'En cours',
                    'Traité' => 'Traité',
                    'Archivé' => 'Archivé',
                ])
                ->default('Nouveau')
                ->required()
                ->native(false)
                ->columnSpan(1),

            // Niveau de confidentialité
            Forms\Components\Select::make('niveau_confidentialite')
                ->options([
                    'Standard' => 'Standard',
                    'Confidentiel' => 'Confidentiel',
                    'Personnel' => 'Personnel',
                ])
                ->default('Standard')
                ->required()
                ->native(false)
                ->columnSpan(1),

            // Correspondant
            Forms\Components\Select::make('correspondant_id')
                ->relationship('correspondant', 'nom_structure')
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    // Formulaire pour créer un correspondant à la volée (optionnel)
                    Forms\Components\TextInput::make('nom_structure')->required(),
                    Forms\Components\TextInput::make('nom_contact'),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\TextInput::make('telephone'),
                    Forms\Components\TextInput::make('adresse'),
                ])
                ->columnSpan(1),

            // Utilisateur initiateur (par défaut l'utilisateur connecté)
            Forms\Components\Select::make('user_id')
                ->relationship('initiateur', 'name')
                ->label('Agent / Initiateur')
                ->searchable()
                ->preload()
                ->default(Auth::id())
                ->required()
                ->columnSpan(1),

            Forms\Components\Toggle::make('requires_approval')
                ->label('Activer le circuit d’approbation')
                ->default(false)
                ->live()
                ->columnSpan(1),

            Forms\Components\Repeater::make('approvals')
                ->relationship()
                ->label('Niveaux d’approbation')
                ->visible(fn ($get): bool => (bool) $get('requires_approval'))
                ->schema([
                    Forms\Components\TextInput::make('level')
                        ->label('Niveau')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->required(),
                    Forms\Components\Select::make('approver_id')
                        ->label('Approbateur')
                        ->relationship('approver', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Hidden::make('status')
                        ->default('pending'),
                ])
                ->columnSpanFull()
                ->reorderableWithButtons()
                ->defaultItems(1)
                ->addActionLabel('Ajouter un niveau'),

            // Section pour les imputations (à développer si besoin)
            // On peut ajouter un Repeater pour les imputations ici
            Forms\Components\Repeater::make('imputations')
                  ->relationship()
                 ->schema([
                     Forms\Components\Hidden::make('expediteur_id')
                         ->default(fn () => Auth::id()),
                     Forms\Components\Select::make('destinataire_id')
                         ->relationship('destinataire', 'name')
                         ->label('Destinataire')
                         ->searchable()
                         ->preload()
                         ->required(),
                     Forms\Components\Textarea::make('instructions')
                         ->label('Instructions')
                         ->nullable(),
                     Forms\Components\Select::make('statut_traitement')
                         ->options([
                             'En attente' => 'En attente',
                             'En cours' => 'En cours',
                             'Traité' => 'Traité',
                         ])
                         ->default('En attente')
                         ->required(),
                 ])
                 ->columnSpanFull(),

            // Numérisation (uniquement pour les courriers physiques)
            Fieldset::make('Numérisation')
                ->visible(fn ($get): bool => $get('canal') === 'Physique')
                ->schema([
                    Forms\Components\Select::make('scan_status')
                        ->label('Statut')
                        ->options([
                            'Non numérisé' => 'Non numérisé',
                            'En cours'     => 'En cours',
                            'Numérisé'     => 'Numérisé',
                        ])
                        ->default('Non numérisé')
                        ->required()
                        ->native(false)
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\DatePicker::make('date_numerisation')
                        ->label('Date de numérisation')
                        ->visible(fn ($get): bool => $get('scan_status') === 'Numérisé')
                        ->columnSpan(1),

                    Forms\Components\Select::make('numerise_par')
                        ->label('Numérisé par')
                        ->relationship('numerisePar', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get): bool => $get('scan_status') === 'Numérisé')
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            // Pièces jointes avec Media Manager
            SpatieMediaLibraryFileUpload::make('pieces_jointes')
                ->label('Pièces jointes')
                ->collection('pieces_jointes')
                ->multiple()
                ->reorderable()
                ->imagePreviewHeight('100')
                ->panelLayout('grid')
                ->responsiveImages()
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'application/zip',
                ])
                ->maxSize(20480) // 20 Mo
                ->columnSpanFull(),
                
                                    
            // Option de co-édition collaborative
            Forms\Components\Toggle::make('collaboration_enabled')
                ->label('Activer la co-édition collaborative')
                ->helperText('Permettre la modification simultanée du courrier par plusieurs utilisateurs.')
                ->default(false)
                ->columnSpan(1),

            // Liens cloud (O365, Google Docs, etc.)
            Forms\Components\Repeater::make('cloud_links')
                ->label('Liens cloud associés')
                ->addActionLabel('Ajouter un lien')
                ->schema([
                    Forms\Components\TextInput::make('url')
                        ->label('URL du fichier cloud')
                        ->required()
                        ->maxLength(512),
                    Forms\Components\Select::make('type')
                        ->label('Type de lien')
                        ->options([
                            'O365' => 'Microsoft 365',
                            'GoogleDocs' => 'Google Docs',
                            'Autre' => 'Autre',
                        ])
                        ->default('Autre')
                        ->required(),
                ])
                ->columnSpanFull()
        ]);
    }
}
