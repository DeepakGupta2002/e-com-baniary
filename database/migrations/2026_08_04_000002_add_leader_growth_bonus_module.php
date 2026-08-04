<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'leader_growth_cycle_start_at')) {
                $table->timestamp('leader_growth_cycle_start_at')->nullable()->after('fast_start_bonus_date');
            }
            if (!Schema::hasColumn('users', 'leader_growth_current_business')) {
                $table->decimal('leader_growth_current_business', 28, 8)->default(0)->after('leader_growth_cycle_start_at');
            }
            if (!Schema::hasColumn('users', 'leader_growth_total_bonus')) {
                $table->decimal('leader_growth_total_bonus', 28, 8)->default(0)->after('leader_growth_current_business');
            }
            if (!Schema::hasColumn('users', 'leader_growth_bonus_count')) {
                $table->unsignedInteger('leader_growth_bonus_count')->default(0)->after('leader_growth_total_bonus');
            }
            if (!Schema::hasColumn('users', 'leader_growth_last_bonus_at')) {
                $table->timestamp('leader_growth_last_bonus_at')->nullable()->after('leader_growth_bonus_count');
            }
        });

        Schema::create('leader_growth_bonus_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cycle_number');
            $table->timestamp('cycle_start')->nullable();
            $table->timestamp('cycle_end')->nullable();
            $table->decimal('required_business', 28, 8);
            $table->decimal('achieved_business', 28, 8);
            $table->decimal('bonus_amount', 28, 8)->default(0);
            $table->foreignId('matching_transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('status', 40)->default('processed');
            $table->timestamps();

            $table->unique('matching_transaction_id', 'leader_growth_matching_transaction_unique');
            $table->index(['user_id', 'cycle_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leader_growth_bonus_logs');

        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'leader_growth_cycle_start_at',
                'leader_growth_current_business',
                'leader_growth_total_bonus',
                'leader_growth_bonus_count',
                'leader_growth_last_bonus_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
