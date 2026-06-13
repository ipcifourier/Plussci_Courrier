<?php

namespace App\Filament\Resources\Courriers\Pages;

use App\Filament\Resources\Courriers\CourrierResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCourrier extends ViewRecord
{
    protected static string $resource = CourrierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // C3 — Feuille de signature PDF
            Action::make('signature_sheet')
                ->label('Feuille de signature')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->url(fn (): string => route('courriers.signature.pdf', $this->record))
                ->openUrlInNewTab(),

            EditAction::make(),
        ];
    }

    protected function getInfolistSchema(): array
    {
        return [
            \Filament\Infolists\Components\TextEntry::make('collaboration_enabled')
                ->label('Co-édition collaborative')
                ->formatStateUsing(fn ($state) => $state ? 'Activée' : 'Désactivée'),

            \Filament\Infolists\Components\RepeatableEntry::make('cloud_links')
                ->label('Liens cloud associés')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('type')
                        ->label('Type'),
                    \Filament\Infolists\Components\TextEntry::make('url')
                        ->label('URL')
                        ->url(fn ($state) => $state)
                        ->openUrlInNewTab(),
                ]),
        ];
    }
}
