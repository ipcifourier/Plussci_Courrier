<?php

namespace App\Filament\Pages;

use App\Models\ChatMessage;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class OnlineUsersChatPage extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Utilisateurs en ligne';

    protected static ?string $title = 'Utilisateurs en ligne & Chat';

    protected static ?int $navigationSort = 26;

    protected string $view = 'filament.pages.online-users-chat';

    public ?int $targetUserId = null;

    public string $messageBody = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    public int $onlineWindowMinutes = 10;

    public int $messageLimit = 2000;

    public int $attachmentMaxSizeKb = 20480;

    public int $attachmentMaxCount = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'Taches et Collaboration';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->can('collaboration.online_users.view')
        );
    }

    public function mount(): void
    {
        if (! $this->hasChatStorage()) {
            return;
        }

        $this->targetUserId = $this->onlineUsers
            ->firstWhere('id', '!=', Auth::id())
            ?->id;

        $this->markConversationAsRead();
    }

    public function getOnlineUsersProperty(): Collection
    {
        if (! $this->hasSessionStorage()) {
            return collect();
        }

        $cutoff = now()->subMinutes($this->onlineWindowMinutes)->timestamp;
        $sessionsTable = (string) config('session.table', 'sessions');

        $activeSessions = DB::table($sessionsTable)
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $cutoff)
            ->groupBy('user_id');

        return User::query()
            ->with('departement:id,nom')
            ->joinSub($activeSessions, 'active_sessions', function ($join): void {
                $join->on('users.id', '=', 'active_sessions.user_id');
            })
            ->orderByDesc('active_sessions.last_activity')
            ->get([
                'users.id',
                'users.name',
                'users.email',
                'users.poste',
                'users.avatar_path',
                'users.departement_id',
                DB::raw('active_sessions.last_activity as last_activity'),
            ]);
    }

    public function getSelectedUserProperty(): ?User
    {
        if (! $this->targetUserId) {
            return null;
        }

        /** @var ?User $selected */
        $selected = $this->onlineUsers->firstWhere('id', $this->targetUserId);

        if ($selected instanceof User) {
            return $selected;
        }

        return User::query()
            ->with('departement:id,nom')
            ->find($this->targetUserId);
    }

    public function getSelectedUserOnlineProperty(): bool
    {
        if (! $this->targetUserId) {
            return false;
        }

        return $this->onlineUsers->contains(fn (User $user): bool => (int) $user->id === (int) $this->targetUserId);
    }

    public function getUnreadCountsBySenderProperty(): array
    {
        if (! $this->hasChatStorage() || ! Auth::id()) {
            return [];
        }

        return ChatMessage::query()
            ->where('recipient_id', Auth::id())
            ->whereNull('read_at')
            ->select('sender_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('sender_id')
            ->pluck('aggregate', 'sender_id')
            ->mapWithKeys(fn ($count, $senderId): array => [(int) $senderId => (int) $count])
            ->all();
    }

    public function getConversationProperty(): Collection
    {
        if (! $this->hasChatStorage()) {
            return collect();
        }

        $userId = Auth::id();
        $targetUserId = $this->targetUserId;

        if (! $userId || ! $targetUserId || $targetUserId === $userId) {
            return collect();
        }

        $this->markConversationAsRead();

        return ChatMessage::query()
            ->with(['sender:id,name', 'recipient:id,name', 'media'])
            ->where(function (Builder $query) use ($userId, $targetUserId): void {
                $query
                    ->where('sender_id', $userId)
                    ->where('recipient_id', $targetUserId);
            })
            ->orWhere(function (Builder $query) use ($userId, $targetUserId): void {
                $query
                    ->where('sender_id', $targetUserId)
                    ->where('recipient_id', $userId);
            })
            ->orderBy('created_at')
            ->limit(200)
            ->get();
    }

    public function selectUser(int $userId): void
    {
        if ($userId === (int) Auth::id()) {
            return;
        }

        if (! $this->onlineUsers->contains(fn (User $user): bool => (int) $user->id === $userId)) {
            Notification::make()
                ->title('Utilisateur hors ligne')
                ->warning()
                ->send();

            return;
        }

        $this->targetUserId = $userId;
        $this->markConversationAsRead();
    }

    public function sendMessage(): void
    {
        if (! $this->hasChatStorage()) {
            Notification::make()
                ->title('Chat indisponible')
                ->body('La table de messages n\'est pas encore migree.')
                ->danger()
                ->send();

            return;
        }

        $userId = Auth::id();
        $targetUserId = $this->targetUserId;
        $body = trim($this->messageBody);
        $attachments = $this->attachments;

        if (! $userId || ! $targetUserId || $targetUserId === $userId) {
            return;
        }

        if ($body === '' && $attachments === []) {
            return;
        }

        $validated = validator(
            ['attachments' => $attachments],
            [
                'attachments' => ['array', 'max:' . $this->attachmentMaxCount],
                'attachments.*' => ['file', 'max:' . $this->attachmentMaxSizeKb],
            ],
            [
                'attachments.max' => 'Vous pouvez joindre au maximum ' . $this->attachmentMaxCount . ' fichiers par message.',
                'attachments.*.max' => 'Chaque fichier doit rester en dessous de ' . (int) ($this->attachmentMaxSizeKb / 1024) . ' Mo.',
            ],
        )->validate();

        $message = ChatMessage::query()->create([
            'sender_id' => $userId,
            'recipient_id' => $targetUserId,
            'body' => mb_substr($body !== '' ? $body : ChatMessage::ATTACHMENT_PLACEHOLDER, 0, $this->messageLimit),
        ]);

        $uploadedCount = 0;

        foreach ($validated['attachments'] ?? [] as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            try {
                $message
                    ->addMedia($file->getRealPath())
                    ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection('attachments');

                $uploadedCount++;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($body === '' && $uploadedCount === 0) {
            $message->delete();

            Notification::make()
                ->title('Envoi impossible')
                ->body('Aucune pièce jointe valide n\'a pu être envoyée.')
                ->danger()
                ->send();

            return;
        }

        $this->messageBody = '';
        $this->attachments = [];

        if ($uploadedCount < count($attachments)) {
            Notification::make()
                ->title('Message envoyé partiellement')
                ->body('Certaines pièces jointes n\'ont pas pu être ajoutées.')
                ->warning()
                ->send();
        }
    }

    public function removeAttachment(int $index): void
    {
        if (! array_key_exists($index, $this->attachments)) {
            return;
        }

        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function getAttachmentAcceptProperty(): string
    {
        return '.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.webp,.zip,.rar';
    }

    public function getUnreadCountProperty(): int
    {
        if (! $this->hasChatStorage() || ! Auth::id()) {
            return 0;
        }

        return ChatMessage::query()
            ->where('recipient_id', Auth::id())
            ->whereNull('read_at')
            ->count();
    }

    private function markConversationAsRead(): void
    {
        $userId = Auth::id();
        $targetUserId = $this->targetUserId;

        if (! $this->hasChatStorage() || ! $userId || ! $targetUserId) {
            return;
        }

        ChatMessage::query()
            ->where('sender_id', $targetUserId)
            ->where('recipient_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function hasChatStorage(): bool
    {
        return Schema::hasTable('chat_messages');
    }

    private function hasSessionStorage(): bool
    {
        return Schema::hasTable((string) config('session.table', 'sessions'));
    }
}
