<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasLabel
{
    case Bank = 'bank';
    case Cash = 'cash';
    case CreditCard = 'credit_card';
    case Brokerage = 'brokerage';
    case Fund = 'fund';
    case Gold = 'gold';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bank => 'บัญชีธนาคาร',
            self::Cash => 'เงินสด',
            self::CreditCard => 'บัตรเครดิต',
            self::Brokerage => 'บัญชีหุ้น',
            self::Fund => 'บัญชีกองทุน',
            self::Gold => 'บัญชีทองคำ',
        };
    }
}
