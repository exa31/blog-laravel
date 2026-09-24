<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StructuredLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_http_requests_are_logged_with_structured_context(): void
    {
        Log::shouldReceive('info')
            ->atLeast()
            ->once()
            ->withArgs(function ($message, $context = []) {
                return str_contains($message, 'HTTP GET /') &&
                    isset($context['type']) && $context['type'] === 'http_access' &&
                    isset($context['method']) && $context['method'] === 'GET' &&
                    isset($context['status']) && $context['status'] === 200 &&
                    isset($context['duration_ms']);
            });

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_health_check_probes_are_excluded_from_access_logging(): void
    {
        Log::shouldReceive('info')
            ->withArgs(function ($message) {
                return str_contains($message, '/up');
            })
            ->never();

        $response = $this->get('/up');
        $response->assertStatus(200);
    }
}
