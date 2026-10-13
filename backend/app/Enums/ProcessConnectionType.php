<?php

namespace App\Enums;

enum ProcessConnectionType: string
{
    case Normal = 'normal';
    case Success = 'success';
    case Revision = 'revision';

    public static function options(): array
    {
        return [self::Normal->value => 'Normal', self::Success->value => 'Kompeten / sukses', self::Revision->value => 'Revisi / asesmen ulang'];
    }
}
