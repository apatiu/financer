<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AssetType: string implements HasLabel
{
    case Stock = 'stock';
    case Fund = 'fund';
    case Gold = 'gold';
    case Bond = 'bond';
    case Crypto = 'crypto';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Stock => 'หุ้น',
            self::Fund => 'กองทุน',
            self::Gold => 'ทองคำ',
            self::Bond => 'ตราสารหนี้',
            self::Crypto => 'คริปโต',
            self::Other => 'อื่น ๆ',
        };
    }
}
