<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_positions', function (Blueprint $table): void {
            $table->json('members')->nullable();
            $table->unsignedSmallInteger('layout_row')->nullable();
            $table->unsignedTinyInteger('layout_column')->nullable();
            $table->unique(['layout_row', 'layout_column']);
        });
        DB::table('organization_positions')->orderBy('id')->each(function (object $node): void {
            DB::table('organization_positions')->where('id', $node->id)->update([
                'members' => json_encode([['person_name' => $node->person_name, 'credentials' => $node->credentials]], JSON_THROW_ON_ERROR),
            ]);
        });

        // Preserve edited content and hierarchy; sibling order does not affect grid positions.
        $defaults = json_decode(file_get_contents(resource_path('data/organization.json')), true, 512, JSON_THROW_ON_ERROR);
        $current = DB::table('organization_positions')->get()->keyBy('id');
        $unchanged = $current->count() === count($defaults);
        foreach ($defaults as $node) {
            foreach ($node as $key => $value) {
                if ($key === 'order') {
                    continue;
                }
                if (! isset($current[$node['id']]) || $current[$node['id']]->{$key} != $value) {
                    $unchanged = false;
                }
            }
        }
        if ($unchanged) {
            $slots = [1 => [1, 5], 2 => [2, 3], 3 => [3, 7], 4 => [4, 3], 5 => [5, 1], 6 => [6, 1], 7 => [5, 3], 8 => [6, 3], 9 => [6, 4], 10 => [5, 7], 11 => [6, 7], 12 => [5, 9], 13 => [6, 9]];
            foreach ($slots as $id => [$row, $column]) {
                DB::table('organization_positions')->where('id', $id)->update(['layout_row' => $row, 'layout_column' => $column]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('organization_positions', function (Blueprint $table): void {
            $table->dropUnique(['layout_row', 'layout_column']);
            $table->dropColumn(['members', 'layout_row', 'layout_column']);
        });
    }
};
