<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processes', function (Blueprint $t): void {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('order')->default(1);
            $t->timestamps();
        });
        foreach (['process_stages', 'process_actors'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table): void {
                $t->id();
                $t->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
                if ($table === 'process_actors') {
                    $t->string('name', 80);
                    $t->string('label', 120);
                    $t->unique(['process_id', 'name']);
                } else {
                    $t->string('title');
                }
                $t->text('description')->nullable();
                $t->unsignedInteger('order')->default(1);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['process_id', 'is_active', 'order']);
            });
        }
        Schema::create('process_steps', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('stage_id')->constrained('process_stages')->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('process_actors')->nullOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('type', 40)->default('normal');
            $t->unsignedInteger('order')->default(1);
            $t->boolean('is_active')->default(true);
            $t->string('document_reference')->nullable();
            $t->string('information_url', 2048)->nullable();
            $t->timestamps();
            $t->index(['stage_id', 'is_active', 'order']);
        });
        Schema::create('process_connections', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $t->foreignId('source_step_id')->constrained('process_steps')->cascadeOnDelete();
            $t->foreignId('target_step_id')->constrained('process_steps')->cascadeOnDelete();
            $t->string('label', 120)->default('');
            $t->string('type', 40)->default('normal');
            $t->unsignedInteger('order')->default(1);
            $t->timestamps();
            $t->unique(['process_id', 'source_step_id', 'target_step_id', 'label', 'type'], 'process_connection_unique');
        });
    }

    public function down(): void
    {
        foreach (['process_connections', 'process_steps', 'process_actors', 'process_stages', 'processes'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
