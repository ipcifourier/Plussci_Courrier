<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\CourrierSlaSettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class CourrierSlaSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $slug = 'courriers-sla';

    protected static ?string $navigationLabel = 'SLA Courriers';

    protected static ?string $title = 'SLA Courriers & Workflows';

    protected static ?int $navigationSort = 24;

    protected string $view = 'filament.pages.courrier-sla-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Courriers';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('admin.roles.manage')
        );
    }

    public function mount(CourrierSlaSettingsService $settings): void
    {
        $sla = $settings->config();

        $this->form->fill([
            'task_reminder_days_before' => implode(',', $sla['task_reminder_days_before']),
            'imputation_reminder_days_before' => implode(',', $sla['imputation_reminder_days_before']),
            'task_escalation_after_overdue_days' => $sla['task_escalation_after_overdue_days'],
            'imputation_escalation_after_overdue_days' => $sla['imputation_escalation_after_overdue_days'],
            'enable_task_escalation' => $sla['enable_task_escalation'],
            'enable_imputation_escalation' => $sla['enable_imputation_escalation'],
            'send_overdue_daily' => $sla['send_overdue_daily'],
            'workflow_default_sla_hours' => $sla['workflow_default_sla_hours'],
            'workflow_sla_by_type' => $sla['workflow_sla_by_type'],
            'workflow_sla_by_priority' => $sla['workflow_sla_by_priority'],
            'workflow_sla_by_confidentiality' => $sla['workflow_sla_by_confidentiality'],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Rappels SLA')
                    ->description('Jours avant echeance: format CSV. Exemple 3,1,0 = J-3, J-1, Jour J.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('task_reminder_days_before')
                            ->label('Taches - rappels (jours avant echeance)')
                            ->required()
                            ->helperText('Ex: 3,1,0'),

                        Forms\Components\TextInput::make('imputation_reminder_days_before')
                            ->label('Imputations - rappels (jours avant echeance)')
                            ->required()
                            ->helperText('Ex: 3,1,0'),

                        Forms\Components\Toggle::make('send_overdue_daily')
                            ->label('Envoyer un rappel quotidien en cas de retard')
                            ->default(true)
                            ->inline(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Escalade')
                    ->description('Escalade automatique vers le responsable quand le retard atteint le seuil configure.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('enable_task_escalation')
                            ->label('Activer escalade taches')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\TextInput::make('task_escalation_after_overdue_days')
                            ->label('Escalade taches apres X jours de retard')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\Toggle::make('enable_imputation_escalation')
                            ->label('Activer escalade imputations')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\TextInput::make('imputation_escalation_after_overdue_days')
                            ->label('Escalade imputations apres X jours de retard')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),

                Section::make('Workflow documents (global)')
                    ->description('Regles SLA appliquees automatiquement quand une etape conserve le SLA standard (24h).')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('workflow_default_sla_hours')
                            ->label('SLA workflow par defaut (heures)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(720)
                            ->required(),

                        Forms\Components\KeyValue::make('workflow_sla_by_priority')
                            ->label('SLA par priorite')
                            ->helperText('Ex: urgente => 8, haute => 16')
                            ->keyLabel('Priorite')
                            ->valueLabel('Heures')
                            ->reorderable(),

                        Forms\Components\KeyValue::make('workflow_sla_by_confidentiality')
                            ->label('SLA par confidentialite')
                            ->helperText('Ex: confidentiel => 12, personnel => 24')
                            ->keyLabel('Niveau')
                            ->valueLabel('Heures')
                            ->reorderable(),

                        Forms\Components\KeyValue::make('workflow_sla_by_type')
                            ->label('SLA par type de document')
                            ->helperText('Ex: contrat => 48, facture => 24')
                            ->keyLabel('Type')
                            ->valueLabel('Heures')
                            ->reorderable(),
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
        $state = $this->form->getState();
        $settings = app(CourrierSlaSettingsService::class);

        $settings->set('courriers.sla', [
            'task_reminder_days_before' => $settings->parseDaysCsv((string) ($state['task_reminder_days_before'] ?? '3,1,0')),
            'imputation_reminder_days_before' => $settings->parseDaysCsv((string) ($state['imputation_reminder_days_before'] ?? '3,1,0')),
            'task_escalation_after_overdue_days' => max(1, (int) ($state['task_escalation_after_overdue_days'] ?? 2)),
            'imputation_escalation_after_overdue_days' => max(1, (int) ($state['imputation_escalation_after_overdue_days'] ?? 1)),
            'enable_task_escalation' => (bool) ($state['enable_task_escalation'] ?? true),
            'enable_imputation_escalation' => (bool) ($state['enable_imputation_escalation'] ?? true),
            'send_overdue_daily' => (bool) ($state['send_overdue_daily'] ?? true),
            'workflow_default_sla_hours' => max(1, (int) ($state['workflow_default_sla_hours'] ?? 24)),
            'workflow_sla_by_type' => $settings->normalizeHoursMap((array) ($state['workflow_sla_by_type'] ?? [])),
            'workflow_sla_by_priority' => $settings->normalizeHoursMap((array) ($state['workflow_sla_by_priority'] ?? [])),
            'workflow_sla_by_confidentiality' => $settings->normalizeHoursMap((array) ($state['workflow_sla_by_confidentiality'] ?? [])),
        ]);

        Notification::make()
            ->title('SLA Courriers & Workflows enregistres')
            ->success()
            ->send();
    }
}
