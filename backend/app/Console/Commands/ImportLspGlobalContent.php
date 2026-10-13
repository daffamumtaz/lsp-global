<?php

namespace App\Console\Commands;

use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\Tuk;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('content:import-lspglobal')]
#[Description('Mengimpor snapshot data publik lspglobal.id tanpa menebak lokasi atau masa berlaku')]
class ImportLspGlobalContent extends Command
{
    public function handle(): int
    {
        $snapshot = json_decode(file_get_contents(resource_path('data/lspglobal/snapshot.json')), true, 512, JSON_THROW_ON_ERROR);
        $tukEnrichment = json_decode(file_get_contents(resource_path('data/lspglobal/tuk-enrichment.json')), true, 512, JSON_THROW_ON_ERROR);
        $photos = json_decode(file_get_contents(resource_path('data/lspglobal/scheme-photos.json')), true, 512, JSON_THROW_ON_ERROR);
        $schemeEnrichment = json_decode(file_get_contents(resource_path('data/lspglobal/scheme-enrichment.json')), true, 512, JSON_THROW_ON_ERROR)['entries'];
        $assessorEnrichment = json_decode(file_get_contents(resource_path('data/lspglobal/assessor-enrichment.json')), true, 512, JSON_THROW_ON_ERROR)['entries'];
        $assessors = array_map(function (array $row) use ($assessorEnrichment): array {
            $row['met'] = $assessorEnrichment[$row['nama']]['certificate'] ?? $row['met'];

            return $row;
        }, $snapshot['assessors']);
        $certificates = collect($assessors)->countBy('met');
        $totals = DB::transaction(function () use ($snapshot, $tukEnrichment, $photos, $certificates, $assessors, $assessorEnrichment, $schemeEnrichment): array {
            $totals = ['TUK' => 0, 'Skema' => 0, 'Asesor' => 0];
            foreach ($snapshot['tuks'] as $row) {
                [$city, $province] = array_pad(explode(',', $row['kotaProvinsi'], 2), 2, null);
                $record = Tuk::where('code', $row['kodeTuk'])->first() ?? Tuk::where('name', $row['namaTuk'])->whereNull('code')->first() ?? new Tuk;
                $record->fill(['name' => $row['namaTuk'], 'code' => $row['kodeTuk'], 'city' => trim($city), 'province' => $province !== null ? trim($province) : null, 'address' => filled($row['alamat']) ? $row['alamat'] : null, 'kind' => $row['jenisTuk']]);
                $enrichment = $tukEnrichment['entries'][$row['kodeTuk']] ?? [];
                if (isset($enrichment['logo_file'])) {
                    $logoPath = 'tuks/'.$enrichment['logo_file'];
                    $logoFile = resource_path('data/lspglobal/tuks/'.$enrichment['logo_file']);
                    if (! is_file($logoFile)) {
                        throw new \RuntimeException('Logo TUK sumber tidak tersedia: '.$row['kodeTuk']);
                    }
                    Storage::disk('public')->put($logoPath, file_get_contents($logoFile));
                    $record->logo_path = $logoPath;
                }
                foreach (['maps_url', 'latitude', 'longitude', 'is_active'] as $field) {
                    if (array_key_exists($field, $enrichment)) {
                        $record->{$field} = $enrichment[$field];
                    }
                }
                if (blank($record->website_url) && isset($enrichment['website_url'])) {
                    $record->website_url = $enrichment['website_url'];
                }
                $record->save();
                $totals['TUK']++;
            }
            foreach ($snapshot['schemes'] as $row) {
                $names = [Str::slug($row['schemaName'])];
                if (preg_match('/\(([^()]+)\)\s*$/', $row['schemaName'], $match)) {
                    $names[] = Str::slug($match[1]);
                }
                $record = CertificationScheme::all()->first(fn ($scheme): bool => in_array(Str::slug($scheme->name), $names, true)) ?? new CertificationScheme;
                $photo = $photos['schemes'][$row['id']];
                $path = 'schemes/stock/'.$photo;
                $image = resource_path('data/lspglobal/stock/'.$photo);
                if (! is_file($image)) {
                    throw new \RuntimeException('Gambar sumber tidak tersedia: '.$row['id']);
                }
                Storage::disk('public')->put($path, file_get_contents($image));
                $record->fill([
                    'name' => $row['schemaName'], 'category' => $row['category'], 'level' => $row['tier'], 'type' => $row['schemaType'], 'price' => $row['price'],
                    'thumbnail' => filled($record->thumbnail) && ! str_starts_with($record->thumbnail, 'schemes/imported/') && Storage::disk('public')->exists($record->thumbnail) ? $record->thumbnail : $path,
                    'methods' => $row['isSjj'] ? ['luring', 'jarak-jauh'] : ['luring'],
                    'registration_url' => 'https://'.$row['redirectUrl'],
                    'units' => $schemeEnrichment[$row['id']]['units'] ?? array_map(fn ($unit): array => ['code' => $unit['code'], 'title' => $unit['name'], 'description' => $unit['description']], $row['content']['units']),
                    'basic_requirements' => array_column($row['content']['requirements'], 'description'),
                    'administrative_requirements' => $snapshot['administrative_requirements'],
                ]);
                $record->save();
                $totals['Skema']++;
            }
            foreach ($assessors as $row) {
                $record = Assessor::where('name', $row['nama'])->first() ?? new Assessor;
                $conflict = $certificates[$row['met']] > 1 || Assessor::where('certificate', $row['met'])->where('name', '!=', $row['nama'])->exists();
                $record->name = $row['nama'];
                if (isset($assessorEnrichment[$row['nama']]) || ! $record->exists || blank($record->certificate)) {
                    $record->certificate = $conflict ? null : $row['met'];
                }
                if ($conflict) {
                    $record->import_notes = 'Sumber lspglobal.id mencantumkan '.$row['met'].' untuk lebih dari satu nama. Nomor belum dikonfirmasi; periksa sebelum menetapkan sertifikat.';
                } elseif (isset($assessorEnrichment[$row['nama']])) {
                    $record->import_notes = $assessorEnrichment[$row['nama']]['notes'];
                } elseif (str_starts_with($record->import_notes ?? '', 'Sumber lspglobal.id mencantumkan ')) {
                    $record->import_notes = null;
                }
                if (! $record->exists) {
                    $record->expires_on = null;
                }
                $record->save();
                $totals['Asesor']++;
            }

            return $totals;
        });
        foreach ($totals as $label => $count) {
            $this->info($label.': '.$count.' entri sumber diproses.');
        }
        $this->warn('Koordinat, bidang dan tanggal kedaluwarsa yang tidak ada pada sumber tetap kosong. Nomor MET ganda dicatat untuk verifikasi.');

        return self::SUCCESS;
    }
}
