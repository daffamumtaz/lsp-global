<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $slots = [1 => [1, 5, 4], 3 => [3, 7, 5], 10 => [5, 7, 5], 11 => [6, 7, 5], 12 => [5, 9, 7], 13 => [6, 9, 7]];
        DB::transaction(function () use ($slots): void {
            foreach ($slots as $id => [$row, $oldColumn, $column]) {
                if (! DB::table('organization_positions')->where('layout_row', $row)->where('layout_column', $column)->where('id', '!=', $id)->exists()) {
                    DB::table('organization_positions')->where('id', $id)->where('layout_row', $row)->where('layout_column', $oldColumn)->update(['layout_column' => $column]);
                }
            }
        });
    }

    public function down(): void
    {
        // Grid coordinates are CMS content; rollback preserves subsequent editorial changes.
    }
};
