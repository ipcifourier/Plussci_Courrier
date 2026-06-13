<?php

namespace App\Filament\Resources\ReportRecommendations\Schemas;

use App\Models\Report;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ReportRecommendationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Recommendation & decision')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('report_id')
                        ->label('Rapport source')
                        ->options(fn () => Report::query()->orderByDesc('created_at')->pluck('reference', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('responsible_user_id')
                        ->label('Responsable')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false),

                    Forms\Components\Textarea::make('recommendation')
                        ->label('Recommandation extraite')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('decision')
                        ->label('Decision')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Mise en oeuvre')
                ->columns(3)
                ->schema([
                    Forms\Components\DatePicker::make('due_date')
                        ->label('Delai')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\Select::make('implementation_status')
                        ->label('Statut')
                        ->options([
                            'not_started' => 'Non demarree',
                            'in_progress' => 'En cours',
                            'implemented' => 'Mise en oeuvre',
                            'partially_implemented' => 'Partiellement mise en oeuvre',
                            'blocked' => 'Bloquee',
                            'cancelled' => 'Annulee',
                        ])
                        ->default('not_started')
                        ->required()
                        ->native(false)
                        ->live(),

                    Forms\Components\TextInput::make('progress_percent')
                        ->label('Avancement (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->required(),

                    Forms\Components\Textarea::make('implementation_notes')
                        ->label('Notes de mise en oeuvre')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('implemented_at')
                        ->label('Date de realisation')
                        ->native(false)
                        ->nullable()
                        ->visible(fn ($get): bool => in_array($get('implementation_status'), ['implemented', 'partially_implemented'], true)),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn (): ?int => Auth::id()),
                ]),
        ]);
    }
}
