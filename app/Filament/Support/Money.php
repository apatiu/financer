<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

/**
 * ช่องเงินในฟอร์ม/ตาราง: ฐานข้อมูลเก็บเป็นหน่วยย่อย (สตางค์) แต่หน้าจอแสดงและกรอกเป็นหน่วยหลัก (บาท)
 */
class Money
{
    public static function input(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->step('0.01')
            ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round($state * 100));
    }

    public static function column(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->numeric(decimalPlaces: 2)
            ->formatStateUsing(fn ($state) => number_format($state / 100, 2))
            ->alignEnd();
    }

    public static function currencyInput(string $name = 'currency'): TextInput
    {
        return TextInput::make($name)
            ->label('สกุลเงิน')
            ->default(fn (): string => Filament::getTenant()?->base_currency ?? 'THB')
            ->required()
            ->length(3)
            ->datalist(['THB', 'USD', 'EUR', 'JPY', 'GBP', 'CNY', 'SGD', 'HKD', 'AUD'])
            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state));
    }

    /**
     * @return array<int, TextInput|Toggle>
     */
    public static function ownerFields(): array
    {
        return [
            TextInput::make('owner_name')
                ->label('ชื่อเจ้าของ')
                ->helperText('เว้นว่างถ้าเป็นของตัวเอง ใส่ชื่อถ้าเป็นของคนในครอบครัว')
                ->maxLength(255),
            Toggle::make('include_in_net_worth')
                ->label('นับรวมในความมั่งคั่งสุทธิ')
                ->default(true),
        ];
    }
}
