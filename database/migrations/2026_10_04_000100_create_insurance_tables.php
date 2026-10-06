<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            // ประกันภัยรถยนต์ / ประกันทรัพย์สิน ผูกกับทรัพย์สินที่คุ้มครอง
            $table->foreignId('fixed_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // ชื่อเรียกเอง เช่น "สะสมทรัพย์ 20/10 ของพ่อ"
            $table->string('insurer'); // บริษัทประกัน
            $table->string('policy_number')->nullable();
            // whole_life | endowment | annuity | unit_linked | term | health | accident | property | motor
            $table->string('type');
            $table->string('insured_name')->nullable(); // ผู้เอาประกัน
            $table->string('beneficiary')->nullable(); // ผู้รับประโยชน์
            // ชื่อเจ้าของถ้าไม่ใช่ตัวเอง (คนในครอบครัว) และสวิตช์เปิด/ปิดการนับรวมในความมั่งคั่งสุทธิ
            $table->string('owner_name')->nullable();
            $table->boolean('include_in_net_worth')->default(true);
            $table->char('currency', 3)->default('THB');

            // จำนวนเงินทุกช่องเก็บเป็นสตางค์แบบจำนวนเต็ม
            $table->bigInteger('sum_assured')->default(0); // ทุนประกัน (ความคุ้มครอง ไม่ใช่ทรัพย์สิน)
            $table->bigInteger('premium_amount')->default(0); // เบี้ยต่องวด
            $table->string('premium_frequency')->default('yearly'); // monthly | quarterly | half_yearly | yearly | single

            $table->date('start_date')->nullable(); // วันเริ่มคุ้มครอง
            $table->date('premium_end_date')->nullable(); // วันสิ้นสุดการชำระเบี้ย
            $table->date('maturity_date')->nullable(); // วันครบกำหนดสัญญา
            $table->date('next_premium_due')->nullable(); // งวดถัดไป ใช้ทำแจ้งเตือน

            $table->string('status')->default('active'); // active | paid_up | lapsed | surrendered | matured | claimed
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
        });

        // มูลค่าเวนคืนเงินสด ณ วันต่าง ๆ = ตัวเลขที่นับเป็นทรัพย์สิน
        // กรอกจากตารางมูลค่าเวนคืนในเล่มกรมธรรม์ (กรอกล่วงหน้าทั้งตารางได้)
        // unit-linked ให้บันทึกมูลค่าหน่วยลงทุนล่าสุดแทน
        Schema::create('insurance_policy_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_policy_id')->constrained()->cascadeOnDelete();
            $table->date('as_of_date');
            $table->bigInteger('cash_value');
            $table->timestamps();

            $table->unique(['insurance_policy_id', 'as_of_date']);
        });

        // ผูกรายการจ่ายเบี้ย / รับเงินคืน / รับสินไหม เข้ากับกรมธรรม์
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('insurance_policy_id')
                ->nullable()
                ->after('category_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('insurance_policy_id');
        });

        Schema::dropIfExists('insurance_policy_values');
        Schema::dropIfExists('insurance_policies');
    }
};
