<?php

use App\Models\Process;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $data = json_decode(file_get_contents(resource_path('data/assessment-process.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($data): void {
            $process = Process::query()->where('slug', $data['slug'])->first();
            if (! $process) {
                return;
            }
            $process->update(['title' => $data['title'], 'description' => $data['description']]);
            $stages = $process->stages()->get();
            foreach ($data['stages'] as $index => $row) {
                $values = ['title' => $row['title'], 'description' => $row['description'], 'order' => $index + 1, 'is_active' => true];
                if ($stage = $stages->get($index)) {
                    $stage->update($values);
                } else {
                    $process->stages()->create($values);
                }
            }
            foreach ($stages->slice(count($data['stages'])) as $stage) {
                $stage->update(['is_active' => false]);
            }
        });
    }

    public function down(): void {}
};
