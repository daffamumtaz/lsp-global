<?php

use App\Models\SitePage;
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
        Schema::table('site_pages', function (Blueprint $table) {
            $table->json('content')->nullable();
        });

        DB::table('site_pages')->insertOrIgnore([
            'kind' => 'landing',
            'title' => 'Landing Page',
            'content' => json_encode(SitePage::defaultContent(), JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_pages', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};
