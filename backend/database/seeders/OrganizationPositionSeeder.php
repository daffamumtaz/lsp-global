<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationPositionSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('organization_positions')->exists()) {
            return;
        }
        $nodes = json_decode(file_get_contents(resource_path('data/organization.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($nodes): void {
            foreach ($nodes as $node) {
                DB::table('organization_positions')->insert([...$node, 'parent_id' => null, 'members' => json_encode([['person_name' => $node['person_name'], 'credentials' => $node['credentials']]], JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
            }
            $slots = [1 => [1, 4], 2 => [2, 3], 3 => [3, 5], 4 => [4, 3], 5 => [5, 1], 6 => [6, 1], 7 => [5, 3], 8 => [6, 3], 9 => [6, 4], 10 => [5, 5], 11 => [6, 5], 12 => [5, 7], 13 => [6, 7]];
            foreach ($nodes as $node) {
                [$row, $column] = $slots[$node['id']];
                DB::table('organization_positions')->where('id', $node['id'])->update(['parent_id' => $node['parent_id'], 'layout_row' => $row, 'layout_column' => $column]);
            }
        });
    }
}
