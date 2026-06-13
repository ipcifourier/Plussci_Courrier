<x-filament-panels::page>
    <style>
        .pluss-chat-page {
            --pluss-chat-border: rgba(29, 78, 216, 0.12);
            --pluss-chat-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            --pluss-chat-blue: #173d78;
            --pluss-chat-blue-soft: #eef5ff;
            --pluss-chat-gray: #5f6f86;
            --pluss-chat-dark: #16324f;
            --pluss-chat-white: #ffffff;
        }

        .pluss-chat-page .pluss-chat-shell {
            display: grid;
            gap: 1.25rem;
        }

        .pluss-chat-page .pluss-chat-hero {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pluss-chat-page .pluss-chat-metric {
            border: 1px solid var(--pluss-chat-border);
            border-radius: 22px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(240, 247, 255, 0.92));
            box-shadow: var(--pluss-chat-shadow);
            padding: 1.1rem 1.2rem;
        }

        .pluss-chat-page .pluss-chat-metric-label {
            color: var(--pluss-chat-gray);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .pluss-chat-page .pluss-chat-metric-value {
            color: var(--pluss-chat-blue);
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 0.35rem;
        }

        .pluss-chat-page .pluss-chat-metric-subtle {
            color: var(--pluss-chat-gray);
            font-size: 0.9rem;
            margin-top: 0.35rem;
        }

        .pluss-chat-page .pluss-chat-layout {
            display: grid;
            gap: 1.25rem;
            grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        }

        .pluss-chat-page .pluss-chat-panel {
            border: 1px solid var(--pluss-chat-border);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: var(--pluss-chat-shadow);
            overflow: hidden;
        }

        .pluss-chat-page .pluss-chat-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            background: linear-gradient(180deg, rgba(238, 245, 255, 0.85), rgba(255, 255, 255, 0.92));
        }

        .pluss-chat-page .pluss-chat-panel-title {
            color: var(--pluss-chat-dark);
            font-size: 1rem;
            font-weight: 800;
        }

        .pluss-chat-page .pluss-chat-list {
            display: grid;
            gap: 0.75rem;
            padding: 1rem;
            max-height: 38rem;
            overflow-y: auto;
        }

        .pluss-chat-page .pluss-chat-user-card {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 0.85rem;
            align-items: center;
            width: 100%;
            padding: 0.95rem;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: #ffffff;
            color: var(--pluss-chat-dark);
            text-align: left;
            transition: transform 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
        }

        .pluss-chat-page .pluss-chat-user-card:hover {
            transform: translateY(-1px);
            border-color: rgba(29, 78, 216, 0.28);
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
        }

        .pluss-chat-page .pluss-chat-user-card.is-selected {
            border-color: rgba(29, 78, 216, 0.45);
            background: linear-gradient(145deg, rgba(238, 245, 255, 0.95), rgba(255, 255, 255, 1));
            box-shadow: 0 14px 28px rgba(29, 78, 216, 0.12);
        }

        .pluss-chat-page .pluss-chat-avatar {
            width: 2.9rem;
            height: 2.9rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #1f5ca8, #173d78);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
        }

        .pluss-chat-page .pluss-chat-user-name {
            color: var(--pluss-chat-dark);
            font-size: 0.98rem;
            font-weight: 800;
        }

        .pluss-chat-page .pluss-chat-user-meta,
        .pluss-chat-page .pluss-chat-user-email,
        .pluss-chat-page .pluss-chat-user-last-seen,
        .pluss-chat-page .pluss-chat-empty,
        .pluss-chat-page .pluss-chat-composer-help {
            color: var(--pluss-chat-gray);
        }

        .pluss-chat-page .pluss-chat-user-meta,
        .pluss-chat-page .pluss-chat-user-email,
        .pluss-chat-page .pluss-chat-user-last-seen {
            font-size: 0.8rem;
        }

        .pluss-chat-page .pluss-chat-presence {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #0f766e;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .pluss-chat-page .pluss-chat-presence-dot {
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
        }

        .pluss-chat-page .pluss-chat-unread {
            min-width: 1.5rem;
            height: 1.5rem;
            border-radius: 999px;
            padding: 0 0.45rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dc2626;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .pluss-chat-page .pluss-chat-conversation {
            display: grid;
            grid-template-rows: auto minmax(20rem, 1fr) auto;
            min-height: 38rem;
        }

        .pluss-chat-page .pluss-chat-conversation-head {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            background: linear-gradient(180deg, rgba(238, 245, 255, 0.85), rgba(255, 255, 255, 0.92));
        }

        .pluss-chat-page .pluss-chat-conversation-body {
            padding: 1rem 1.1rem;
            overflow-y: auto;
            background: linear-gradient(180deg, rgba(248, 251, 255, 0.96), rgba(255, 255, 255, 1));
        }

        .pluss-chat-page .pluss-chat-thread {
            display: grid;
            gap: 0.85rem;
        }

        .pluss-chat-page .pluss-chat-bubble-row {
            display: flex;
        }

        .pluss-chat-page .pluss-chat-bubble-row.is-mine {
            justify-content: flex-end;
        }

        .pluss-chat-page .pluss-chat-bubble-row.is-theirs {
            justify-content: flex-start;
        }

        .pluss-chat-page .pluss-chat-bubble {
            max-width: min(80%, 44rem);
            border-radius: 20px;
            padding: 0.9rem 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .pluss-chat-page .pluss-chat-bubble.is-mine {
            background: linear-gradient(145deg, #1f5ca8, #173d78);
            color: #ffffff;
            border-bottom-right-radius: 8px;
        }

        .pluss-chat-page .pluss-chat-bubble.is-theirs {
            background: #ffffff;
            color: var(--pluss-chat-dark);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-bottom-left-radius: 8px;
        }

        .pluss-chat-page .pluss-chat-bubble-meta {
            font-size: 0.72rem;
            opacity: 0.82;
            margin-bottom: 0.35rem;
            font-weight: 700;
        }

        .pluss-chat-page .pluss-chat-bubble-text {
            white-space: pre-wrap;
            line-height: 1.55;
            font-size: 0.95rem;
        }

        .pluss-chat-page .pluss-chat-attachments {
            display: grid;
            gap: 0.6rem;
            margin-top: 0.75rem;
        }

        .pluss-chat-page .pluss-chat-attachment {
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(255, 255, 255, 0.14);
            padding: 0.7rem 0.85rem;
        }

        .pluss-chat-page .pluss-chat-bubble.is-theirs .pluss-chat-attachment {
            background: #f8fbff;
        }

        .pluss-chat-page .pluss-chat-attachment-name {
            font-size: 0.86rem;
            font-weight: 700;
            line-height: 1.35;
            word-break: break-word;
        }

        .pluss-chat-page .pluss-chat-attachment-meta {
            font-size: 0.76rem;
            margin-top: 0.2rem;
            opacity: 0.82;
        }

        .pluss-chat-page .pluss-chat-attachment-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.55rem;
        }

        .pluss-chat-page .pluss-chat-attachment-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.32rem 0.7rem;
            font-size: 0.76rem;
            font-weight: 700;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.18);
            color: inherit;
            border: 1px solid rgba(255, 255, 255, 0.22);
        }

        .pluss-chat-page .pluss-chat-bubble.is-theirs .pluss-chat-attachment-link {
            background: #ffffff;
            border-color: rgba(29, 78, 216, 0.14);
            color: var(--pluss-chat-blue);
        }

        .pluss-chat-page .pluss-chat-attachment-preview {
            display: block;
            max-width: 100%;
            max-height: 16rem;
            object-fit: cover;
            border-radius: 14px;
            margin-top: 0.6rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .pluss-chat-page .pluss-chat-file-picker {
            display: grid;
            gap: 0.55rem;
            margin-top: 0.85rem;
        }

        .pluss-chat-page .pluss-chat-file-input {
            display: block;
            width: 100%;
            border-radius: 14px;
            border: 1px dashed rgba(29, 78, 216, 0.24);
            background: #f8fbff;
            color: var(--pluss-chat-dark);
            padding: 0.85rem;
            font-size: 0.88rem;
        }

        .pluss-chat-page .pluss-chat-file-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .pluss-chat-page .pluss-chat-file-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            background: #eef5ff;
            color: var(--pluss-chat-blue);
            border: 1px solid rgba(29, 78, 216, 0.14);
            padding: 0.32rem 0.72rem;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .pluss-chat-page .pluss-chat-file-remove {
            border: none;
            background: transparent;
            color: inherit;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 800;
            padding: 0;
        }

        .pluss-chat-page .pluss-chat-composer {
            padding: 1rem 1.1rem 1.1rem;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
            background: #ffffff;
        }

        .pluss-chat-page .pluss-chat-textarea {
            width: 100%;
            min-height: 7rem;
            resize: vertical;
            border-radius: 18px;
            border: 1px solid rgba(29, 78, 216, 0.18);
            background: #f8fbff;
            color: var(--pluss-chat-dark);
            padding: 0.95rem 1rem;
            font-size: 0.95rem;
            line-height: 1.55;
            outline: none;
        }

        .pluss-chat-page .pluss-chat-textarea:focus {
            border-color: rgba(29, 78, 216, 0.4);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .pluss-chat-page .pluss-chat-composer-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 0.8rem;
        }

        .pluss-chat-page .pluss-chat-empty {
            border: 1px dashed rgba(148, 163, 184, 0.45);
            border-radius: 18px;
            background: rgba(248, 250, 252, 0.85);
            padding: 1rem;
            font-size: 0.95rem;
        }

        @media (max-width: 1100px) {
            .pluss-chat-page .pluss-chat-hero,
            .pluss-chat-page .pluss-chat-layout {
                grid-template-columns: 1fr;
            }

            .pluss-chat-page .pluss-chat-conversation {
                min-height: 34rem;
            }
        }
    </style>

    <div class="pluss-chat-page" wire:poll.10s>
        <div class="pluss-chat-shell">
        @if (! \Illuminate\Support\Facades\Schema::hasTable('chat_messages'))
            <x-filament::section>
                <div class="text-sm text-danger-700">
                    Le chat n'est pas encore disponible: migration manquante pour la table <code>chat_messages</code>.
                </div>
            </x-filament::section>
        @endif

        <div class="pluss-chat-hero">
            <div class="pluss-chat-metric">
                <div class="pluss-chat-metric-label">Connectés</div>
                <div class="pluss-chat-metric-value">{{ $this->onlineUsers->count() }}</div>
                <div class="pluss-chat-metric-subtle">Actifs sur les {{ $this->onlineWindowMinutes }} dernières minutes</div>
            </div>

            <div class="pluss-chat-metric">
                <div class="pluss-chat-metric-label">Messages non lus</div>
                <div class="pluss-chat-metric-value">{{ $this->unreadCount }}</div>
                <div class="pluss-chat-metric-subtle">Tous expéditeurs confondus</div>
            </div>

            <div class="pluss-chat-metric">
                <div class="pluss-chat-metric-label">Conversation active</div>
                <div class="pluss-chat-metric-value">
                    {{ $this->selectedUser?->name ?? 'Aucune' }}
                </div>
                <div class="pluss-chat-metric-subtle">
                    @if ($this->selectedUser)
                        {{ $this->selectedUserOnline ? 'En ligne' : 'Hors ligne' }}
                    @else
                        Sélectionnez un contact pour démarrer
                    @endif
                </div>
            </div>
        </div>

        <div class="pluss-chat-layout">
            <section class="pluss-chat-panel">
                <div class="pluss-chat-panel-header">
                    <div class="pluss-chat-panel-title">Connectés</div>
                    <div class="pluss-chat-user-last-seen">{{ $this->onlineUsers->count() }} utilisateur(s)</div>
                </div>

                <div class="pluss-chat-list">
                    @forelse ($this->onlineUsers as $onlineUser)
                        @php
                            $isSelected = (int) $this->targetUserId === (int) $onlineUser->id;
                            $isMe = (int) auth()->id() === (int) $onlineUser->id;
                            $unread = $this->unreadCountsBySender[(int) $onlineUser->id] ?? 0;
                            $initials = collect(explode(' ', (string) $onlineUser->name))
                                ->filter()
                                ->take(2)
                                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                        @endphp

                        <button
                            type="button"
                            wire:click="selectUser({{ (int) $onlineUser->id }})"
                            @class([
                                'pluss-chat-user-card',
                                'is-selected' => $isSelected,
                            ])
                            @disabled($isMe)
                        >
                            <div class="pluss-chat-avatar">{{ $initials !== '' ? $initials : 'U' }}</div>

                            <div>
                                <div class="pluss-chat-user-name">{{ $onlineUser->name }} @if ($isMe) (vous) @endif</div>
                                <div class="pluss-chat-user-email">{{ $onlineUser->email }}</div>
                                <div class="pluss-chat-user-meta">
                                    {{ $onlineUser->poste ?: 'Poste non renseigné' }}
                                    @if ($onlineUser->departement?->nom)
                                        • {{ $onlineUser->departement->nom }}
                                    @endif
                                </div>
                                <div class="pluss-chat-user-last-seen">
                                    Vu {{ \Illuminate\Support\Carbon::createFromTimestamp((int) $onlineUser->last_activity)->diffForHumans() }}
                                </div>
                            </div>

                            <div class="space-y-2 text-right">
                                <div class="pluss-chat-presence">
                                    <span class="pluss-chat-presence-dot"></span>
                                    <span>En ligne</span>
                                </div>
                                @if ($unread > 0 && ! $isMe)
                                    <span class="pluss-chat-unread">{{ $unread }}</span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="pluss-chat-empty">
                            Aucun utilisateur en ligne pour le moment.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="pluss-chat-panel">
                @if (! $this->targetUserId)
                    <div class="pluss-chat-conversation">
                        <div class="pluss-chat-panel-header">
                            <div class="pluss-chat-panel-title">Chat interne</div>
                            <div class="pluss-chat-user-last-seen">Aucune conversation sélectionnée</div>
                        </div>

                        <div class="pluss-chat-conversation-body">
                            <div class="pluss-chat-empty">
                                Sélectionnez un utilisateur en ligne pour engager la conversation.
                            </div>
                        </div>

                        <div class="pluss-chat-composer">
                            <div class="pluss-chat-composer-help">
                                La zone de saisie s’activera dès qu’un contact sera sélectionné.
                            </div>
                        </div>
                    </div>
                @else
                    <div class="pluss-chat-conversation">
                        <div class="pluss-chat-conversation-head">
                            @php
                                $selectedInitials = collect(explode(' ', (string) $this->selectedUser?->name))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                    ->implode('');
                            @endphp

                            <div class="pluss-chat-avatar">{{ $selectedInitials !== '' ? $selectedInitials : 'U' }}</div>

                            <div class="min-w-0">
                                <div class="pluss-chat-panel-title">{{ $this->selectedUser?->name ?? 'Utilisateur' }}</div>
                                <div class="pluss-chat-user-email">{{ $this->selectedUser?->email }}</div>
                                <div class="pluss-chat-user-meta">
                                    {{ $this->selectedUser?->poste ?: 'Poste non renseigné' }}
                                    @if ($this->selectedUser?->departement?->nom)
                                        • {{ $this->selectedUser->departement->nom }}
                                    @endif
                                </div>
                            </div>

                            <div class="text-right">
                                @if ($this->selectedUserOnline)
                                    <div class="pluss-chat-presence">
                                        <span class="pluss-chat-presence-dot"></span>
                                        <span>En ligne</span>
                                    </div>
                                @else
                                    <div class="pluss-chat-user-last-seen">Hors ligne</div>
                                @endif
                            </div>
                        </div>

                        <div class="pluss-chat-conversation-body">
                            <div class="pluss-chat-thread">
                                @forelse ($this->conversation as $message)
                                    @php
                                        $mine = (int) $message->sender_id === (int) auth()->id();
                                        $messageAttachments = $message->media->where('collection_name', 'attachments');
                                    @endphp
                                    <div @class([
                                        'pluss-chat-bubble-row',
                                        'is-mine' => $mine,
                                        'is-theirs' => ! $mine,
                                    ])>
                                        <div @class([
                                            'pluss-chat-bubble',
                                            'is-mine' => $mine,
                                            'is-theirs' => ! $mine,
                                        ])>
                                            <div class="pluss-chat-bubble-meta">
                                                {{ $mine ? 'Vous' : ($message->sender->name ?? 'Utilisateur') }} • {{ $message->created_at?->format('d/m/Y H:i') }}
                                            </div>
                                            @if ($message->shouldDisplayBody())
                                                <div class="pluss-chat-bubble-text">{{ $message->body }}</div>
                                            @endif

                                            @if ($messageAttachments->isNotEmpty())
                                                <div class="pluss-chat-attachments">
                                                    @foreach ($messageAttachments as $attachment)
                                                        <div class="pluss-chat-attachment">
                                                            <div class="pluss-chat-attachment-name">{{ $attachment->file_name }}</div>
                                                            <div class="pluss-chat-attachment-meta">
                                                                {{ strtoupper(pathinfo((string) $attachment->file_name, PATHINFO_EXTENSION) ?: 'FILE') }}
                                                                • {{ number_format(((int) $attachment->size) / 1024, 1, ',', ' ') }} Ko
                                                            </div>

                                                            @if ($message->isPreviewableAttachment($attachment) && str_starts_with((string) ($attachment->mime_type ?? ''), 'image/'))
                                                                <a href="{{ route('chat.attachments.show', ['chatMessage' => $message, 'media' => $attachment]) }}" target="_blank" rel="noreferrer">
                                                                    <img
                                                                        src="{{ route('chat.attachments.show', ['chatMessage' => $message, 'media' => $attachment]) }}"
                                                                        alt="{{ $attachment->file_name }}"
                                                                        class="pluss-chat-attachment-preview"
                                                                    >
                                                                </a>
                                                            @endif

                                                            <div class="pluss-chat-attachment-actions">
                                                                <a
                                                                    href="{{ route('chat.attachments.show', ['chatMessage' => $message, 'media' => $attachment]) }}"
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    class="pluss-chat-attachment-link"
                                                                >
                                                                    Ouvrir
                                                                </a>
                                                                <a
                                                                    href="{{ route('chat.attachments.show', ['chatMessage' => $message, 'media' => $attachment, 'download' => 1]) }}"
                                                                    class="pluss-chat-attachment-link"
                                                                >
                                                                    Télécharger
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="pluss-chat-empty">
                                        Aucun message pour cette conversation. Envoyez le premier message pour démarrer.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <form wire:submit="sendMessage" class="pluss-chat-composer">
                            <textarea
                                wire:model.defer="messageBody"
                                maxlength="{{ $this->messageLimit }}"
                                class="pluss-chat-textarea"
                                placeholder="Écrivez votre message ici..."
                            ></textarea>

                            <div class="pluss-chat-file-picker">
                                <input
                                    type="file"
                                    wire:model="attachments"
                                    multiple
                                    accept="{{ $this->attachmentAccept }}"
                                    class="pluss-chat-file-input"
                                >

                                @if ($this->attachments)
                                    <div class="pluss-chat-file-list">
                                        @foreach ($this->attachments as $index => $attachment)
                                            <span class="pluss-chat-file-chip">
                                                {{ $attachment->getClientOriginalName() }}
                                                <button type="button" wire:click="removeAttachment({{ $index }})" class="pluss-chat-file-remove" aria-label="Retirer le fichier">
                                                    ×
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="pluss-chat-composer-footer">
                                <div class="pluss-chat-composer-help">
                                    Maximum {{ $this->messageLimit }} caractères, {{ $this->attachmentMaxCount }} fichiers et {{ (int) ($this->attachmentMaxSizeKb / 1024) }} Mo par fichier.
                                </div>
                                <x-filament::button type="submit" icon="heroicon-o-paper-airplane" color="primary">
                                    Envoyer
                                </x-filament::button>
                            </div>
                        </form>
                    </div>
                @endif
            </section>
        </div>
        </div>
    </div>
</x-filament-panels::page>
