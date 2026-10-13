<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $board = DB::table('organization_positions')->where('title', 'Dewan Pengarah')->value('id');
        if ($board !== null) {
            DB::table('organization_positions')->where('title', 'like', 'Ketua Bid%')->update(['parent_id' => $board, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Previous CMS parents are unknown; rollback must not overwrite editorial changes.
    }
};
