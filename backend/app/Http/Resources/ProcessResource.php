<?php

namespace App\Http\Resources;

use App\Models\ProcessActor;
use App\Models\ProcessConnection;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProcessResource extends JsonResource
{
    public function __construct($resource, private bool $includeInactive = false)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $actors = $this->actors->filter(fn (ProcessActor $actor): bool => $this->includeInactive || $actor->is_active);
        $actorIds = $actors->pluck('id')->all();
        // Preview menyertakan data nonaktif; publik menyembunyikan langkah milik pelaksana nonaktif.
        $stages = $this->stages
            ->filter(fn (ProcessStage $stage): bool => $this->includeInactive || $stage->is_active)
            ->map(function (ProcessStage $stage) use ($actorIds): array {
                $steps = $stage->steps
                    ->filter(fn (ProcessStep $step): bool => $this->includeInactive || (
                        $step->is_active && ($step->actor_id === null || in_array($step->actor_id, $actorIds, true))
                    ))
                    ->map(fn (ProcessStep $step): array => [
                        'id' => $step->id,
                        'title' => $step->title,
                        'description' => $step->description,
                        'actorId' => $step->actor_id,
                        'type' => $step->type,
                        'order' => $step->order,
                        'documentReference' => $step->document_reference,
                        'informationUrl' => $step->information_url,
                        'isActive' => $step->is_active,
                    ])->values()->all();

                return [
                    'id' => $stage->id,
                    'title' => $stage->title,
                    'description' => $stage->description,
                    'descriptionHtml' => Str::sanitizeHtml(Str::markdown($stage->description ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false])),
                    'order' => $stage->order,
                    'isActive' => $stage->is_active,
                    'steps' => $steps,
                ];
            })->values()->all();

        $visibleStepIds = [];
        foreach ($stages as $stage) {
            foreach ($stage['steps'] as $step) {
                $visibleStepIds[$step['id']] = true;
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'order' => $this->order,
            'isActive' => $this->is_active,
            'actors' => $actors->map(fn (ProcessActor $actor): array => [
                'id' => $actor->id,
                'name' => $actor->name,
                'label' => $actor->label,
                'description' => $actor->description,
                'order' => $actor->order,
                'isActive' => $actor->is_active,
            ])->values()->all(),
            'stages' => $stages,
            // Kedua ujung koneksi harus terlihat agar diagram tidak memiliki referensi putus.
            'connections' => $this->connections
                ->filter(fn (ProcessConnection $connection): bool => isset($visibleStepIds[$connection->source_step_id], $visibleStepIds[$connection->target_step_id]))
                ->map(fn (ProcessConnection $connection): array => [
                    'id' => $connection->id,
                    'sourceStepId' => $connection->source_step_id,
                    'targetStepId' => $connection->target_step_id,
                    'label' => $connection->label ?: null,
                    'type' => $connection->type ?: 'normal',
                    'order' => $connection->order,
                ])->values()->all(),
        ];
    }
}
