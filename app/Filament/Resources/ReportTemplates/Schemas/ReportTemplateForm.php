<?php

namespace App\Filament\Resources\ReportTemplates\Schemas;

use App\Models\ReportCategory;
use Filament\Forms;
use Filament\Schemas\Schema;

class ReportTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->label('Titre du modele')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('report_category_id')
                ->label('Categorie')
                ->options(fn () => ReportCategory::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->native(false),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\Textarea::make('content_template')
                ->label('Contenu type')
                ->rows(10)
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\SpatieMediaLibraryFileUpload::make('template_files')
                ->label('Fichier modele institutionnel')
                ->collection('report_templates')
                ->multiple()
                ->downloadable()
                ->openable()
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_validated')
                ->label('Modele valide PLUSS')
                ->default(true),
        ]);
    }
}
