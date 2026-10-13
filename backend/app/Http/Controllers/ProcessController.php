<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProcessResource;
use App\Models\Process;
use Illuminate\Http\JsonResponse;

class ProcessController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Process::query()->where('is_active', true)->orderBy('order')->orderBy('id')->get(['id', 'title', 'slug', 'description', 'order'])])->header('Cache-Control', 'no-store');
    }

    public function show(string $slug): JsonResponse
    {
        return $this->graph($slug, false);
    }

    public function preview(string $slug): JsonResponse
    {
        return $this->graph($slug, true);
    }

    private function graph(string $slug, bool $preview): JsonResponse
    {
        $process = Process::query()->when(! $preview, fn ($q) => $q->where('is_active', true))->with(['stages.steps', 'actors', 'connections'])->where('slug', $slug)->firstOrFail();

        return (new ProcessResource($process, $preview))->response()->header('Cache-Control', 'no-store');
    }
}
