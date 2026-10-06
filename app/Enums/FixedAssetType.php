<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FixedAssetType: string implements HasLabel
{
    case Land = 'land';
    case House = 'house';
    case Condo = 'condo';
    case Vehicle = 'vehicle';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Land => 'ที่ดิน',
            self::House => 'บ้าน',
            self::Condo => 'คอนโด',
            self::Vehicle => 'รถยนต์',
            self::Other => 'อื่น ๆ',
        };
    }
}
