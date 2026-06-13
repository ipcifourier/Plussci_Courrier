<?php

namespace Tests\Feature;

use App\Http\Controllers\CourrierRegistrePdfController;
use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CourrierRegistrePdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_pdf_export_route(): void
    {
        $response = $this->get(route('courriers.registre.pdf'));

        $response->assertRedirect();
    }

    public function test_user_without_export_permission_gets_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('courriers.registre.pdf'));

        $response->assertForbidden();
    }

    public function test_export_signed_only_sends_only_signed_records_to_pdf_view(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('courriers.export', 'web');
        $user->givePermissionTo('courriers.export');

        $correspondant = Correspondant::query()->create(['nom_structure' => 'Org Export']);

        Courrier::query()->create([
            'type' => 'Sortant',
            'reference' => 'PDF-0001',
            'date_reception_envoi' => now()->toDateString(),
            'objet' => 'Signé',
            'priorite' => 'Normale',
            'statut' => 'Traité',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id' => $correspondant->id,
            'user_id' => $user->id,
            'requires_approval' => false,
            'approval_status' => 'not_required',
            'signed_by' => $user->id,
            'signed_at' => now(),
        ]);

        Courrier::query()->create([
            'type' => 'Sortant',
            'reference' => 'PDF-0002',
            'date_reception_envoi' => now()->toDateString(),
            'objet' => 'Non signé',
            'priorite' => 'Normale',
            'statut' => 'Traité',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id' => $correspondant->id,
            'user_id' => $user->id,
            'requires_approval' => false,
            'approval_status' => 'not_required',
            'signed_by' => null,
            'signed_at' => null,
        ]);

        $capturedData = null;

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use (&$capturedData): bool {
                $capturedData = $data;

                return $view === 'pdf.courriers-registre';
            })
            ->andReturnSelf();

        Pdf::shouldReceive('setPaper')
            ->once()
            ->with('a4', 'landscape')
            ->andReturnSelf();

        Pdf::shouldReceive('download')
            ->once()
            ->andReturn(response('pdf-binary', 200, ['content-type' => 'application/pdf']));

        $authorizedUser = \Mockery::mock(User::class)->makePartial();
        $authorizedUser->shouldReceive('hasPermissionTo')
            ->with('courriers.export')
            ->andReturn(true);
        $authorizedUser->id = $user->id;

        $request = Request::create(route('courriers.registre.pdf', ['signed_only' => 1]), 'GET', [
            'signed_only' => 1,
        ]);
        $request->setUserResolver(fn () => $authorizedUser);

        $response = app(CourrierRegistrePdfController::class)($request);

        $this->assertSame(200, $response->status());
        $this->assertNotNull($capturedData);
        $this->assertTrue($capturedData['filters']['signed_only']);
        $this->assertCount(1, $capturedData['courriers']);
        $this->assertEquals('PDF-0001', $capturedData['courriers']->first()->reference);
    }
}
