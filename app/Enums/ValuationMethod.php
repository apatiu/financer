<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ValuationMethod: string implements HasLabel
{
    case Appraisal = 'appraisal';
    case Market = 'market';
    case Depreciation = 'depreciation';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Appraisal => 'ราคาประเมิน',
            self::Market => 'ราคาตลาด',
            self::Depreciation => 'ค่าเสื่อม',
            self::Manual => 'กรอกเอง',
        };
    }
}
