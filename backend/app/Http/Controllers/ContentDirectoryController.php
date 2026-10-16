<?php

namespace App\Http\Controllers;

use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\GalleryItem;
use App\Models\Tuk;
use App\Observers\ContentDirectoryObserver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContentDirectoryController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        if (DB::transactionLevel() > 0) {
            return response()->json($this->directoryData());
        }

        Cache::add(ContentDirectoryObserver::CACHE_VERSION_KEY, (string) Str::uuid());
        $version = Cache::get(ContentDirectoryObserver::CACHE_VERSION_KEY);
        $key = 'content-directory:'.$version.':'.hash('sha256', $request->getSchemeAndHttpHost());

        return response()->json(Cache::remember($key, max(1, (int) config('security.content_directory_cache_seconds')), fn (): array => $this->directoryData()));
    }

    private function directoryData(): array
    {
        $mediaUrl = static fn (?string $path): ?string => filled($path) ? url(Storage::disk('public')->url($path)) : null;

        return ['data' => [
            'tuks' => Tuk::query()->where('is_active', true)->orderBy('name')->get()->map(fn (Tuk $tuk): array => self::tukEntry($tuk))->all(),
            'assessors' => Assessor::query()->where('is_active', true)->orderBy('name')->get()->map(fn (Assessor $assessor): array => [
                'id' => (string) $assessor->id,
                'name' => $assessor->name,
                'certificate' => $assessor->certificate ?? '',
                'field' => $assessor->field ?? '',
            ])->all(),
            'schemes' => CertificationScheme::query()->where('is_active', true)->whereNotNull('slug')->orderBy('name')->get()->map(fn (CertificationScheme $scheme): array => [
                'id' => (string) $scheme->id,
                'slug' => $scheme->slug,
                'name' => $scheme->name,
                'category' => $scheme->category,
                'level' => (int) $scheme->level,
                'type' => $scheme->type,
                'methods' => $scheme->methods ?? [],
                'price' => $scheme->price,
                'thumbnail' => $mediaUrl($scheme->thumbnail) ?? '/figma/certification.jpg',
                'units' => $scheme->units ?? [],
                'basicRequirements' => $scheme->basic_requirements ?? [],
                'administrativeRequirements' => $scheme->administrative_requirements ?? [],
                'registrationUrl' => $scheme->registration_url,
            ])->all(),
            'gallery' => GalleryItem::query()->with('media')->orderByDesc('year')->get()->map(fn (GalleryItem $item): array => [
                'id' => (string) $item->id,
                'title' => $item->title,
                'year' => (int) $item->year,
                'date' => $item->date,
                'location' => $item->location ?? '',
                'description' => trim($item->description ?? ''),
                'media_type' => $item->media_type,
                'media' => $item->getMedia('gallery')->map(function (Media $media) use ($item): array {
                    $isVideo = str_starts_with($media->mime_type, 'video/');
                    $url = $media->getFullUrl();

                    return [
                        'id' => (string) $media->id,
                        'type' => $isVideo ? 'video' : 'photo',
                        'src' => $url,
                        'thumbnail' => $isVideo ? '' : $url,
                        'alt' => $item->title,
                        'width' => 1200,
                        'height' => 800,
                    ];
                })->all(),
            ])->all(),
        ]];
    }

    public static function tukEntry(Tuk $tuk): array
    {
        return [
            'id' => (string) $tuk->id,
            'name' => $tuk->name ?? '',
            'url' => $tuk->website_url ?? '',
            'logo_url' => filled($tuk->logo_path) ? url(Storage::disk('public')->url($tuk->logo_path)) : '',
            'code' => $tuk->code ?? '',
            'kind' => $tuk->kind ?? '',
            'city' => $tuk->city ?? '',
            'province' => $tuk->province ?? '',
            'address' => $tuk->address ?? '',
            'maps_url' => $tuk->maps_url ?? '',
            'latitude' => $tuk->latitude,
            'longitude' => $tuk->longitude,
        ];
    }
}
