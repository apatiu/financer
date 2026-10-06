<?php

namespace App\Services;

use App\Enums\FixedAssetStatus;
use App\Enums\FixedAssetType;
use App\Enums\InsuranceStatus;
use App\Models\FixedAsset;
use App\Models\InsurancePolicy;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * รวมรายการที่ใกล้ครบกำหนดหรือเลยกำหนดแล้ว เรียงจากเร่งด่วนสุด
 * รายการเลยกำหนดจะยังแสดงอยู่จนกว่าผู้ใช้จะอัปเดตวันที่ถัดไปหรือสถานะ
 *
 * @phpstan-type Reminder array{
 *     key: string, kind: string, title: string, due_on: string, days_left: int,
 *     owner_name: string|null, resource: string, record_id: int
 * }
 */
class DueReminderService
{
    public const DEFAULT_WINDOW_DAYS = 30;

    /**
     * @return Collection<int, array{key: string, kind: string, title: string, due_on: string, days_left: int, owner_name: string|null, resource: string, record_id: int}>
     */
    public function upcoming(Workspace $workspace, int $withinDays = self::DEFAULT_WINDOW_DAYS): Collection
    {
        $today = CarbonImmutable::today();
        $until = $today->addDays($withinDays);

        $reminders = collect();

        $workspace->insurancePolicies()
            ->whereIn('status', [InsuranceStatus::Active, InsuranceStatus::PaidUp])
            ->get()
            ->each(function (InsurancePolicy $policy) use (&$reminders, $today, $until): void {
                if ($policy->status === InsuranceStatus::Active) {
                    $reminders->push($this->reminder($policy, 'premium', 'จ่ายเบี้ยประกัน', $policy->name, $policy->next_premium_due, $today, $until));
                }

                $reminders->push($this->reminder($policy, 'maturity', 'ประกันครบกำหนดสัญญา', $policy->name, $policy->maturity_date, $today, $until));
            });

        $workspace->fixedAssets()
            ->where('type', FixedAssetType::Vehicle)
            ->where('status', FixedAssetStatus::Owned)
            ->get()
            ->each(function (FixedAsset $vehicle) use (&$reminders, $today, $until): void {
                $reminders->push($this->reminder($vehicle, 'vehicle_tax', 'ต่อภาษีรถ', $vehicle->name, $vehicle->details['tax_due_on'] ?? null, $today, $until));
                $reminders->push($this->reminder($vehicle, 'compulsory_insurance', 'ต่อ พ.ร.บ.', $vehicle->name, $vehicle->details['compulsory_insurance_due_on'] ?? null, $today, $until));
            });

        return $reminders->filter()->sortBy('due_on')->values();
    }

    /**
     * @return array{key: string, kind: string, title: string, due_on: string, days_left: int, owner_name: string|null, resource: string, record_id: int}|null
     */
    private function reminder(
        InsurancePolicy|FixedAsset $record,
        string $kind,
        string $label,
        string $name,
        CarbonInterface|string|null $dueOn,
        CarbonImmutable $today,
        CarbonImmutable $until,
    ): ?array {
        $dueOn = $this->date($dueOn);

        if ($dueOn === null || $dueOn->greaterThan($until)) {
            return null;
        }

        return [
            'key' => "{$kind}-{$record->getKey()}",
            'kind' => $label,
            'title' => $name,
            'due_on' => $dueOn->toDateString(),
            'days_left' => (int) $today->diffInDays($dueOn, false),
            'owner_name' => $record->owner_name,
            'resource' => $record instanceof InsurancePolicy ? 'insurance' : 'fixed_asset',
            'record_id' => $record->getKey(),
        ];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
