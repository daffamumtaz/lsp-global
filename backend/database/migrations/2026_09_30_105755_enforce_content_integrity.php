<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['tuks' => 'code', 'assessors' => 'certificate'] as $table => $column) {
            $duplicate = DB::table($table)->selectRaw("UPPER(TRIM($column)) AS identifier, COUNT(*) AS total")
                ->whereNotNull($column)->whereRaw("TRIM($column) <> ''")->groupByRaw("UPPER(TRIM($column))")->havingRaw('COUNT(*) > 1')->exists();
            if ($duplicate) {
                throw new RuntimeException("Duplikasi $table.$column: perbaiki data sebelum migration.");
            }
            DB::table($table)->whereNotNull($column)->update([$column => DB::raw("NULLIF(UPPER(TRIM($column)), '')")]);
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($column));
            if (DB::getDriverName() === 'sqlite') {
                DB::statement("CREATE UNIQUE INDEX {$table}_{$column}_normalized_unique ON $table (UPPER(TRIM($column))) WHERE $column IS NOT NULL AND TRIM($column) <> ''");
            }
        }
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }
        foreach (['process_stages', 'process_actors'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_immutable_process BEFORE UPDATE OF process_id ON $table WHEN NEW.process_id <> OLD.process_id BEGIN SELECT RAISE(ABORT, 'Data tidak dapat dipindahkan ke alur lain.'); END");
        }
        foreach (['INSERT', 'UPDATE'] as $event) {
            $suffix = strtolower($event);
            DB::unprepared("CREATE TRIGGER process_steps_same_process_$suffix BEFORE $event ON process_steps WHEN
                (NEW.actor_id IS NOT NULL AND (SELECT process_id FROM process_actors WHERE id = NEW.actor_id) <> (SELECT process_id FROM process_stages WHERE id = NEW.stage_id))
                OR EXISTS (SELECT 1 FROM process_connections WHERE (source_step_id = NEW.id OR target_step_id = NEW.id) AND process_id <> (SELECT process_id FROM process_stages WHERE id = NEW.stage_id))
                BEGIN SELECT RAISE(ABORT, 'Aktivitas dan pelaksana harus berasal dari alur yang sama.'); END");
            DB::unprepared("CREATE TRIGGER process_connections_same_process_$suffix BEFORE $event ON process_connections WHEN
                NEW.source_step_id = NEW.target_step_id
                OR NEW.process_id <> (SELECT s.process_id FROM process_steps p JOIN process_stages s ON s.id = p.stage_id WHERE p.id = NEW.source_step_id)
                OR NEW.process_id <> (SELECT s.process_id FROM process_steps p JOIN process_stages s ON s.id = p.stage_id WHERE p.id = NEW.target_step_id)
                BEGIN SELECT RAISE(ABORT, 'Hubungan harus berada dalam alur yang sama.'); END");
            DB::unprepared("CREATE TRIGGER schemes_valid_values_$suffix BEFORE $event ON certification_schemes WHEN NEW.price < 0 OR NEW.price > 4294967295 OR (NEW.level IS NOT NULL AND (CAST(NEW.level AS INTEGER) NOT BETWEEN 1 AND 9 OR CAST(CAST(NEW.level AS INTEGER) AS TEXT) <> NEW.level))
                BEGIN SELECT RAISE(ABORT, 'Harga atau level tidak valid.'); END");
            foreach (['methods', 'units', 'basic_requirements', 'administrative_requirements'] as $column) {
                DB::unprepared("CREATE TRIGGER schemes_{$column}_json_$suffix BEFORE $event ON certification_schemes WHEN NEW.$column IS NOT NULL AND (NOT json_valid(NEW.$column) OR json_type(NEW.$column) <> 'array') BEGIN SELECT RAISE(ABORT, 'JSON harus berupa array valid.'); END");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach (['process_stages_immutable_process', 'process_actors_immutable_process'] as $name) {
                DB::unprepared("DROP TRIGGER IF EXISTS $name");
            }
            foreach (['insert', 'update'] as $suffix) {
                foreach (['process_steps_same_process', 'process_connections_same_process', 'schemes_valid_values', 'schemes_methods_json', 'schemes_units_json', 'schemes_basic_requirements_json', 'schemes_administrative_requirements_json'] as $name) {
                    DB::unprepared("DROP TRIGGER IF EXISTS {$name}_$suffix");
                }
            }
        }
        foreach (['tuks' => 'code', 'assessors' => 'certificate'] as $table => $column) {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement("DROP INDEX IF EXISTS {$table}_{$column}_normalized_unique");
            }
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique([$column]));
        }
    }
};
