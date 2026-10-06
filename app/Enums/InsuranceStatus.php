<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InsuranceStatus: string implements HasLabel
{
    case Active = 'active';
    case PaidUp = 'paid_up';
    case Lapsed = 'lapsed';
    case Surrendered = 'surrendered';
    case Matured = 'matured';
    case Claimed = 'claimed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'มีผลบังคับ',
            self::PaidUp => 'ชำระครบแล้ว',
            self::Lapsed => 'ขาดอายุ',
            self::Surrendered => 'เวนคืน',
            self::Matured => 'ครบกำหนด',
            self::Claimed => 'เคลมแล้ว',
        };
    }
}
