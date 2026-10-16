<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SELECT setval(pg_get_serial_sequence('organization_positions', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM organization_positions");
        Schema::table('certification_schemes', function (Blueprint $table): void {
            $table->bigInteger('price')->nullable()->default(null)->change();
        });
        DB::statement('ALTER TABLE certification_schemes ADD CONSTRAINT schemes_price_range CHECK (price BETWEEN 0 AND 4294967295)');
        DB::statement("ALTER TABLE certification_schemes ADD CONSTRAINT schemes_level_range CHECK (level IS NULL OR level ~ '^[1-9]$')");
        foreach (['methods', 'units', 'basic_requirements', 'administrative_requirements'] as $column) {
            DB::statement("ALTER TABLE certification_schemes ADD CONSTRAINT schemes_{$column}_array CHECK ($column IS NULL OR jsonb_typeof($column::jsonb) = 'array')");
        }
        foreach (['tuks' => 'code', 'assessors' => 'certificate'] as $table => $column) {
            DB::statement("CREATE UNIQUE INDEX {$table}_{$column}_normalized_unique ON $table (UPPER(TRIM($column))) WHERE $column IS NOT NULL AND TRIM($column) <> ''");
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.lsp_immutable_process() RETURNS trigger
LANGUAGE plpgsql SECURITY INVOKER SET search_path = pg_catalog AS $$
BEGIN
    IF NEW.process_id IS DISTINCT FROM OLD.process_id THEN
        RAISE EXCEPTION 'Data tidak dapat dipindahkan ke alur lain.' USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END;
$$;
CREATE OR REPLACE FUNCTION public.lsp_validate_process_step() RETURNS trigger
LANGUAGE plpgsql SECURITY INVOKER SET search_path = pg_catalog AS $$
DECLARE stage_process bigint;
BEGIN
    SELECT process_id INTO stage_process FROM public.process_stages WHERE id = NEW.stage_id;
    IF (NEW.actor_id IS NOT NULL AND EXISTS (
        SELECT 1 FROM public.process_actors WHERE id = NEW.actor_id AND process_id <> stage_process
    )) OR EXISTS (
        SELECT 1 FROM public.process_connections
        WHERE (source_step_id = NEW.id OR target_step_id = NEW.id) AND process_id <> stage_process
    ) THEN
        RAISE EXCEPTION 'Aktivitas dan pelaksana harus berasal dari alur yang sama.' USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END;
$$;
CREATE OR REPLACE FUNCTION public.lsp_validate_process_connection() RETURNS trigger
LANGUAGE plpgsql SECURITY INVOKER SET search_path = pg_catalog AS $$
BEGIN
    IF NEW.source_step_id = NEW.target_step_id OR EXISTS (
        SELECT 1 FROM public.process_steps step JOIN public.process_stages stage ON stage.id = step.stage_id
        WHERE step.id IN (NEW.source_step_id, NEW.target_step_id) AND stage.process_id <> NEW.process_id
    ) THEN
        RAISE EXCEPTION 'Hubungan harus berada dalam alur yang sama.' USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END;
$$;
CREATE OR REPLACE FUNCTION public.lsp_keep_last_admin() RETURNS trigger
LANGUAGE plpgsql SECURITY INVOKER SET search_path = pg_catalog AS $$
BEGIN
    IF OLD.is_admin THEN
        IF TG_OP = 'DELETE' THEN
            PERFORM pg_advisory_xact_lock(734219801);
            IF (SELECT COUNT(*) FROM public.users WHERE is_admin) <= 1 THEN
                RAISE EXCEPTION 'Admin terakhir tidak dapat dihapus.' USING ERRCODE = '23514';
            END IF;
        ELSIF NOT NEW.is_admin THEN
            PERFORM pg_advisory_xact_lock(734219801);
            IF (SELECT COUNT(*) FROM public.users WHERE is_admin) <= 1 THEN
                RAISE EXCEPTION 'Admin terakhir tidak dapat dicabut.' USING ERRCODE = '23514';
            END IF;
        END IF;
    END IF;
    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$;
SQL);
        foreach (['process_stages', 'process_actors'] as $table) {
            DB::statement("CREATE TRIGGER {$table}_immutable_process BEFORE UPDATE OF process_id ON $table FOR EACH ROW EXECUTE FUNCTION public.lsp_immutable_process()");
        }
        DB::statement('CREATE TRIGGER process_steps_same_process BEFORE INSERT OR UPDATE ON process_steps FOR EACH ROW EXECUTE FUNCTION public.lsp_validate_process_step()');
        DB::statement('CREATE TRIGGER process_connections_same_process BEFORE INSERT OR UPDATE ON process_connections FOR EACH ROW EXECUTE FUNCTION public.lsp_validate_process_connection()');
        DB::statement('CREATE TRIGGER users_keep_last_admin BEFORE UPDATE OF is_admin OR DELETE ON users FOR EACH ROW EXECUTE FUNCTION public.lsp_keep_last_admin()');
        foreach (['lsp_immutable_process', 'lsp_validate_process_step', 'lsp_validate_process_connection', 'lsp_keep_last_admin'] as $function) {
            DB::statement("REVOKE ALL ON FUNCTION public.$function() FROM PUBLIC");
            foreach (['anon', 'authenticated'] as $role) {
                if (DB::selectOne('SELECT 1 FROM pg_roles WHERE rolname = ?', [$role])) {
                    DB::statement("REVOKE ALL ON FUNCTION public.$function() FROM $role");
                }
            }
        }
    }

    /** Retain the widened price column and repaired sequence so rollback cannot truncate data. */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach ([
            'process_stages' => 'process_stages_immutable_process',
            'process_actors' => 'process_actors_immutable_process',
            'process_steps' => 'process_steps_same_process',
            'process_connections' => 'process_connections_same_process',
            'users' => 'users_keep_last_admin',
        ] as $table => $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS $trigger ON $table");
        }
        foreach (['lsp_immutable_process', 'lsp_validate_process_step', 'lsp_validate_process_connection', 'lsp_keep_last_admin'] as $function) {
            DB::statement("DROP FUNCTION IF EXISTS public.$function()");
        }
        foreach (['schemes_price_range', 'schemes_level_range', 'schemes_methods_array', 'schemes_units_array', 'schemes_basic_requirements_array', 'schemes_administrative_requirements_array'] as $constraint) {
            DB::statement("ALTER TABLE certification_schemes DROP CONSTRAINT IF EXISTS $constraint");
        }
        foreach (['tuks_code_normalized_unique', 'assessors_certificate_normalized_unique'] as $index) {
            DB::statement("DROP INDEX IF EXISTS $index");
        }
    }
};
