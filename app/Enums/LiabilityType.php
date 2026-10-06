<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LiabilityType: string implements HasLabel
{
    case CarLoan = 'car_loan';
    case Mortgage = 'mortgage';
    case PersonalLoan = 'personal_loan';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::CarLoan => 'สินเชื่อรถ',
            self::Mortgage => 'สินเชื่อบ้าน',
            self::PersonalLoan => 'สินเชื่อส่วนบุคคล',
            self::Other => 'อื่น ๆ',
        };
    }
}
