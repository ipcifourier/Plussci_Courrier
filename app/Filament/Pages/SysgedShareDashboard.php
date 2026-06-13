<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentSession;
use App\Models\DocumentShare;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class SysgedShareDashboard extends Page
{
    public const THEME_PRESET_LIGHT_FORCE = 'light-force';

    public const THEME_PRESET_SOFT_DARK = 'soft-dark';

    /** @var array<int, string> */
    private const ALLOWED_THEME_PRESETS = [
        self::THEME_PRESET_LIGHT_FORCE,
        self::THEME_PRESET_SOFT_DARK,
    ];

    public string $themePreset = self::THEME_PRESET_SOFT_DARK;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static ?string $navigationLabel = 'SYSGED Share';

    protected static ?string $title = 'SYSGED Share';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.sysged-share-dashboard';

    public static function getNavigationGroup(): ?string
    {
        return 'GED';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User;
    }

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $preferences = is_array($user->preferences) ? $user->preferences : [];
        $savedPreset = (string) ($preferences['sysged_share_theme_preset'] ?? '');

        $this->themePreset = in_array($savedPreset, self::ALLOWED_THEME_PRESETS, true)
            ? $savedPreset
            : self::THEME_PRESET_SOFT_DARK;
    }

    public function setThemePreset(string $preset): void
    {
        if (! in_array($preset, self::ALLOWED_THEME_PRESETS, true)) {
            return;
        }

        $this->themePreset = $preset;

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $preferences = is_array($user->preferences) ? $user->preferences : [];
        $preferences['sysged_share_theme_preset'] = $preset;

        $user->forceFill(['preferences' => $preferences])->save();
    }

    public function getShareStats(): array
    {
        $sharedDocumentIds = $this->sharedDocumentIds();

        return [
            'active_shares' => $this->activeRecipientSharesQuery()->count(),
            'external_shares' => $this->activeRecipientSharesQuery()
                ->where('type', 'external')
                ->count(),
            'active_presence' => DocumentSession::query()
                ->where('last_seen_at', '>=', now()->subMinutes(5))
                ->whereIn('document_id', $sharedDocumentIds)
                ->count(),
            'actions_today' => AuditLog::query()
                ->whereDate('created_at', today())
                ->where('entity_type', Document::class)
                ->whereIn('entity_id', $sharedDocumentIds)
                ->where(function ($query): void {
                    $query->where('action', 'like', 'documents.share.%')
                        ->orWhere('action', 'documents.onlyoffice.open');
                })
                ->count(),
        ];
    }

    public function getRecentShareEvents()
    {
        $sharedDocumentIds = $this->sharedDocumentIds();

        return AuditLog::query()
            ->with('actor:id,name')
            ->where('entity_type', Document::class)
            ->whereIn('entity_id', $sharedDocumentIds)
            ->where(function ($query): void {
                $query->where('action', 'like', 'documents.share.%')
                    ->orWhere('action', 'documents.onlyoffice.open');
            })
            ->latest('created_at')
            ->limit(12)
            ->get();
    }

    public function getMyShares(): Collection
    {
        return $this->activeRecipientSharesQuery()
            ->with(['document:id,titre,reference_doc', 'sharedBy:id,name'])
            ->latest('created_at')
            ->limit(20)
            ->get();
    }

    public function canOpenDocumentsIndex(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('ged.documents.view')
        );
    }

    public function getDocumentsUrl(): string
    {
        return DocumentResource::getUrl('index');
    }

    public function getDocumentViewUrl(int $documentId): string
    {
        return DocumentResource::getUrl('view', ['record' => $documentId]);
    }

    private function activeRecipientSharesQuery(): Builder
    {
        $userId = Auth::id();

        return DocumentShare::query()
            ->where('recipient_user_id', $userId)
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    private function sharedDocumentIds(): Collection
    {
        return $this->activeRecipientSharesQuery()
            ->distinct('document_id')
            ->pluck('document_id');
    }
}
