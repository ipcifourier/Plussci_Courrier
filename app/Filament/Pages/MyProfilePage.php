<?php

namespace App\Filament\Pages;

use App\Models\Departement;
use App\Models\SyncDevice;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MyProfilePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $title = 'Mon profil';

    protected static ?string $slug = 'mon-profil';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.my-profile';

    public ?array $data = [];

    /** @var array<int, array<string, mixed>> */
    public array $activeSessions = [];

    /** @var array<int, array<string, mixed>> */
    public array $activeSyncDevices = [];

    public ?string $latestSyncToken = null;

    public static function canAccess(): bool
    {
        return Auth::user() instanceof User;
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        $preferences = is_array($user->preferences) ? $user->preferences : [];
        $syncPreferences = is_array($preferences['sync'] ?? null) ? $preferences['sync'] : [];

        $this->activeSessions = $this->resolveActiveSessions($user);
        $this->activeSyncDevices = $this->resolveSyncDevices($user);

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'departement_id' => $user->departement_id,
            'poste' => $user->poste,
            'hire_date' => $user->hire_date?->format('Y-m-d'),
            'phone' => $user->phone,
            'personal_email' => $user->personal_email,
            'address' => $user->address,
            'bio' => $user->bio,
            'avatar_path' => $user->avatar_path,
            'cv_path' => $user->cv_path,
            'preferences' => [
                'locale' => (string) ($preferences['locale'] ?? 'fr'),
                'timezone' => (string) ($preferences['timezone'] ?? 'Africa/Abidjan'),
                'email_notifications' => (bool) ($preferences['email_notifications'] ?? true),
                'digest_frequency' => (string) ($preferences['digest_frequency'] ?? 'daily'),
                'sync' => [
                    'enabled' => (bool) ($syncPreferences['enabled'] ?? true),
                    'interval_minutes' => (int) ($syncPreferences['interval_minutes'] ?? (int) config('sync.default_interval_minutes', 15)),
                    'conflict_policy' => (string) ($syncPreferences['conflict_policy'] ?? 'keep_both'),
                    'download_on_metered' => (bool) ($syncPreferences['download_on_metered'] ?? false),
                    'auto_start' => (bool) ($syncPreferences['auto_start'] ?? true),
                ],
            ],
            'inactivity_timeout_minutes' => (int) ($user->inactivity_timeout_minutes ?: config('session.lifetime', 120)),
            'current_password' => null,
            'new_password' => null,
            'new_password_confirmation' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Photo et documents')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_path')
                            ->label('Photo de profil')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('users/avatars')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('JPG/PNG, 2 Mo max.'),

                        Forms\Components\FileUpload::make('cv_path')
                            ->label('Charger mon CV')
                            ->disk('public')
                            ->directory('users/cv')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->downloadable()
                            ->openable()
                            ->maxSize(10240)
                            ->helperText('PDF, DOC ou DOCX, 10 Mo max.'),
                    ]),

                Section::make('Infos personnelles')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Adresse e-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->rule(fn () => Rule::unique('users', 'email')->ignore((int) Auth::id())),

                        Forms\Components\Select::make('departement_id')
                            ->label('Département')
                            ->options(fn () => Departement::query()->orderBy('nom')->pluck('nom', 'id')->toArray())
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('poste')
                            ->label('Poste / Fonction')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\DatePicker::make('hire_date')
                            ->label('Date d\'embauche')
                            ->native(false)
                            ->nullable(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(30)
                            ->nullable(),

                        Forms\Components\TextInput::make('personal_email')
                            ->label('E-mail personnel')
                            ->email()
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\Textarea::make('bio')
                            ->label('Bio / A propos')
                            ->rows(3)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                Section::make('Préférences')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('preferences.locale')
                            ->label('Langue')
                            ->options([
                                'fr' => 'Français',
                                'en' => 'English',
                            ])
                            ->default('fr')
                            ->required(),

                        Forms\Components\Select::make('preferences.timezone')
                            ->label('Fuseau horaire')
                            ->options([
                                'Africa/Abidjan' => 'Africa/Abidjan',
                                'Europe/Paris' => 'Europe/Paris',
                                'UTC' => 'UTC',
                            ])
                            ->default('Africa/Abidjan')
                            ->required(),

                        Forms\Components\Toggle::make('preferences.email_notifications')
                            ->label('Notifications e-mail')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Select::make('preferences.digest_frequency')
                            ->label('Résumé e-mail')
                            ->options([
                                'realtime' => 'Temps réel',
                                'daily' => 'Quotidien',
                                'weekly' => 'Hebdomadaire',
                            ])
                            ->default('daily')
                            ->required(),

                        Forms\Components\Toggle::make('preferences.sync.enabled')
                            ->label('Activer la synchronisation automatique')
                            ->default(true)
                            ->inline(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('preferences.sync.interval_minutes')
                            ->label('Fréquence de synchronisation (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->default((int) config('sync.default_interval_minutes', 15))
                            ->required(),

                        Forms\Components\Select::make('preferences.sync.conflict_policy')
                            ->label('Résolution des conflits')
                            ->options([
                                'keep_both' => 'Conserver les deux versions',
                                'server_wins' => 'Version serveur prioritaire',
                                'local_wins' => 'Version locale prioritaire',
                            ])
                            ->default('keep_both')
                            ->required(),

                        Forms\Components\Toggle::make('preferences.sync.download_on_metered')
                            ->label('Autoriser la sync sur connexion limitée')
                            ->default(false)
                            ->inline(false),

                        Forms\Components\Toggle::make('preferences.sync.auto_start')
                            ->label('Lancer la sync au démarrage du poste')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('Sécurité')
                    ->description('Modifiez votre mot de passe et gérez la déconnexion automatique après inactivité.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('password_rotation_status')
                            ->label('Expiration du mot de passe')
                            ->content(fn (): Htmlable => $this->renderPasswordRotationStatusHtml())
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('inactivity_timeout_minutes')
                            ->label('Déconnexion après inactivité (minutes)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(480)
                            ->required()
                            ->helperText('5 à 480 minutes. Recommandé: 30 à 120.'),

                        Forms\Components\Placeholder::make('security_hint')
                            ->label('Conseil')
                            ->content('Utilisez un mot de passe long et unique. Évitez la réutilisation.'),

                        Forms\Components\TextInput::make('current_password')
                            ->label('Mot de passe actuel')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('new_password')
                            ->label('Nouveau mot de passe')
                            ->password()
                            ->revealable()
                            ->rule(Password::min(12)->letters()->mixedCase()->numbers()->symbols())
                            ->helperText('Minimum 12 caractères avec majuscule, minuscule, chiffre et symbole.')
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Confirmer le nouveau mot de passe')
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                    ]),

                Section::make('Sessions actives')
                    ->description('Sessions connectées à votre compte. Vous pouvez fermer toutes les autres sessions.')
                    ->schema([
                        Forms\Components\Placeholder::make('active_sessions_list')
                            ->label('Appareils connectés')
                            ->content(fn (): Htmlable => new \Illuminate\Support\HtmlString($this->renderActiveSessionsHtml())),
                    ]),

                Section::make('Synchronisation ordinateur')
                    ->description('Connectez votre client desktop (Windows, macOS, Linux) via un jeton personnel de synchronisation.')
                    ->schema([
                        Forms\Components\Placeholder::make('sync_resources_downloads')
                            ->label('Téléchargements client desktop')
                            ->content(fn (): Htmlable => new \Illuminate\Support\HtmlString($this->renderSyncResourcesHtml())),

                        Forms\Components\Placeholder::make('sync_devices_list')
                            ->label('Appareils de synchronisation')
                            ->content(fn (): Htmlable => new \Illuminate\Support\HtmlString($this->renderSyncDevicesHtml())),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_sync_device_token')
                ->label('Générer un jeton de synchronisation')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('info')
                ->modalHeading('Nouveau jeton de synchronisation')
                ->modalDescription('Créez un accès pour votre application desktop de synchronisation.')
                ->form([
                    Forms\Components\TextInput::make('device_name')
                        ->label('Nom de l\'appareil')
                        ->placeholder('Ex: PC Bureau - Alice')
                        ->required()
                        ->maxLength(120),
                    Forms\Components\Select::make('platform')
                        ->label('Système')
                        ->options([
                            'windows' => 'Windows',
                            'macos' => 'macOS',
                            'linux' => 'Linux',
                            'other' => 'Autre',
                        ])
                        ->default('windows')
                        ->required(),
                    Forms\Components\TextInput::make('client_version')
                        ->label('Version client (optionnel)')
                        ->maxLength(60)
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    /** @var User|null $user */
                    $user = Auth::user();

                    if (! $user instanceof User) {
                        return;
                    }

                    $issued = SyncDevice::issueForUser(
                        $user,
                        (string) ($data['device_name'] ?? 'Poste utilisateur'),
                        (string) ($data['platform'] ?? 'unknown'),
                        (string) ($data['client_version'] ?? '')
                    );

                    $this->latestSyncToken = (string) $issued['plain_token'];
                    $this->activeSyncDevices = $this->resolveSyncDevices($user->fresh());

                    Notification::make()
                        ->title('Jeton de synchronisation généré')
                        ->body('Conservez ce jeton: ' . $this->latestSyncToken . ' (il ne sera plus affiché).')
                        ->success()
                        ->send();
                }),

            Action::make('revoke_sync_devices')
                ->label('Révoquer tous les jetons sync')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Révoquer les accès de synchronisation')
                ->modalDescription('Tous les clients desktop devront être reconnectés avec un nouveau jeton.')
                ->action(function (): void {
                    /** @var User|null $user */
                    $user = Auth::user();

                    if (! $user instanceof User) {
                        return;
                    }

                    $user->syncDevices()->update(['is_active' => false]);
                    $this->latestSyncToken = null;
                    $this->activeSyncDevices = $this->resolveSyncDevices($user->fresh());

                    Notification::make()
                        ->title('Jetons de synchronisation révoqués')
                        ->success()
                        ->send();
                }),

            Action::make('logout_other_devices')
                ->label('Déconnecter les autres appareils')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Déconnecter les autres appareils')
                ->modalDescription('Entrez votre mot de passe actuel pour fermer toutes les autres sessions actives.')
                ->form([
                    Forms\Components\TextInput::make('current_password')
                        ->label('Mot de passe actuel')
                        ->password()
                        ->revealable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var User|null $user */
                    $user = Auth::user();

                    if (! $user instanceof User) {
                        return;
                    }

                    if (! Hash::check((string) ($data['current_password'] ?? ''), (string) $user->password)) {
                        Notification::make()
                            ->title('Mot de passe incorrect')
                            ->danger()
                            ->send();

                        return;
                    }

                    Auth::logoutOtherDevices((string) $data['current_password']);

                    if (config('session.driver') === 'database') {
                        DB::table((string) config('session.table', 'sessions'))
                            ->where('user_id', $user->id)
                            ->where('id', '!=', session()->getId())
                            ->delete();
                    }

                    $this->activeSessions = $this->resolveActiveSessions($user->fresh());

                    Notification::make()
                        ->title('Autres sessions déconnectées')
                        ->success()
                        ->send();
                }),

            Action::make('save')
                ->label('Enregistrer le profil')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        $state = $this->form->getState();
        $oldAvatar = $user->avatar_path;
        $oldCv = $user->cv_path;
        $existingPreferences = is_array($user->preferences) ? $user->preferences : [];

        $newPassword = trim((string) ($state['new_password'] ?? ''));

        if ($newPassword !== '') {
            $confirmation = (string) ($state['new_password_confirmation'] ?? '');

            if ($newPassword !== $confirmation) {
                $this->addError('data.new_password_confirmation', 'La confirmation du nouveau mot de passe ne correspond pas.');

                return;
            }

            $currentPassword = (string) ($state['current_password'] ?? '');

            if (! Hash::check($currentPassword, (string) $user->password)) {
                $this->addError('data.current_password', 'Le mot de passe actuel est incorrect.');

                return;
            }

            $user->password = $newPassword;
            $user->last_password_changed_at = now();
        }

        $user->fill([
            'name' => $state['name'] ?? $user->name,
            'email' => $state['email'] ?? $user->email,
            'departement_id' => $state['departement_id'] ?? null,
            'poste' => $state['poste'] ?? null,
            'hire_date' => $state['hire_date'] ?? null,
            'phone' => $state['phone'] ?? null,
            'personal_email' => $state['personal_email'] ?? null,
            'address' => $state['address'] ?? null,
            'bio' => $state['bio'] ?? null,
            'avatar_path' => $state['avatar_path'] ?? null,
            'cv_path' => $state['cv_path'] ?? null,
            'preferences' => array_merge($existingPreferences, [
                'locale' => (string) data_get($state, 'preferences.locale', 'fr'),
                'timezone' => (string) data_get($state, 'preferences.timezone', 'Africa/Abidjan'),
                'email_notifications' => (bool) data_get($state, 'preferences.email_notifications', true),
                'digest_frequency' => (string) data_get($state, 'preferences.digest_frequency', 'daily'),
                'sync' => [
                    'enabled' => (bool) data_get($state, 'preferences.sync.enabled', true),
                    'interval_minutes' => min(120, max(1, (int) data_get($state, 'preferences.sync.interval_minutes', config('sync.default_interval_minutes', 15)))),
                    'conflict_policy' => (string) data_get($state, 'preferences.sync.conflict_policy', 'keep_both'),
                    'download_on_metered' => (bool) data_get($state, 'preferences.sync.download_on_metered', false),
                    'auto_start' => (bool) data_get($state, 'preferences.sync.auto_start', true),
                ],
            ]),
            'inactivity_timeout_minutes' => (int) ($state['inactivity_timeout_minutes'] ?? config('session.lifetime', 120)),
        ]);

        $user->save();

        if ($oldAvatar && $oldAvatar !== $user->avatar_path) {
            Storage::disk('public')->delete($oldAvatar);
        }

        if ($oldCv && $oldCv !== $user->cv_path) {
            Storage::disk('public')->delete($oldCv);
        }

        Notification::make()
            ->title('Profil mis à jour')
            ->success()
            ->send();

        $this->mount();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveSyncDevices(User $user): array
    {
        return SyncDevice::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['name', 'platform', 'client_version', 'is_active', 'last_synced_at', 'last_seen_at'])
            ->map(function (SyncDevice $device): array {
                return [
                    'name' => (string) $device->name,
                    'platform' => (string) $device->platform,
                    'client_version' => (string) ($device->client_version ?: 'n/a'),
                    'is_active' => (bool) $device->is_active,
                    'last_synced_at' => $device->last_synced_at?->diffForHumans() ?? 'Jamais',
                    'last_seen_at' => $device->last_seen_at?->diffForHumans() ?? 'Jamais',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveActiveSessions(User $user): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->limit(20)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function ($session): array {
                $isCurrent = (string) $session->id === (string) session()->getId();

                return [
                    'id' => (string) $session->id,
                    'ip_address' => (string) ($session->ip_address ?: '—'),
                    'user_agent' => Str::limit((string) ($session->user_agent ?: '—'), 90),
                    'last_activity' => now()->setTimestamp((int) $session->last_activity)->diffForHumans(),
                    'is_current' => $isCurrent,
                ];
            })
            ->values()
            ->all();
    }

    private function renderActiveSessionsHtml(): string
    {
        if ($this->activeSessions === []) {
            return '<span class="text-sm text-gray-600">Aucune session active détectée ou driver de session non-database.</span>';
        }

        $rows = '';

        foreach ($this->activeSessions as $session) {
            $badge = $session['is_current']
                ? '<span style="display:inline-block;padding:2px 8px;border-radius:9999px;background:#dcfce7;color:#166534;font-size:12px;">Session actuelle</span>'
                : '<span style="display:inline-block;padding:2px 8px;border-radius:9999px;background:#f3f4f6;color:#374151;font-size:12px;">Autre appareil</span>';

            $rows .= '<tr>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . e($session['ip_address']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . e($session['user_agent']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . e($session['last_activity']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . $badge . '</td>'
                . '</tr>';
        }

        return '<div style="overflow:auto;">'
            . '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            . '<thead><tr>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">IP</th>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Navigateur / Appareil</th>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Dernière activité</th>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Statut</th>'
            . '</tr></thead><tbody>'
            . $rows
            . '</tbody></table>'
            . '</div>';
    }

    private function renderSyncDevicesHtml(): string
    {
        $tokenNotice = $this->latestSyncToken
            ? '<div style="margin-bottom:8px;padding:10px;border-radius:8px;background:#eff6ff;color:#1d4ed8;font-size:12px;">'
                . 'Dernier jeton généré: <code style="font-weight:700;">' . e($this->latestSyncToken) . '</code>'
                . '</div>'
            : '';

        if ($this->activeSyncDevices === []) {
            return $tokenNotice . '<span class="text-sm text-gray-600">Aucun appareil de synchronisation enregistré.</span>';
        }

        $rows = '';

        foreach ($this->activeSyncDevices as $device) {
            $badge = $device['is_active']
                ? '<span style="display:inline-block;padding:2px 8px;border-radius:9999px;background:#dcfce7;color:#166534;font-size:12px;">Actif</span>'
                : '<span style="display:inline-block;padding:2px 8px;border-radius:9999px;background:#fef2f2;color:#991b1b;font-size:12px;">Révoqué</span>';

            $rows .= '<tr>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . e($device['name']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . e($device['platform']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . e($device['client_version']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . e($device['last_synced_at']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . e($device['last_seen_at']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e5e7eb;">' . $badge . '</td>'
                . '</tr>';
        }

        return $tokenNotice
            . '<div style="overflow:auto;">'
            . '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            . '<thead><tr>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Appareil</th>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Système</th>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Version client</th>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Dernière sync</th>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Dernière activité</th>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Statut</th>'
            . '</tr></thead><tbody>'
            . $rows
            . '</tbody></table>'
            . '</div>';
    }

    private function renderSyncResourcesHtml(): string
    {
        if (! $this->canAccessSyncResourceDownloads()) {
            return '<div style="font-size:13px;color:#92400e;background:#fffbeb;padding:10px;border-radius:8px;">'
                . 'Votre profil ne dispose pas des droits necessaires pour telecharger les installateurs desktop.'
                . '</div>';
        }

        $assets = [
            [
                'label' => 'Guide d\'installation (PDF)',
                'asset' => 'guide-desktop-pdf',
            ],
            [
                'label' => 'Package Windows (.zip)',
                'asset' => 'client-windows',
            ],
            [
                'label' => 'Package macOS (.tar.gz)',
                'asset' => 'client-macos',
            ],
        ];

        $rows = '';

        foreach ($assets as $item) {
            $meta = $this->resolveProfileDownloadAssetMeta((string) $item['asset']);

            if (! $meta['exists']) {
                $rows .= '<li style="margin:0 0 8px;">'
                    . '<span style="font-weight:600;">' . e((string) $item['label']) . '</span> '
                    . '<span style="display:inline-block;padding:2px 8px;border-radius:9999px;background:#f3f4f6;color:#374151;font-size:12px;">Non disponible</span>'
                    . '<span style="color:#6b7280;font-size:12px;"> - ' . e((string) $meta['message']) . '</span>'
                    . '</li>';

                continue;
            }

            $rows .= '<li style="margin:0 0 8px;">'
                . '<a href="' . e((string) route('profile.downloads.asset', ['asset' => $item['asset']])) . '"'
                . ' style="display:inline-block;padding:6px 12px;border-radius:8px;background:#2563eb;color:#fff;text-decoration:none;font-size:13px;">'
                . 'Télécharger'
                . '</a> '
                . '<span style="font-weight:600;">' . e((string) $item['label']) . '</span>'
                . '<span style="color:#6b7280;font-size:12px;"> (' . e((string) $meta['size']) . ')</span>'
                . '</li>';
        }

        return '<div style="font-size:13px;">'
            . '<p style="margin:0 0 10px;color:#374151;">Téléchargez ici le guide et les installateurs publiés par l\'équipe IT.</p>'
            . '<ul style="padding-left:18px;margin:0;">' . $rows . '</ul>'
            . '</div>';
    }

    private function canAccessSyncResourceDownloads(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('Super Admin')
            || $user->can('ged.documents.download')
            || $user->can('admin.users.view');
    }

    /**
     * @return array{exists: bool, size: string, message: string}
     */
    private function resolveProfileDownloadAssetMeta(string $asset): array
    {
        $map = [
            'guide-desktop-pdf' => base_path('docs/GUIDE_DISTRIBUTION_CLIENT_DESKTOP.pdf'),
            'client-windows' => base_path('desktop-client/dist/plussci-sync-client-windows.zip'),
            'client-macos' => base_path('desktop-client/dist/plussci-sync-client-macos.tar.gz'),
        ];

        if (! isset($map[$asset]) || ! is_file($map[$asset])) {
            return [
                'exists' => false,
                'size' => 'n/a',
                'message' => 'Fichier non publie sur le serveur',
            ];
        }

        if ($asset === 'client-macos' && ! $this->isRealMacosPackage($map[$asset])) {
            return [
                'exists' => false,
                'size' => 'n/a',
                'message' => 'Archive macOS invalide (ni bundle .app ni script source)',
            ];
        }

        $bytes = max(0, (int) filesize($map[$asset]));
        $kb = $bytes / 1024;

        return [
            'exists' => true,
            'size' => number_format($kb, 1, ',', ' ') . ' Ko',
            'message' => 'Disponible',
        ];
    }

    /**
     * Returns true if the .tar.gz contains either:
     *  - a compiled macOS app bundle (PLUSSCISyncClient.app/), built with PyInstaller on macOS
     *  - OR a source-only package (sync_client.py), which is the cross-platform distribution fallback
     */
    private function isRealMacosPackage(string $path): bool
    {
        $handle = @gzopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $needles = ['PLUSSCISyncClient.app/', 'sync_client.py'];
        $carry = '';

        while (! gzeof($handle)) {
            $chunk = gzread($handle, 4096);

            if ($chunk === false) {
                break;
            }

            $haystack = $carry . $chunk;

            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    gzclose($handle);

                    return true;
                }
            }

            $carry = substr($haystack, -64);
        }

        gzclose($handle);

        return false;
    }

    private function renderPasswordRotationStatusHtml(): Htmlable
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return new \Illuminate\Support\HtmlString('<span class="text-sm text-gray-600">Statut indisponible.</span>');
        }

        $rotationDays = (int) config('security.password_rotation_days', 90);

        if ($rotationDays <= 0) {
            return new \Illuminate\Support\HtmlString(
                '<div style="padding:10px;border-radius:8px;background:#eff6ff;color:#1d4ed8;font-size:13px;">'
                . 'Rotation du mot de passe désactivée.'
                . '</div>'
            );
        }

        if ($user->last_password_changed_at === null) {
            return new \Illuminate\Support\HtmlString(
                '<div style="padding:10px;border-radius:8px;background:#fef2f2;color:#991b1b;font-size:13px;">'
                . 'Mot de passe expiré. Aucun historique de changement détecté.'
                . '</div>'
            );
        }

        $expiresAt = $user->last_password_changed_at->copy()->addDays($rotationDays);
        $remainingDays = (int) now()->startOfDay()->diffInDays($expiresAt->startOfDay(), false);

        if ($remainingDays < 0) {
            return new \Illuminate\Support\HtmlString(
                '<div style="padding:10px;border-radius:8px;background:#fef2f2;color:#991b1b;font-size:13px;">'
                . 'Mot de passe expiré depuis ' . e(abs($remainingDays)) . ' jour(s). Veuillez le changer.'
                . '</div>'
            );
        }

        if ($remainingDays <= 7) {
            return new \Illuminate\Support\HtmlString(
                '<div style="padding:10px;border-radius:8px;background:#fffbeb;color:#92400e;font-size:13px;">'
                . 'Mot de passe expire dans ' . e($remainingDays) . ' jour(s) (le ' . e($expiresAt->format('d/m/Y')) . ').'
                . '</div>'
            );
        }

        return new \Illuminate\Support\HtmlString(
            '<div style="padding:10px;border-radius:8px;background:#ecfdf5;color:#065f46;font-size:13px;">'
            . 'Mot de passe valide. Expiration dans ' . e($remainingDays) . ' jour(s) (le ' . e($expiresAt->format('d/m/Y')) . ').'
            . '</div>'
        );
    }
}
