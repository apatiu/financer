<?php

namespace Database\Seeders;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // สินทรัพย์กลางที่ทุก workspace ใช้ร่วมกัน (ราคาใช้ราคารับซื้อ กรอกเองที่หน้าสินทรัพย์ลงทุน)
        Asset::updateOrCreate(
            ['scope_key' => 0, 'type' => AssetType::Gold, 'symbol' => 'GOLD965', 'exchange' => ''],
            ['name' => 'ทองคำแท่ง 96.5%', 'currency' => 'THB', 'unit' => 'baht_weight'],
        );
    }
}
