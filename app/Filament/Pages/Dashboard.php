<?php

namespace App\Filament\Pages;

use App\Services\NetWorthService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('snapshot')
                ->label('บันทึกความมั่งคั่งตอนนี้')
                ->icon(Heroicon::OutlinedCamera)
                ->action(function (NetWorthService $netWorth): void {
                    $netWorth->snapshot(Filament::getTenant());

                    Notification::make()->title('บันทึกความมั่งคั่งสุทธิของวันนี้แล้ว')->success()->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }
}
