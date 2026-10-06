<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ภาพความมั่งคั่งสุทธิ ณ วันหนึ่ง ใช้ทำกราฟแนวโน้ม
        // ความมั่งคั่งคำนวณได้เฉพาะ "ตอนนี้" (ราคา/อัตราแลกเปลี่ยนล่าสุด) จึงต้องบันทึกไว้เป็นระยะ
        // เงินทุกช่องเป็นสตางค์ในสกุล base_currency ของ workspace ณ ตอนบันทึก
        Schema::create('net_worth_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->char('currency', 3);
            $table->bigInteger('cash')->default(0);
            $table->bigInteger('investment_cash')->default(0);
            $table->bigInteger('investments')->default(0);
            $table->bigInteger('insurance')->default(0);
            $table->bigInteger('fixed_assets')->default(0);
            $table->bigInteger('total_assets')->default(0);
            $table->bigInteger('loans')->default(0);
            $table->bigInteger('credit_card_debt')->default(0);
            $table->bigInteger('total_liabilities')->default(0);
            $table->bigInteger('net_worth')->default(0);
            // จำนวนรายการที่ไม่ถูกนับเพราะยังไม่มีอัตราแลกเปลี่ยน
            $table->unsignedInteger('unconverted_items')->default(0);
            $table->timestamps();

            $table->unique(['workspace_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('net_worth_snapshots');
    }
};
