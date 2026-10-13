<?php

namespace App\Filament;

use App\Http\Controllers\ContentDirectoryController;
use App\Http\Resources\ProcessResource;
use App\Models\OrganizationPosition;
use App\Models\Process;
use App\Models\ProcessActor;
use App\Models\ProcessConnection;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use App\Models\Tuk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdminPreview
{
    public static function organization(?int $id = null, ?array $draft = null): array
    {
        $nodes = OrganizationPosition::query()->orderBy('order')->orderBy('id')->get()->keyBy('id');
        if ($draft !== null) {
            $node = self::draftRecord($nodes, OrganizationPosition::class, $id, $draft);
            $node->members = array_map(fn (array $member): array => ['person_name' => $member['person_name'] ?? '', 'credentials' => filled($member['credentials'] ?? null) ? $member['credentials'] : null], array_values($draft['members'] ?? []));
            $node->person_name = $node->members[0]['person_name'] ?? '';
            $node->credentials = $node->members[0]['credentials'] ?? null;
            foreach (['parent_id', 'layout_row', 'layout_column'] as $field) {
                $node->$field = filled($draft[$field] ?? null) ? $draft[$field] : null;
            }
            $nodes[$node->id] = $node;
        }

        return $nodes->map(fn (OrganizationPosition $node): array => $node->only(['id', 'parent_id', 'title', 'person_name', 'credentials', 'members', 'layout_row', 'layout_column', 'order', 'is_active']))->values()->all();
    }

    public static function tuks(?int $id = null, ?array $draft = null): array
    {
        $nodes = Tuk::query()->orderBy('name')->get()->keyBy('id');
        if ($draft !== null) {
            Validator::make($draft, ['latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180']])->validate();
            $node = self::draftRecord($nodes, Tuk::class, $id, $draft);
            foreach (['latitude', 'longitude'] as $field) {
                $node->$field = filled($draft[$field] ?? null) ? $draft[$field] : null;
            }
            // FileUpload state is a temporary file collection; preview must not persist it.
            $logo = $draft['logo_path'] ?? null;
            if (is_array($logo)) {
                $logo = reset($logo) ?: null;
            }
            if ($logo instanceof TemporaryUploadedFile) {
                $node->logo_path = null;
                $node->setAttribute('preview_logo_url', $logo->temporaryUrl());
            } else {
                $node->logo_path = array_key_exists('logo_path', $draft) ? (is_string($logo) ? $logo : null) : $node->getOriginal('logo_path');
            }
            $nodes[$node->id] = $node;
        }

        return $nodes->map(fn (Tuk $node): array => [...ContentDirectoryController::tukEntry($node), 'logo_url' => $node->preview_logo_url ?? ContentDirectoryController::tukEntry($node)['logo_url'], 'is_active' => $node->is_active])->values()->all();
    }

    public static function process(Process $record, array $header = [], ?string $kind = null, ?int $id = null, ?array $draft = null): array
    {
        $process = $record->fresh(['actors', 'stages.steps', 'connections']);
        $process->fill(array_intersect_key($header, array_flip(['title', 'description', 'is_active'])));
        $actors = $process->actors->keyBy('id');
        $stages = $process->stages->keyBy('id');
        $steps = $process->stages->flatMap(fn ($stage) => $stage->steps)->keyBy('id');
        $connections = $process->connections->keyBy('id');
        if ($draft !== null) {
            $models = ['actors' => ProcessActor::class, 'stages' => ProcessStage::class, 'steps' => ProcessStep::class, 'connections' => ProcessConnection::class];
            if (! isset($models[$kind])) {
                throw ValidationException::withMessages(['preview' => 'Jenis draft alur tidak valid.']);
            }
            $nodes = match ($kind) {
                'actors' => $actors, 'stages' => $stages, 'steps' => $steps, 'connections' => $connections
            };
            $node = self::draftRecord($nodes, $models[$kind], $id, $draft);
            if ($kind === 'steps') {
                $node->stage_id = (int) $node->stage_id;
                $node->actor_id = filled($draft['actor_id'] ?? null) ? (int) $draft['actor_id'] : null;
                if (! $stages->has($node->stage_id) || ($node->actor_id !== null && ! $actors->has($node->actor_id))) {
                    throw ValidationException::withMessages(['preview' => 'Tahap dan pelaksana harus berasal dari alur ini.']);
                }
            }
            if ($kind === 'connections') {
                $node->source_step_id = (int) $node->source_step_id;
                $node->target_step_id = (int) $node->target_step_id;
            }
            if ($kind === 'connections' && (! $steps->has($node->source_step_id) || ! $steps->has($node->target_step_id) || $node->source_step_id === $node->target_step_id)) {
                throw ValidationException::withMessages(['preview' => 'Pilih dua aktivitas berbeda dari alur ini.']);
            }
            $nodes[$node->id] = $node;
        }
        foreach ($stages as $stage) {
            $stage->setRelation('steps', $steps->filter(fn ($step): bool => $step->stage_id === $stage->id)->sortBy('order')->values());
        }
        $process->setRelation('actors', $actors->sortBy('order')->values());
        $process->setRelation('stages', $stages->sortBy('order')->values());
        $process->setRelation('connections', $connections->sortBy('order')->values());

        return (new ProcessResource($process, includeInactive: true))->resolve(request());
    }

    private static function draftRecord($nodes, string $model, ?int $id, array $draft): Model
    {
        if ($id !== null && ! $nodes->has($id)) {
            throw ValidationException::withMessages(['preview' => 'Data draft tidak ditemukan dalam preview ini.']);
        }
        $record = $id !== null ? clone $nodes[$id] : new $model;
        $record->fill(array_intersect_key($draft, array_flip(array_diff($record->getFillable(), ['process_id', 'logo_path']))));
        $record->id = $id ?? (($nodes->keys()->max() ?? 0) + 1);

        return $record;
    }
}
