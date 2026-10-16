<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tables = ['migrations', 'users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'site_pages', 'certification_schemes', 'gallery_items', 'contact_messages', 'media', 'activity_log', 'organization_positions', 'processes', 'process_stages', 'process_actors', 'process_steps', 'process_connections', 'tuks', 'assessors', 'widget_grid_layouts', 'widget_grid_templates', 'widget_grid_settings'];
        foreach ($tables as $table) {
            DB::statement("ALTER TABLE public.$table ENABLE ROW LEVEL SECURITY");
        }
        $qualifiedTables = implode(', ', array_map(fn (string $table): string => "public.$table", $tables));
        $roles = ['PUBLIC'];
        foreach (['anon', 'authenticated'] as $role) {
            if (DB::selectOne('SELECT 1 FROM pg_roles WHERE rolname = ?', [$role])) {
                $roles[] = $role;
            }
        }
        foreach ($roles as $role) {
            DB::statement("REVOKE ALL ON TABLE $qualifiedTables FROM $role");
            DB::statement("REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM $role");
            DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON TABLES FROM $role");
            DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON SEQUENCES FROM $role");
        }
    }

    /** Rollbacks retain protection; Laravel uses the table owner, not Data API roles. */
    public function down(): void {}
};
