<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CategoryType: string implements HasLabel
{
    case Income = 'income';
    case Expense = 'expense';

    public function getLabel(): string
    {
        return match ($this) {
            self::Income => 'รายรับ',
            self::Expense => 'รายจ่าย',
        };
    }
}
