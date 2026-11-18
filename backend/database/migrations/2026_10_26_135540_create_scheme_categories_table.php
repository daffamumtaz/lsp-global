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
        Schema::create('scheme_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $categories = DB::table('certification_schemes')->select('category')->distinct()->pluck('category');
        foreach ($categories as $name) {
            if (filled($name)) {
                DB::table('scheme_categories')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        Schema::table('certification_schemes', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });
        DB::table('certification_schemes')->where('category', '')->update(['category' => null]);
        Schema::table('certification_schemes', function (Blueprint $table) {
            $table->foreign('category')->references('name')->on('scheme_categories')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certification_schemes', function (Blueprint $table) {
            $table->dropForeign(['category']);
        });
        DB::table('certification_schemes')->whereNull('category')->update(['category' => '']);
        Schema::table('certification_schemes', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
        });
        Schema::dropIfExists('scheme_categories');
    }
};
