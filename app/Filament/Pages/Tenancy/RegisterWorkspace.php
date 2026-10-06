<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Workspace;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterWorkspace extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'สร้างสมุดบัญชีใหม่';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('ชื่อสมุดบัญชี')->required()->maxLength(255),
            TextInput::make('base_currency')
                ->label('สกุลเงินหลัก')
                ->default('THB')
                ->required()
                ->length(3)
                ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),
        ]);
    }

    protected function handleRegistration(array $data): Workspace
    {
        return Workspace::createFor(auth()->user(), $data);
    }
}
