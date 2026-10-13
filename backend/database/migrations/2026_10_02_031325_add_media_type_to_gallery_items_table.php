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
        $types = [];
        foreach (DB::table('gallery_items')->get(['id', 'title']) as $album) {
            $mimes = DB::table('media')->where('model_type', 'App\\Models\\GalleryItem')->where('model_id', $album->id)->where('collection_name', 'gallery')->pluck('mime_type');
            $albumTypes = $mimes->map(fn (string $mime): string => str_starts_with($mime, 'video/') ? 'video' : 'photo')->unique();
            if ($albumTypes->count() > 1) {
                throw new RuntimeException("Album galeri #{$album->id} ({$album->title}) berisi campuran foto dan video. Pisahkan media sebelum menjalankan migrasi.");
            }
            $types[$album->id] = $albumTypes->first();
        }
        Schema::table('gallery_items', fn (Blueprint $table) => $table->enum('media_type', ['photo', 'video'])->nullable());
        foreach ($types as $id => $type) {
            DB::table('gallery_items')->where('id', $id)->update(['media_type' => $type]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gallery_items', fn (Blueprint $table) => $table->dropColumn('media_type'));
    }
};
