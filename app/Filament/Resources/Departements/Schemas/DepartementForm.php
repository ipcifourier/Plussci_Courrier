<?php

namespace App\Filament\Resources\Departements\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DepartementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nom')
                    ->required(),
                Select::make('responsable')
                    ->label('Responsable du département')
                    ->searchable()
                    ->preload()
                    ->options(fn () =>
                        User::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->name => $user->name . ' (' . $user->email . ')'
                            ])
                    )
                    ->getSearchResultsUsing(fn (string $search) =>
                        User::query()
                            ->where('is_active', true)
                            ->where(function (Builder $query) use ($search): void {
                                $query->where('name', 'like', "%{$search}%")
                                      ->orWhere('email', 'like', "%{$search}%");
                            })
                            ->orderBy('name')
                            ->limit(20)
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->name => $user->name . ' (' . $user->email . ')'
                            ])
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => filled($value) ? User::query()
                        ->where('name', $value)
                        ->value('email') !== null
                            ? $value . ' (' . User::query()->where('name', $value)->value('email') . ')'
                            : $value : null)
                    ->nullable(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'filament-description-field']),
            ]);
    }
}
