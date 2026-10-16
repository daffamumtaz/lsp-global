<?php

namespace Tests\Feature;

use App\Models\CertificationScheme;
use App\Models\OrganizationPosition;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_insert_uses_an_id_above_imported_records(): void
    {
        $maximumId = OrganizationPosition::max('id');
        $position = OrganizationPosition::factory()->create();

        $this->assertGreaterThan($maximumId, $position->id);
    }

    public function test_price_accepts_the_entire_validated_integer_range(): void
    {
        $scheme = CertificationScheme::factory()->create(['price' => 4294967295]);

        $this->assertSame(4294967295, $scheme->refresh()->price);
    }

    public function test_price_can_remain_unknown(): void
    {
        $scheme = CertificationScheme::factory()->create(['price' => null]);

        $this->assertNull($scheme->refresh()->price);
    }

    public function test_migration_preserves_existing_unknown_prices(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires the disposable PostgreSQL test database.');
        }
        $scheme = CertificationScheme::factory()->create(['price' => null]);
        $migration = require database_path('migrations/2026_10_13_073014_prepare_postgres_for_preview_deployment.php');
        $migration->down();
        DB::statement('ALTER TABLE certification_schemes ALTER COLUMN price TYPE integer');
        $migration->up();

        $this->assertNull($scheme->refresh()->price);
        $scheme->update(['price' => 4294967295]);
        $this->assertSame(4294967295, $scheme->refresh()->price);
    }

    public function test_postgres_rejects_removing_the_last_admin_through_a_direct_query(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires the disposable PostgreSQL test database.');
        }
        $admin = User::factory()->admin()->create();

        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $admin->id)->update(['is_admin' => false]);
    }

    public function test_data_api_roles_lose_existing_access_to_laravel_tables(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires the disposable PostgreSQL test database.');
        }
        foreach (['anon', 'authenticated'] as $role) {
            if (! DB::selectOne('SELECT 1 FROM pg_roles WHERE rolname = ?', [$role])) {
                DB::statement("CREATE ROLE $role NOLOGIN");
            }
            DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON public.users TO $role");
        }
        $migration = require database_path('migrations/2026_10_13_095007_protect_laravel_tables_from_supabase_data_api.php');

        $migration->up();

        foreach (['anon', 'authenticated'] as $role) {
            $this->assertFalse(DB::selectOne("SELECT has_table_privilege(?, 'public.users', 'SELECT,INSERT,UPDATE,DELETE') AS permitted", [$role])->permitted);
        }
        $this->assertModelExists(User::factory()->admin()->create());
    }

    public function test_row_security_blocks_anonymous_reads_even_if_select_is_granted_again(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires the disposable PostgreSQL test database.');
        }
        if (! DB::selectOne("SELECT 1 FROM pg_roles WHERE rolname = 'anon'")) {
            DB::statement('CREATE ROLE anon NOLOGIN');
        }
        User::factory()->create();
        $migration = require database_path('migrations/2026_10_13_095007_protect_laravel_tables_from_supabase_data_api.php');
        $migration->up();
        DB::statement('GRANT USAGE ON SCHEMA public TO anon');
        DB::statement('GRANT SELECT ON public.users TO anon');

        DB::statement('SET LOCAL ROLE anon');
        $users = DB::table('users')->get();
        DB::statement('RESET ROLE');

        $this->assertCount(0, $users);
    }
}
