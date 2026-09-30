<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organization_positions')->restrictOnDelete();
            $table->string('title', 120);
            $table->string('person_name', 160);
            $table->string('credentials', 160)->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['parent_id', 'order']);
        });
        $nodes = json_decode(file_get_contents(resource_path('data/organization.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($nodes as $node) {
            DB::table('organization_positions')->insert([...$node, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ($nodes as $node) {
            DB::table('organization_positions')->where('id', $node['id'])->update(['parent_id' => $node['parent_id']]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_positions');
    }
};
