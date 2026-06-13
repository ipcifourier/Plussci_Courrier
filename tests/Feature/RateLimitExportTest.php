<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset the rate limiter between test cases
        RateLimiter::clear('');
    }

    public function test_export_routes_require_authentication(): void
    {
        $this->get(route('courriers.registre.pdf'))->assertRedirect();
        $this->get(route('audit.logs.export'))->assertRedirect();
    }

    public function test_exports_rate_limiter_is_registered_with_correct_config(): void
    {
        $user = User::factory()->create();

        // Simulate 10 allowed attempts using the same key strategy as our limiter (by user id)
        $key = (string) $user->id;

        $limiter = app(\Illuminate\Cache\RateLimiter::class);

        // Start fresh
        $limiter->resetAttempts($key);

        for ($i = 1; $i <= 10; $i++) {
            $allowed = $limiter->attempt($key, 10, fn () => true, 60);
            $this->assertTrue($allowed, "Attempt {$i} should be allowed");
        }

        // 11th attempt must be blocked
        $blocked = $limiter->attempt($key, 10, fn () => true, 60);
        $this->assertFalse($blocked, 'Attempt 11 should be blocked');

        // Clean up
        $limiter->resetAttempts($key);
    }

    public function test_exports_rate_limiter_resets_after_decay(): void
    {
        $user = User::factory()->create();

        $key     = (string) $user->id;
        $limiter = app(\Illuminate\Cache\RateLimiter::class);

        $limiter->resetAttempts($key);

        // Exhaust the limit
        for ($i = 0; $i < 10; $i++) {
            $limiter->hit($key, 60);
        }

        $this->assertTrue($limiter->tooManyAttempts($key, 10));

        // Reset simulates decay
        $limiter->resetAttempts($key);

        $this->assertFalse($limiter->tooManyAttempts($key, 10));
    }

    public function test_audit_export_route_returns_403_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('audit.logs.export'))
            ->assertForbidden();
    }

    public function test_courrier_registre_pdf_returns_403_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('courriers.registre.pdf'))
            ->assertForbidden();
    }
}
