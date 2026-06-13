<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Journal d\'audit';

    protected static ?string $recordTitleAttribute = 'action';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Audit et traçabilité';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        $freshUser = User::query()->find($user->id);

        if (! $freshUser instanceof User) {
            return false;
        }

        if ($freshUser->hasRole('Super Admin')) {
            return true;
        }

        try {
            return $freshUser->hasAnyPermission([
                'audit.view',
                'audit.export',
            ]);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s'),
                TextEntry::make('actor.name')->label('Acteur')->placeholder('Système'),
                TextEntry::make('action')->label('Action'),
                TextEntry::make('entity_type')->label('Type entité')->placeholder('-'),
                TextEntry::make('entity_id')->label('ID entité')->placeholder('-'),
                TextEntry::make('ip_address')->label('IP')->placeholder('-'),
                TextEntry::make('user_agent')->label('User-Agent')->columnSpanFull()->placeholder('-'),
                TextEntry::make('diff_view')
                    ->label('Modifications (avant → après)')
                    ->html()
                    ->formatStateUsing(fn ($state, $record): string => self::renderJsonDiff(
                        is_array($record->before_json) ? $record->before_json : [],
                        is_array($record->after_json) ? $record->after_json : [],
                    ))
                    ->columnSpanFull(),
                TextEntry::make('meta_json')
                    ->label('Métadonnées')
                    ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-')
                    ->columnSpanFull(),
            ]);
    }

    /** AU1 — Rendu du diff coloré avant/après pour l'infolist. */
    private static function renderJsonDiff(array $before, array $after): string
    {
        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));

        if (empty($allKeys)) {
            return '<em style="color:#9ca3af;font-size:13px;">Aucune donnée enregistrée</em>';
        }

        $rows = '';
        foreach ($allKeys as $key) {
            $bVal = array_key_exists($key, $before)
                ? (is_array($before[$key]) ? json_encode($before[$key], JSON_UNESCAPED_UNICODE) : (string) $before[$key])
                : null;
            $aVal = array_key_exists($key, $after)
                ? (is_array($after[$key]) ? json_encode($after[$key], JSON_UNESCAPED_UNICODE) : (string) $after[$key])
                : null;

            if ($bVal === $aVal) {
                continue;
            }

            $bg     = $bVal === null ? 'background:#ecfdf5;' : ($aVal === null ? 'background:#fef2f2;' : 'background:#fffbeb;');
            $symbol = $bVal === null ? '＋' : ($aVal === null ? '－' : '~');
            $bHtml  = $bVal !== null ? htmlspecialchars($bVal, ENT_QUOTES, 'UTF-8') : '<em style="color:#9ca3af;">—</em>';
            $aHtml  = $aVal !== null ? htmlspecialchars($aVal, ENT_QUOTES, 'UTF-8') : '<em style="color:#9ca3af;">—</em>';

            $rows .= '<tr style="' . $bg . '">'
                . '<td style="padding:4px 8px;font-family:monospace;font-size:12px;font-weight:600;color:#4b5563;white-space:nowrap;">' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:4px 8px;font-family:monospace;font-size:12px;color:#dc2626;max-width:280px;word-break:break-all;">' . $bHtml . '</td>'
                . '<td style="padding:4px 8px;font-family:monospace;font-size:14px;color:#9ca3af;text-align:center;">' . $symbol . '</td>'
                . '<td style="padding:4px 8px;font-family:monospace;font-size:12px;color:#16a34a;max-width:280px;word-break:break-all;">' . $aHtml . '</td>'
                . '</tr>';
        }

        if ($rows === '') {
            return '<em style="color:#9ca3af;font-size:13px;">Aucune modification de données (création ou action sans changement de champ).</em>';
        }

        return '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            . '<thead><tr style="background:#f3f4f6;">'
            . '<th style="padding:6px 8px;text-align:left;font-size:11px;color:#6b7280;font-weight:600;">Champ</th>'
            . '<th style="padding:6px 8px;text-align:left;font-size:11px;color:#dc2626;font-weight:600;">Avant</th>'
            . '<th style="padding:6px 8px;text-align:center;font-size:11px;color:#9ca3af;">→</th>'
            . '<th style="padding:6px 8px;text-align:left;font-size:11px;color:#16a34a;font-weight:600;">Après</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('actor')->latest('created_at');
    }
}
