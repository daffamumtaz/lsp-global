<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tuks', fn (Blueprint $table) => $table->string('kind')->nullable());
        Schema::table('assessors', fn (Blueprint $table) => $table->text('import_notes')->nullable());
    }

    public function down(): void
    {
        Schema::table('tuks', fn (Blueprint $table) => $table->dropColumn('kind'));
        Schema::table('assessors', fn (Blueprint $table) => $table->dropColumn('import_notes'));
    }
};
