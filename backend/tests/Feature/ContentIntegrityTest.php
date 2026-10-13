<?php

namespace Tests\Feature;

use App\Models\Assessor;
use App\Models\CertificationScheme;
use App\Models\GalleryItem;
use App\Models\Tuk;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public static function invalidRecords(): array
    {
        return [
            'blank TUK' => [Tuk::class, ['name' => '   ']],
            'credentials' => [Tuk::class, ['website_url' => 'https://user:pass@example.com']],
            'unsafe URL' => [Tuk::class, ['website_url' => 'javascript:alert(1)']],
            'negative price' => [CertificationScheme::class, ['price' => -1]],
            'invalid level' => [CertificationScheme::class, ['level' => 10]],
            'invalid method' => [CertificationScheme::class, ['methods' => ['invalid']]],
            'invalid unit' => [CertificationScheme::class, ['units' => [['code' => 'U1']]]],
            'invalid requirement' => [CertificationScheme::class, ['basic_requirements' => [['invalid']]]],
            'invalid year' => [GalleryItem::class, ['year' => 2101]],
        ];
    }

    #[DataProvider('invalidRecords')]
    public function test_model_rejects_invalid_content(string $model, array $attributes): void
    {
        $this->expectException(ValidationException::class);
        $model::factory()->create($attributes);
    }

    public function test_identifiers_are_normalized_and_optional_blanks_become_null(): void
    {
        $tuk = Tuk::factory()->create(['code' => ' tuk-01 ']);
        $this->assertSame('TUK-01', $tuk->code);
        $this->assertNull(Assessor::factory()->create(['certificate' => '  '])->certificate);
        $this->assertNull(Tuk::factory()->create(['code' => ''])->code);
        $this->expectException(ValidationException::class);
        Tuk::factory()->create(['code' => 'tuk-01']);
    }

    public function test_database_rejects_duplicate_certificates_after_trim(): void
    {
        Assessor::factory()->create(['certificate' => 'MET-001']);
        $this->expectException(QueryException::class);
        DB::table('assessors')->insert(['name' => 'Duplicate', 'certificate' => ' met-001 ']);
    }
}
