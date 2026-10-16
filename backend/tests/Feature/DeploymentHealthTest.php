<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeploymentHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_a_migrated_database_as_ready(): void
    {
        $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);
    }

    public function test_health_rejects_a_database_without_session_tables(): void
    {
        config(['app.debug' => false]);
        Schema::drop('sessions');

        $this->getJson('/up')->assertServerError()->assertExactJson(['status' => 'down']);
    }
}
