<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ทรัพย์สินที่จับต้องได้และไม่มียอดเงิน/จำนวนหน่วย: ที่ดิน บ้าน คอนโด รถยนต์
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // land | house | condo | vehicle | other
            $table->string('name'); // เช่น "ที่ดินโฉนด 12345 อ.เมือง", "Toyota Yaris ทะเบียน กข 1234"
            // ส่วนที่ถือครอง (%) ใช้คูณมูลค่าตอนรวมความมั่งคั่ง เช่น ที่ดินร่วมกับญาติ 50%
            $table->decimal('ownership_percent', 5, 2)->default(100);
            $table->char('currency', 3)->default('THB');
            // ชื่อเจ้าของถ้าไม่ใช่ตัวเอง (คนในครอบครัว) และสวิตช์เปิด/ปิดการนับรวมในความมั่งคั่งสุทธิ
            $table->string('owner_name')->nullable();
            $table->boolean('include_in_net_worth')->default(true);
            $table->date('acquired_on')->nullable();
            $table->bigInteger('purchase_price')->default(0); // สตางค์
            // ข้อมูลเฉพาะชนิด: ที่ดิน {deed_number, rai, ngan, sq_wah, location}
            // รถ {plate, brand, model, year, tax_due_on, compulsory_insurance_due_on}
            $table->json('details')->nullable();
            $table->string('status')->default('owned'); // owned | sold
            $table->date('sold_on')->nullable();
            $table->bigInteger('sold_price')->nullable(); // สตางค์
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'type']);
        });

        // มูลค่า ณ วันต่าง ๆ (ราคาประเมิน / ราคาตลาด / ค่าเสื่อม) ใช้ค่าล่าสุดที่ไม่เกินวันนี้
        Schema::create('fixed_asset_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained()->cascadeOnDelete();
            $table->date('as_of_date');
            $table->bigInteger('value'); // สตางค์ ของทั้งทรัพย์สิน (ยังไม่คูณสัดส่วนถือครอง)
            $table->string('method')->default('manual'); // appraisal | market | depreciation | manual
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['fixed_asset_id', 'as_of_date']);
        });

        // หนี้สิน: สินเชื่อรถ จำนอง ผ่อนบ้าน (บัตรเครดิตใช้ accounts ชนิด credit_card)
        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type'); // car_loan | mortgage | personal_loan | other
            $table->string('lender')->nullable();
            $table->char('currency', 3)->default('THB');
            // ชื่อเจ้าของถ้าไม่ใช่ตัวเอง (คนในครอบครัว) และสวิตช์เปิด/ปิดการนับรวมในความมั่งคั่งสุทธิ
            $table->string('owner_name')->nullable();
            $table->boolean('include_in_net_worth')->default(true);
            $table->bigInteger('principal')->default(0); // เงินต้นตอนกู้ (สตางค์)
            $table->bigInteger('outstanding_balance')->default(0); // ยอดคงค้างล่าสุด (สตางค์)
            $table->decimal('interest_rate', 6, 3)->nullable(); // % ต่อปี
            $table->bigInteger('monthly_payment')->default(0); // สตางค์
            $table->date('started_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status')->default('active'); // active | closed
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liabilities');
        Schema::dropIfExists('fixed_asset_valuations');
        Schema::dropIfExists('fixed_assets');
    }
};
