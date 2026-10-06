<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InsuranceType: string implements HasLabel
{
    case WholeLife = 'whole_life';
    case Endowment = 'endowment';
    case Annuity = 'annuity';
    case UnitLinked = 'unit_linked';
    case Term = 'term';
    case Health = 'health';
    case Accident = 'accident';
    case Property = 'property';
    case Motor = 'motor';

    public function getLabel(): string
    {
        return match ($this) {
            self::WholeLife => 'ตลอดชีพ',
            self::Endowment => 'สะสมทรัพย์',
            self::Annuity => 'บำนาญ',
            self::UnitLinked => 'ยูนิตลิงค์',
            self::Term => 'ชั่วระยะเวลา',
            self::Health => 'สุขภาพ',
            self::Accident => 'อุบัติเหตุ',
            self::Property => 'ทรัพย์สิน',
            self::Motor => 'รถยนต์',
        };
    }
}
