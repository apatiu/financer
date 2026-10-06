<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TradeType: string implements HasLabel
{
    case Buy = 'buy';
    case Sell = 'sell';
    case Dividend = 'dividend';
    case Split = 'split';
    case Bonus = 'bonus';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';

    public function getLabel(): string
    {
        return match ($this) {
            self::Buy => 'ซื้อ',
            self::Sell => 'ขาย',
            self::Dividend => 'ปันผล',
            self::Split => 'แตกพาร์',
            self::Bonus => 'หุ้นปันผล',
            self::TransferIn => 'โอนเข้า',
            self::TransferOut => 'โอนออก',
        };
    }
}
