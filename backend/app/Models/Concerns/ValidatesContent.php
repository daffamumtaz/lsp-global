<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait ValidatesContent
{
    protected static function bootValidatesContent(): void
    {
        static::creating(function (self $record): void {
            $record->validateContent();
        });
        static::updating(function (self $record): void {
            $record->validateContent();
        });
    }

    protected function validateContent(): void
    {
        $record = $this;
        foreach (['name', 'title', 'category', 'slug', 'level', 'code', 'certificate'] as $field) {
            if (is_string($record->$field)) {
                $record->$field = trim($record->$field);
            }
        }
        foreach (['code', 'certificate'] as $field) {
            if (array_key_exists($field, $record->getAttributes())) {
                $record->$field = filled($record->$field) ? strtoupper($record->$field) : null;
            }
        }
        // Kolom JSON harus divalidasi sebagai array hasil cast, bukan string mentah database.
        $data = $record->getAttributes();
        foreach ($record->getCasts() as $field => $cast) {
            if ($cast === 'array') {
                $data[$field] = $record->$field;
            }
        }
        Validator::make($data, $record->validationRules())->validate();
        foreach (['website_url', 'maps_url', 'registration_url'] as $field) {
            if ($record->$field && (parse_url($record->$field, PHP_URL_USER) !== null || parse_url($record->$field, PHP_URL_PASS) !== null)) {
                throw ValidationException::withMessages([$field => 'Link tidak boleh memuat kredensial.']);
            }
        }
    }
}
