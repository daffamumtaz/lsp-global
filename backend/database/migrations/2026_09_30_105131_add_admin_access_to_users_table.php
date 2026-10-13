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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER users_keep_last_admin_update BEFORE UPDATE OF is_admin ON users WHEN OLD.is_admin = 1 AND NEW.is_admin = 0 AND (SELECT COUNT(*) FROM users WHERE is_admin = 1) = 1 BEGIN SELECT RAISE(ABORT, 'Admin terakhir tidak dapat dicabut.'); END");
            DB::unprepared("CREATE TRIGGER users_keep_last_admin_delete BEFORE DELETE ON users WHEN OLD.is_admin = 1 AND (SELECT COUNT(*) FROM users WHERE is_admin = 1) = 1 BEGIN SELECT RAISE(ABORT, 'Admin terakhir tidak dapat dihapus.'); END");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS users_keep_last_admin_update');
            DB::unprepared('DROP TRIGGER IF EXISTS users_keep_last_admin_delete');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
