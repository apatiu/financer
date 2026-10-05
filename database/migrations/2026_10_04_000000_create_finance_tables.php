<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // สมุดบัญชี 1 เล่ม = 1 tenant ของ Filament
        // ผู้ใช้ใหม่ทุกคนได้ workspace ส่วนตัว 1 อัน และเชิญคนอื่นเข้ามาทีหลังได้
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->char('base_currency', 3)->default('THB');
            $table->timestamps();
        });

        Schema::create('workspace_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member'); // owner | member | viewer
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // bank | cash | credit_card | brokerage | gold
            $table->string('institution')->nullable(); // ชื่อธนาคาร / โบรกเกอร์ / ร้านทอง
            $table->char('currency', 3)->default('THB');
            // จำนวนเงินทุกช่องเก็บเป็นหน่วยย่อย (สตางค์) แบบจำนวนเต็ม
            $table->bigInteger('opening_balance')->default(0);
            $table->date('opened_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['workspace_id', 'type']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('type'); // income | expense
            $table->timestamps();

            $table->unique(['workspace_id', 'parent_id', 'name']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            // บวก = เงินเข้า, ลบ = เงินออก (หน่วยย่อยของสกุลเงินบัญชี)
            $table->bigInteger('amount');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            // การโอน = 2 แถวที่ใช้ transfer_group_id เดียวกัน (ออกจากบัญชีหนึ่ง เข้าอีกบัญชีหนึ่ง)
            $table->uuid('transfer_group_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'date']);
            $table->index(['workspace_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');
    }
};
