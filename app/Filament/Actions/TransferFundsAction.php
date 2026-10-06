<?php

namespace App\Filament\Actions;

use App\Filament\Support\Money;
use App\Models\Account;
use App\Services\TransferService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class TransferFundsAction
{
    public static function make(): Action
    {
        return Action::make('transfer')
            ->label('โอนเงินระหว่างบัญชี')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->modalHeading('โอนเงินระหว่างบัญชี')
            ->modalSubmitActionLabel('โอน')
            ->schema([
                Select::make('from_account_id')->label('จากบัญชี')
                    ->options(fn (): array => static::accounts()->pluck('name', 'id')->all())
                    ->searchable()->required()->live(),
                Select::make('to_account_id')->label('ไปยังบัญชี')
                    ->options(fn (): array => static::accounts()->pluck('name', 'id')->all())
                    ->searchable()->required()->live()->different('from_account_id'),
                DatePicker::make('date')->label('วันที่')->default(now())->required(),
                Money::input('amount', 'จำนวนเงินที่โอนออก')
                    ->required()
                    ->minValue(0.01)
                    ->suffix(fn (Get $get): ?string => Account::find($get('from_account_id'))?->currency),
                Money::input('received_amount', 'จำนวนเงินที่ปลายทางได้รับ')
                    ->helperText('ต่างสกุลเงิน กรอกยอดตามที่ได้รับจริง (รวมค่าธรรมเนียมแลกเปลี่ยนแล้ว)')
                    ->visible(fn (Get $get): bool => static::isCrossCurrency($get))
                    ->required(fn (Get $get): bool => static::isCrossCurrency($get))
                    ->suffix(fn (Get $get): ?string => Account::find($get('to_account_id'))?->currency),
                TextInput::make('description')->label('รายละเอียด')->maxLength(255),
            ])
            ->action(function (array $data): void {
                app(TransferService::class)->transfer(
                    from: static::accounts()->findOrFail($data['from_account_id']),
                    to: static::accounts()->findOrFail($data['to_account_id']),
                    amount: $data['amount'],
                    date: $data['date'],
                    receivedAmount: $data['received_amount'] ?? null,
                    description: $data['description'] ?: null,
                    createdBy: auth()->id(),
                );

                Notification::make()->title('โอนเงินเรียบร้อย')->success()->send();
            });
    }

    /**
     * @return Builder<Account>
     */
    private static function accounts(): Builder
    {
        return Account::query()
            ->forWorkspace(Filament::getTenant())
            ->where('is_archived', false)
            ->orderBy('name');
    }

    private static function isCrossCurrency(Get $get): bool
    {
        $from = Account::find($get('from_account_id'));
        $to = Account::find($get('to_account_id'));

        return $from !== null && $to !== null && $from->currency !== $to->currency;
    }
}
