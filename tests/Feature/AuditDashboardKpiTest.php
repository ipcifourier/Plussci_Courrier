<?php

namespace Tests\Feature;

use App\Filament\Widgets\AuditActivityChartWidget;
use App\Filament\Widgets\AuditStatsOverview;
use App\Filament\Widgets\AuditTopActorsWidget;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditDashboardKpiTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    //  AuditStatsOverview
    // ─────────────────────────────────────────────────────────────

    public function test_stats_overview_returns_five_stats(): void
    {
        $stats = $this->getStats();

        $this->assertCount(5, $stats);
    }

    public function test_stats_overview_today_count_reflects_db(): void
    {
        // Two logs today, one yesterday
        AuditLog::query()->create(['action' => 'login', 'created_at' => now()]);
        AuditLog::query()->create(['action' => 'update', 'created_at' => now()]);
        AuditLog::query()->create(['action' => 'delete', 'created_at' => now()->subDay()]);

        $stats = $this->getStats();

        // Stat index 1 = "Aujourd'hui"
        $todayStat = $stats[1];
        $this->assertStringContainsString('2', (string) $todayStat->getValue());
    }

    public function test_stats_overview_anomaly_stat_is_zero_when_clean(): void
    {
        // 5 actions from same IP — below threshold (>30)
        for ($i = 0; $i < 5; $i++) {
            AuditLog::query()->create([
                'action'     => 'view',
                'ip_address' => '192.168.1.1',
                'created_at' => now()->subHours(2),
            ]);
        }

        $stats = $this->getStats();

        // Stat index 4 = "Anomalies détectées"
        $anomalyStat = $stats[4];
        $this->assertEquals('0', (string) $anomalyStat->getValue());
    }

    public function test_stats_overview_anomaly_stat_detects_suspicious_ip(): void
    {
        // 35 actions from same IP within 24 h (above threshold of 30)
        for ($i = 0; $i < 35; $i++) {
            AuditLog::query()->create([
                'action'     => 'view',
                'ip_address' => '10.0.0.99',
                'created_at' => now()->subHours(1),
            ]);
        }

        $stats = $this->getStats();

        $anomalyStat = $stats[4];
        $this->assertEquals('1', (string) $anomalyStat->getValue());
    }

    public function test_stats_overview_active_actors_counts_distinct_users(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // userA: 3 actions this month
        for ($i = 0; $i < 3; $i++) {
            AuditLog::query()->create([
                'actor_id'   => $userA->id,
                'action'     => 'update',
                'created_at' => now()->startOfMonth()->addDays($i + 1),
            ]);
        }
        // userB: 1 action this month
        AuditLog::query()->create([
            'actor_id'   => $userB->id,
            'action'     => 'view',
            'created_at' => now(),
        ]);
        // system action (no actor) — should NOT count
        AuditLog::query()->create([
            'actor_id'   => null,
            'action'     => 'system.cleanup',
            'created_at' => now(),
        ]);

        $stats = $this->getStats();

        // Stat index 3 = "Acteurs actifs (mois)"
        $actorStat = $stats[3];
        $this->assertEquals('2', (string) $actorStat->getValue());
    }

    // ─────────────────────────────────────────────────────────────
    //  AuditActivityChartWidget
    // ─────────────────────────────────────────────────────────────

    public function test_activity_chart_returns_fourteen_day_labels(): void
    {
        $data = $this->getChartData(AuditActivityChartWidget::class);

        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(14, $data['labels']);
    }

    public function test_activity_chart_type_is_line(): void
    {
        $widget = new AuditActivityChartWidget();
        $method = new ReflectionMethod(AuditActivityChartWidget::class, 'getType');

        $this->assertEquals('line', $method->invoke($widget));
    }

    public function test_activity_chart_counts_todays_actions_correctly(): void
    {
        AuditLog::query()->create(['action' => 'login', 'created_at' => now()]);
        AuditLog::query()->create(['action' => 'update', 'created_at' => now()]);

        $data = $this->getChartData(AuditActivityChartWidget::class);

        $todayCount = last($data['datasets'][0]['data']);
        $this->assertEquals(2, $todayCount);
    }

    // ─────────────────────────────────────────────────────────────
    //  AuditTopActorsWidget
    // ─────────────────────────────────────────────────────────────

    public function test_top_actors_chart_type_is_doughnut(): void
    {
        $widget = new AuditTopActorsWidget();
        $method = new ReflectionMethod(AuditTopActorsWidget::class, 'getType');

        $this->assertEquals('doughnut', $method->invoke($widget));
    }

    public function test_top_actors_chart_labels_actor_by_name(): void
    {
        $user = User::factory()->create(['name' => 'Alice Dupont']);

        for ($i = 0; $i < 5; $i++) {
            AuditLog::query()->create([
                'actor_id'   => $user->id,
                'action'     => 'view',
                'created_at' => now(),
            ]);
        }

        $data = $this->getChartData(AuditTopActorsWidget::class);

        $this->assertContains('Alice Dupont', $data['labels']);
    }

    public function test_top_actors_chart_labels_null_actor_as_system(): void
    {
        AuditLog::query()->create([
            'actor_id'   => null,
            'action'     => 'system.init',
            'created_at' => now(),
        ]);

        $data = $this->getChartData(AuditTopActorsWidget::class);

        $this->assertContains('Système', $data['labels']);
    }

    public function test_top_actors_chart_limits_to_five_entries(): void
    {
        // Create 7 distinct users each with 1 action
        for ($i = 0; $i < 7; $i++) {
            $user = User::factory()->create();
            AuditLog::query()->create([
                'actor_id'   => $user->id,
                'action'     => 'view',
                'created_at' => now(),
            ]);
        }

        $data = $this->getChartData(AuditTopActorsWidget::class);

        $this->assertLessThanOrEqual(5, count($data['labels']));
    }

    // ─────────────────────────────────────────────────────────────
    //  Visibility / canView
    // ─────────────────────────────────────────────────────────────

    public function test_audit_widgets_hidden_for_user_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertFalse(AuditStatsOverview::canView());
        $this->assertFalse(AuditActivityChartWidget::canView());
        $this->assertFalse(AuditTopActorsWidget::canView());
    }

    public function test_audit_widgets_visible_for_user_with_audit_view(): void
    {
        Permission::findOrCreate('audit.view', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('audit.view');

        $this->actingAs($user);

        $this->assertTrue(AuditStatsOverview::canView());
        $this->assertTrue(AuditActivityChartWidget::canView());
        $this->assertTrue(AuditTopActorsWidget::canView());
    }

    public function test_audit_widgets_visible_for_super_admin(): void
    {
        $role = Role::findOrCreate('Super Admin', 'web');

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        $this->assertTrue(AuditStatsOverview::canView());
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array<int, \Filament\Widgets\StatsOverviewWidget\Stat>
     */
    private function getStats(): array
    {
        $widget = new AuditStatsOverview();
        $method = new ReflectionMethod(AuditStatsOverview::class, 'getStats');

        return $method->invoke($widget);
    }

    /**
     * @param  class-string  $widgetClass
     * @return array<string, mixed>
     */
    private function getChartData(string $widgetClass): array
    {
        $widget = new $widgetClass();
        $method = new ReflectionMethod($widgetClass, 'getData');

        return $method->invoke($widget);
    }
}
