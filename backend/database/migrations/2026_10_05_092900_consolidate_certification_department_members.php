<?php

use App\Models\OrganizationPosition;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $department = OrganizationPosition::where('title', 'Ketua Bid. Sertifikasi dan Standardisasi')->first();
        if ($department === null) {
            return;
        }
        foreach ($department->children()->where('title', 'Anggota')->whereDoesntHave('children')->orderBy('order')->get()->groupBy('is_active') as $members) {
            if ($members->count() > 1) {
                $members->first()->mergePositions($members->skip(1)->modelKeys());
            }
        }
    }

    public function down(): void
    {
        // Members remain intact; rollback must not split a subsequently edited CMS position.
    }
};
