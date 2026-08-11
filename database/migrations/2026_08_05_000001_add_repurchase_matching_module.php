<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('repurchase_left_bv', 28, 8)->default(0)->after('leader_growth_last_bonus_at');
            $table->decimal('repurchase_right_bv', 28, 8)->default(0)->after('repurchase_left_bv');
            $table->decimal('repurchase_left_carry', 28, 8)->default(0)->after('repurchase_right_bv');
            $table->decimal('repurchase_right_carry', 28, 8)->default(0)->after('repurchase_left_carry');
            $table->decimal('total_repurchase_matching_income', 28, 8)->default(0)->after('repurchase_right_carry');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('repurchase_processed_at')->nullable()->after('status');
        });

        Schema::create('repurchase_bv_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('side', 10);
            $table->decimal('bv', 28, 8);
            $table->string('status', 40)->default('processed');
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'side']);
            $table->index('from_user_id');
            $table->unique(['user_id', 'order_id'], 'repurchase_bv_user_order_unique');
        });

        Schema::create('repurchase_matching_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('left_bv', 28, 8);
            $table->decimal('right_bv', 28, 8);
            $table->decimal('matched_bv', 28, 8);
            $table->decimal('percentage', 8, 2)->default(12);
            $table->decimal('income', 28, 8);
            $table->decimal('carry_left', 28, 8);
            $table->decimal('carry_right', 28, 8);
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('status', 40)->default('paid');
            $table->timestamp('created_at')->nullable();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repurchase_matching_logs');
        Schema::dropIfExists('repurchase_bv_logs');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('repurchase_processed_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'repurchase_left_bv',
                'repurchase_right_bv',
                'repurchase_left_carry',
                'repurchase_right_carry',
                'total_repurchase_matching_income',
            ]);
        });
    }
};
