<?php

namespace Tests\Feature;

use App\Http\Controllers\AuditLogExportController;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditLogExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_audit_export_route(): void
    {
        $response = $this->get(route('audit.logs.export'));

        $response->assertRedirect();
    }

    public function test_user_without_audit_export_permission_gets_forbidden(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get(route('audit.logs.export'));

        $response->assertForbidden();
    }

    public function test_export_csv_applies_action_filter(): void
    {
        Permission::findOrCreate('audit.export', 'web');

        /** @var User $user */
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('audit.export');

        AuditLog::query()->create([
            'actor_id' => $user->id,
            'action' => 'courriers.sign',
            'entity_type' => 'App\\Models\\Courrier',
            'entity_id' => 1,
        ]);

        AuditLog::query()->create([
            'actor_id' => $user->id,
            'action' => 'courriers.approval.approve',
            'entity_type' => 'App\\Models\\Courrier',
            'entity_id' => 2,
        ]);

        $request = Request::create(route('audit.logs.export', [
            'format' => 'csv',
            'action' => 'courriers.sign',
        ]), 'GET', [
            'format' => 'csv',
            'action' => 'courriers.sign',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app(AuditLogExportController::class)($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        $this->assertStringContainsString('courriers.sign', $content);
        $this->assertStringNotContainsString('courriers.approval.approve', $content);
    }

    public function test_export_xlsx_returns_expected_content_type(): void
    {
        Permission::findOrCreate('audit.export', 'web');

        /** @var User $user */
        $user = User::factory()->create();
        $user->givePermissionTo('audit.export');

        AuditLog::query()->create([
            'actor_id' => $user->id,
            'action' => 'courriers.export.pdf',
        ]);

        $request = Request::create(route('audit.logs.export', [
            'format' => 'xlsx',
        ]), 'GET', [
            'format' => 'xlsx',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app(AuditLogExportController::class)($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }
}
