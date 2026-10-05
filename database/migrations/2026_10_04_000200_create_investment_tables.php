<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // สิ่งที่ถือได้: หุ้น กองทุน ทองคำ ฯลฯ
        // workspace_id = null คือสินทรัพย์กลางที่ทุก workspace ใช้ร่วมกัน (เช่น PTT, ทองแท่ง 96.5%)
        // workspace_id มีค่า คือสินทรัพย์ที่ผู้ใช้สร้างเองและเห็นเฉพาะ workspace นั้น
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type'); // stock | fund | gold | bond | crypto | other
            $table->string('symbol'); // PTT, K-USXNDQ-A(A), GOLD965
            $table->string('name');
            $table->string('exchange')->nullable(); // SET, NASDAQ ...
            $table->char('currency', 3)->default('THB'); // สกุลเงินของราคา
            $table->string('unit')->default('share'); // share | unit | baht_weight | gram | oz
            $table->timestamps();

            $table->unique(['workspace_id', 'type', 'symbol', 'exchange']);
        });

        // ราคาต่อ 1 หน่วย รายวัน ใช้ตีมูลค่าพอร์ต
        // ราคาต่อหน่วยมีทศนิยมย่อยกว่าสตางค์ได้ (NAV กองทุน 4 ตำแหน่ง) จึงใช้ decimal
        // ทองคำ: ใช้ "ราคารับซื้อ" เพราะเป็นราคาที่ขายออกได้จริง
        Schema::create('asset_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('price', 24, 8);
            $table->string('source')->nullable(); // manual | ชื่อแหล่งข้อมูล
            $table->timestamps();

            $table->unique(['asset_id', 'date']);
        });

        // รายการซื้อขาย = ข้อมูลต้นทาง (source of truth) ของพอร์ต
        Schema::create('asset_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete(); // บัญชีหุ้น / บัญชีทอง
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            // รายการเงินสดคู่กัน (เงินที่จ่ายซื้อ / รับจากการขาย / ปันผล) ถ้ามี
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            // buy | sell | dividend | split | bonus | transfer_in | transfer_out
            $table->string('type');
            // บวก = หน่วยเพิ่ม, ลบ = หน่วยลด (dividend เป็นเงินสดให้ใส่ 0)
            $table->decimal('quantity', 24, 8)->default(0);
            $table->decimal('price', 24, 8)->nullable(); // ราคาต่อหน่วยที่ทำรายการ
            // จำนวนเงินเก็บเป็นสตางค์แบบจำนวนเต็ม
            $table->bigInteger('fee')->default(0); // ค่าคอม + VAT + ค่ากำเหน็จ
            $table->bigInteger('amount')->default(0); // ยอดสุทธิ: ลบ = จ่ายออก, บวก = รับเข้า
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'asset_id', 'date']);
            $table->index(['workspace_id', 'date']);
        });

        // ยอดถือครองปัจจุบัน = ผลสรุปที่คำนวณจาก asset_trades
        // เป็น cache เท่านั้น ห้ามแก้ตรง ๆ ให้คำนวณใหม่ทุกครั้งที่ trade เปลี่ยน
        Schema::create('holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 24, 8)->default(0);
            $table->bigInteger('cost_basis')->default(0); // ต้นทุนรวมของหน่วยที่ยังถืออยู่ (สตางค์)
            $table->bigInteger('realized_gain')->default(0); // กำไรขาดทุนที่รับรู้แล้วสะสม (สตางค์)
            $table->timestamps();

            $table->unique(['account_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holdings');
        Schema::dropIfExists('asset_trades');
        Schema::dropIfExists('asset_prices');
        Schema::dropIfExists('assets');
    }
};
