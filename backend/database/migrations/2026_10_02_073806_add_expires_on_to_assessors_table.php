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
        Schema::table('assessors', function (Blueprint $table) {
            $table->date('expires_on')->nullable()->index();
        });
        DB::table('assessors')->update(['expires_on' => now('Asia/Jakarta')->addYearsNoOverflow(3)->toDateString()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessors', function (Blueprint $table) {
            $table->dropIndex(['expires_on']);
            $table->dropColumn('expires_on');
        });
    }
};
