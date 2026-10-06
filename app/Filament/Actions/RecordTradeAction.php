<?php

namespace App\Filament\Actions;

use App\Enums\AccountType;
use App\Enums\TradeType;
use App\Filament\Support\Money;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Holding;
use App\Services\TradeService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * ปุ่ม "ซื้อ" / "ขาย" กรอกแค่สินทรัพย์ จำนวน ราคา ค่าธรรมเนียม ระบบคิดยอดสุทธิและเครื่องหมายให้
 * ส่งบัญชีเข้ามาเมื่ออยู่ในหน้าของบัญชีนั้น ถ้าไม่ส่งจะให้เลือกบัญชีลงทุนในฟอร์ม
 */
class RecordTradeAction
{
    public static function buy(?Account $account = null): Action
    {
        return static::make(TradeType::Buy, $account);
    }

    public static function sell(?Account $account = null): Action
    {
        return static::make(TradeType::Sell, $account);
    }

    private static function make(TradeType $type, ?Account $account): Action
    {
        $isBuy = $type === TradeType::Buy;

        return Action::make($isBuy ? 'buy' : 'sell')
            ->label($isBuy ? 'ซื้อ' : 'ขาย')
            ->icon($isBuy ? Heroicon::OutlinedArrowDownTray : Heroicon::OutlinedArrowUpTray)
            ->color($isBuy ? 'success' : 'danger')
            ->modalHeading($isBuy ? 'บันทึกการซื้อ' : 'บันทึกการขาย')
            ->modalSubmitActionLabel('บันทึก')
            ->schema(static::schema($isBuy, $account))
            ->action(function (array $data, Action $action) use ($type, $account): void {
                $account ??= static::investmentAccounts()->findOrFail($data['account_id']);
                $asset = Asset::findOrFail($data['asset_id']);

                try {
                    app(TradeService::class)->record(
                        account: $account,
                        asset: $asset,
                        type: $type,
                        date: $data['date'],
                        quantity: (string) $data['quantity'],
                        price: (string) $data['price'],
                        fee: (int) ($data['fee'] ?? 0),
                        accountAmount: $data['account_amount'] ?? null,
                        recordCash: (bool) ($data['record_cash'] ?? false),
                        notes: $data['notes'] ?? null,
                        createdBy: auth()->id(),
                    );
                } catch (ValidationException $exception) {
                    Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();

                    $action->halt();
                }

                Notification::make()->success()->title($type === TradeType::Buy ? 'บันทึกการซื้อแล้ว' : 'บันทึกการขายแล้ว')->send();
            });
    }

    /**
     * @return array<int, mixed>
     */
    private static function schema(bool $isBuy, ?Account $fixedAccount): array
    {
        $account = fn (Get $get): ?Account => $fixedAccount ?? static::investmentAccounts()->find($get('account_id'));

        return [
            Select::make('account_id')
                ->label('บัญชี')
                ->options(fn (): array => static::investmentAccounts()->pluck('name', 'id')->all())
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('asset_id', null))
                ->hidden($fixedAccount !== null),
            Select::make('asset_id')
                ->label('สินทรัพย์')
                ->options(fn (Get $get): array => static::assetOptions($isBuy, $account($get)))
                ->searchable()
                ->required()
                ->live()
                ->helperText(fn (Get $get): ?string => $isBuy ? null : 'แสดงเฉพาะสินทรัพย์ที่ถือครองอยู่ในบัญชีนี้'),
            DatePicker::make('date')->label('วันที่')->default(now())->required(),
            TextInput::make('quantity')
                ->label('จำนวนหน่วย')
                ->numeric()
                ->step('0.00000001')
                ->minValue(0.00000001)
                ->required()
                ->live(onBlur: true)
                ->helperText(function (Get $get) use ($isBuy, $account): ?string {
                    $held = static::held($account($get), $get('asset_id'));

                    return ! $isBuy && $held !== null ? 'ถือครองอยู่ '.static::trimmed($held).' หน่วย' : null;
                })
                ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($isBuy, $account, $get): void {
                    $held = static::held($account($get), $get('asset_id'));

                    if (! $isBuy && $held !== null && is_numeric($value) && bccomp((string) $value, $held, 8) > 0) {
                        $fail('ขายเกินจำนวนที่ถือครอง (ถืออยู่ '.static::trimmed($held).' หน่วย)');
                    }
                }),
            TextInput::make('price')
                ->label('ราคาต่อหน่วย')
                ->numeric()
                ->step('0.00000001')
                ->minValue(0)
                ->required()
                ->live(onBlur: true)
                ->helperText(function (Get $get): ?string {
                    $quantity = $get('quantity');
                    $price = $get('price');

                    return is_numeric($quantity) && is_numeric($price)
                        ? 'มูลค่ารวม '.number_format((float) bcmul((string) $quantity, (string) $price, 8), 2)
                        : null;
                }),
            Money::input('fee', 'ค่าธรรมเนียมรวม (คอมมิชชั่น + VAT)')->default(0)->required(),
            Money::input('account_amount', 'ยอดสุทธิเป็นสกุลของบัญชี')
                ->helperText('สกุลสินทรัพย์ต่างจากสกุลบัญชี กรอกยอดที่จ่าย/ได้รับจริง')
                ->visible(fn (Get $get): bool => static::currenciesDiffer($account($get), $get('asset_id')))
                ->required(fn (Get $get): bool => static::currenciesDiffer($account($get), $get('asset_id'))),
            Toggle::make('record_cash')
                ->label('บันทึกรายการเงินสดในบัญชีนี้ด้วย')
                ->helperText($isBuy ? 'หักเงินสดในบัญชีตามยอดที่จ่ายซื้อ' : 'เพิ่มเงินสดในบัญชีตามยอดที่ได้จากการขาย')
                ->default(true),
            Textarea::make('notes')->label('บันทึก'),
        ];
    }

    /**
     * @return Builder<Account>
     */
    private static function investmentAccounts(): Builder
    {
        return Account::query()
            ->forWorkspace(Filament::getTenant())
            ->where('is_archived', false)
            ->whereIn('type', [AccountType::Brokerage, AccountType::Fund, AccountType::Gold])
            ->orderBy('name');
    }

    /**
     * @return array<int, string>
     */
    private static function assetOptions(bool $isBuy, ?Account $account): array
    {
        $query = Asset::query()
            ->where(function (Builder $query): void {
                $query->whereNull('workspace_id')->orWhere('workspace_id', Filament::getTenant()?->getKey());
            })
            ->orderBy('symbol');

        if (! $isBuy) {
            $query->whereIn('id', $account === null ? [] : Holding::query()
                ->where('account_id', $account->getKey())
                ->where('quantity', '>', 0)
                ->pluck('asset_id'));
        }

        return $query->get()->mapWithKeys(fn (Asset $asset): array => [$asset->getKey() => "{$asset->symbol} - {$asset->name}"])->all();
    }

    private static function held(?Account $account, mixed $assetId): ?string
    {
        $asset = $account === null || blank($assetId) ? null : Asset::find($assetId);

        return $asset === null ? null : app(TradeService::class)->heldQuantity($account, $asset);
    }

    private static function currenciesDiffer(?Account $account, mixed $assetId): bool
    {
        $asset = blank($assetId) ? null : Asset::find($assetId);

        return $account !== null && $asset !== null && $account->currency !== $asset->currency;
    }

    private static function trimmed(string $number): string
    {
        return str_contains($number, '.') ? rtrim(rtrim($number, '0'), '.') : $number;
    }
}
