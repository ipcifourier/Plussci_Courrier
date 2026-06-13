<?php

namespace App\Filament\Resources\BureauMembers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\BelongsToSelect;
use Filament\Schemas\Schema;
use App\Models\Gtt;
use App\Models\Structure;
use Illuminate\Support\Facades\Auth;

class BureauMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nom')->label('Nom')->required(),
            TextInput::make('prenom')->label('Prénom')->required(),
            TextInput::make('fonction')->label('Fonction')->required(),
            TextInput::make('email')->label('Email')->nullable(),
            TextInput::make('telephone')->label('Téléphone')->nullable(),
            FileUpload::make('photo')->label('Photo')->nullable(),
            DatePicker::make('date_entree')->label("Date d'entrée")->nullable(),
            Toggle::make('statut')->label('Actif')->default(true),
            Select::make('gtt_id')
                ->label('GTT lié')
                ->relationship('gtt', 'name', modifyQueryUsing: fn ($query) => $query->visibleTo(Auth::user()))
                ->required(),
            Select::make('structure_id')
                ->label('Structure de provenance')
                ->relationship('structure', 'nom')
                ->createOptionForm(fn (Schema $schema) => \App\Filament\Resources\Structures\Schemas\StructureForm::configure($schema))
                ->nullable(),
        ]);
    }
}
