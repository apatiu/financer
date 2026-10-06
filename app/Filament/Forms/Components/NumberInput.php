<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class NumberInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mask(RawJs::make('$money($input)'))
            ->stripCharacters(',')
            ->numeric();
    }
}
