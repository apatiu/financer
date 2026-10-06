<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PremiumFrequency: string implements HasLabel
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case HalfYearly = 'half_yearly';
    case Yearly = 'yearly';
    case Single = 'single';

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly => 'รายเดือน',
            self::Quarterly => 'รายไตรมาส',
            self::HalfYearly => 'ราย 6 เดือน',
            self::Yearly => 'รายปี',
            self::Single => 'จ่ายครั้งเดียว',
        };
    }
}
