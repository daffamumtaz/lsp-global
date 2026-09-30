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
                DB::table('organization_positions')->insert([...$node, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ($nodes as $node) {
                DB::table('organization_positions')->where('id', $node['id'])->update(['parent_id' => $node['parent_id']]);
            }
        });
    }
}
