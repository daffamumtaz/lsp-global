<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            foreach (['website_url', 'logo_path', 'code', 'city', 'province', 'maps_url'] as $field) {
                $table->string($field)->nullable();
            }
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('assessors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('certificate')->nullable();
            $table->string('field')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('certification_schemes', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->string('type')->default('Okupasi');
            $table->string('thumbnail')->nullable();
            $table->string('registration_url')->nullable();
            foreach (['methods', 'units', 'basic_requirements', 'administrative_requirements'] as $field) {
                $table->json($field)->nullable();
            }
            $table->unsignedInteger('price')->nullable()->default(null)->change();
        });
        Storage::disk('public')->put('tuks/jobhun.png', file_get_contents(resource_path('data/jobhun.png')));
        DB::table('tuks')->insert(['name' => 'Jobhun', 'logo_path' => 'tuks/jobhun.png', 'website_url' => 'https://jobhun.id', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('certification_schemes', function (Blueprint $table) {
            $table->dropColumn(['slug', 'type', 'thumbnail', 'registration_url', 'methods', 'units', 'basic_requirements', 'administrative_requirements']);
        });
        Schema::dropIfExists('assessors');
        Schema::dropIfExists('tuks');
    }
};
