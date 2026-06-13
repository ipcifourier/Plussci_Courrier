<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * AD5 — Page de paramètres système (Super Admin uniquement).
 */
class SystemSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    protected static ?string $slug = 'system-settings';

    protected static ?string $navigationLabel = 'Paramètres système';

    protected static ?string $title = 'Paramètres système';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasAnyRole(['Super Admin', 'Admin'])
            || $user->hasPermissionTo('admin.settings.manage')
        );
    }

    /** Lecture d'une clé AppSetting avec valeur castée. */
    private function get(string $key, mixed $default = null): mixed
    {
        return AppSetting::where('key', $key)->first()?->value ?? $default;
    }

    public function mount(): void
    {
        $this->form->fill([
            // Organisation
            'app_name'              => $this->get('system.app_name', config('app.name', 'PlussCI')),
            'organization_name'     => $this->get('system.organization_name', ''),
            'timezone'              => $this->get('system.timezone', config('app.timezone', 'Africa/Abidjan')),
            'locale'                => $this->get('system.locale', config('app.locale', 'fr')),

            // Sécurité
            'password_rotation_days' => (int) $this->get('system.password_rotation_days', config('security.password_rotation_days', 90)),
            'session_lifetime'       => (int) $this->get('system.session_lifetime', (int) config('session.lifetime', 120)),
            'max_login_attempts'     => (int) $this->get('system.max_login_attempts', 5),

            // Notifications & E-mail
            'mail_from_address'      => $this->get('system.mail_from_address', config('mail.from.address', '')),
            'mail_from_name'         => $this->get('system.mail_from_name', config('mail.from.name', '')),
            'notifications_enabled'  => (bool) $this->get('system.notifications_enabled', true),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $timezones = collect(\DateTimeZone::listIdentifiers())
            ->mapWithKeys(fn (string $tz): array => [$tz => str_replace('_', ' ', $tz)])
            ->toArray();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Organisation')
                    ->description('Informations générales de l\'application et de l\'organisation.')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('app_name')
                            ->label('Nom de l\'application')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('organization_name')
                            ->label('Nom de l\'organisation')
                            ->maxLength(150)
                            ->nullable(),

                        Forms\Components\Select::make('timezone')
                            ->label('Fuseau horaire')
                            ->options($timezones)
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('locale')
                            ->label('Langue de l\'interface')
                            ->options([
                                'fr' => 'Français',
                                'en' => 'English',
                            ])
                            ->required()
                            ->native(false),
                    ]),

                Section::make('Sécurité')
                    ->description('Politiques de connexion et de gestion des sessions.')
                    ->icon('heroicon-o-shield-check')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('password_rotation_days')
                            ->label('Rotation du mot de passe (jours)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(365)
                            ->required()
                            ->helperText('0 = rotation désactivée'),

                        Forms\Components\TextInput::make('session_lifetime')
                            ->label('Durée de session (minutes)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(1440)
                            ->required()
                            ->helperText('1440 min = 24 heures'),

                        Forms\Components\TextInput::make('max_login_attempts')
                            ->label('Tentatives de connexion max')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(20)
                            ->required(),
                    ]),

                Section::make('Notifications & E-mail')
                    ->description('Configuration de l\'adresse d\'expédition des e-mails système.')
                    ->icon('heroicon-o-envelope')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('Adresse e-mail expéditeur')
                            ->email()
                            ->maxLength(150)
                            ->nullable(),

                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('Nom affiché expéditeur')
                            ->maxLength(100)
                            ->nullable(),

                        Forms\Components\Toggle::make('notifications_enabled')
                            ->label('Activer les notifications par e-mail')
                            ->helperText('Désactiver stoppe l\'envoi de tous les e-mails de notification.')
                            ->inline(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $map = [
            'system.app_name'               => (string) ($data['app_name'] ?? ''),
            'system.organization_name'       => (string) ($data['organization_name'] ?? ''),
            'system.timezone'                => (string) ($data['timezone'] ?? 'Africa/Abidjan'),
            'system.locale'                  => (string) ($data['locale'] ?? 'fr'),
            'system.password_rotation_days'  => (int) ($data['password_rotation_days'] ?? 90),
            'system.session_lifetime'        => (int) ($data['session_lifetime'] ?? 120),
            'system.max_login_attempts'      => (int) ($data['max_login_attempts'] ?? 5),
            'system.mail_from_address'       => (string) ($data['mail_from_address'] ?? ''),
            'system.mail_from_name'          => (string) ($data['mail_from_name'] ?? ''),
            'system.notifications_enabled'   => (bool) ($data['notifications_enabled'] ?? true),
        ];

        foreach ($map as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Notification::make()
            ->title('Paramètres enregistrés.')
            ->success()
            ->send();
    }
}
