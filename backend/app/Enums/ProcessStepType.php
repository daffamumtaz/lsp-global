<?php

namespace App\Enums;

enum ProcessStepType: string
{
    case Normal = 'normal';
    case Decision = 'decision';
    case Outcome = 'outcome';
    case Document = 'document';
    case Start = 'start';
    case End = 'end';

    public static function options(): array
    {
        return [self::Normal->value => 'Normal', self::Decision->value => 'Keputusan', self::Outcome->value => 'Hasil', self::Document->value => 'Dokumen', self::Start->value => 'Mulai', self::End->value => 'Selesai'];
    }
}
