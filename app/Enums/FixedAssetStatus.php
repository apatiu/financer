<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FixedAssetStatus: string implements HasLabel
{
    case Owned = 'owned';
    case Sold = 'sold';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owned => 'ถือครองอยู่',
            self::Sold => 'ขายแล้ว',
        };
    }
}
