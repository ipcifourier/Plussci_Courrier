<?php

namespace App\Filament\Resources\Dossiers\Schemas;

use App\Models\Dossier;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;

class DossierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identification')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code dossier')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('libelle')
                            ->label('Libelle')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Classement GED annuel')
                    ->description('Permet de rattacher le dossier a une annee d\'activite, a un niveau de classement et a une arborescence parent-enfant.')
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('Dossier parent')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'libelle',
                                modifyQueryUsing: fn ($query) => $query
                                    ->visibleTo(Auth::user())
                                    ->orderByDesc('annee_activite')
                                    ->orderBy('ordre_affichage')
                                    ->orderBy('libelle'),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                if (! $state) {
                                    return;
                                }

                                $parent = Dossier::query()->visibleTo(Auth::user())->find($state);

                                if (! $parent) {
                                    return;
                                }

                                $set('annee_activite', $parent->annee_activite);
                                $set('ordre_affichage', ((int) Dossier::query()->visibleTo(Auth::user())->where('parent_id', $parent->id)->max('ordre_affichage')) + 10);

                                if ($parent->type_dossier === Dossier::TYPE_YEAR) {
                                    $set('type_dossier', Dossier::TYPE_CATEGORY);
                                } elseif ($parent->type_dossier !== Dossier::TYPE_STANDARD) {
                                    $set('type_dossier', Dossier::TYPE_SUBCATEGORY);
                                }
                            })
                            ->nullable()
                            ->helperText('Choisissez un parent pour construire une arborescence annee > rubrique > sous-dossier.'),

                        Forms\Components\TextInput::make('annee_activite')
                            ->label('Annee d\'activite')
                            ->numeric()
                            ->default((int) date('Y'))
                            ->minValue(2020)
                            ->maxValue(2100)
                            ->helperText('Utilisez la meme annee pour tous les dossiers d\'une meme campagne ou activite.'),

                        Forms\Components\Select::make('type_dossier')
                            ->label('Niveau de classement')
                            ->options(Dossier::typeOptions())
                            ->default(Dossier::TYPE_STANDARD)
                            ->required(),

                        Forms\Components\TextInput::make('ordre_affichage')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Utilisez un pas de 10 pour inserer facilement de nouveaux dossiers entre deux niveaux.'),
                    ])
                    ->columns(2),

                Section::make('Responsabilite et securite')
                    ->schema([
                        Forms\Components\Select::make('owner_id')
                            ->label('Responsable')
                            ->relationship('owner', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::id()),

                        Forms\Components\Select::make('confidentialite')
                            ->options([
                                'Standard' => 'Standard',
                                'Confidentiel' => 'Confidentiel',
                                'Personnel' => 'Personnel',
                            ])
                            ->default('Standard')
                            ->required(),

                        Forms\Components\Select::make('statut')
                            ->options([
                                'Actif' => 'Actif',
                                'Clos' => 'Clos',
                                'Archive' => 'Archive',
                            ])
                            ->default('Actif')
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }
}
