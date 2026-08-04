<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('plan_code', 80)->nullable()->after('name')->index();
        });

        DB::table('plans')->orderBy('id')->get(['id', 'name'])->each(function ($plan) {
            $code = Str::of($plan->name)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            DB::table('plans')->where('id', $plan->id)->update(['plan_code' => $code]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('plan_activated_at')->nullable()->after('plan_id');
            $table->boolean('fast_start_bonus_claimed')->default(false)->after('plan_activated_at');
            $table->decimal('fast_start_bonus_amount', 28, 8)->default(0)->after('fast_start_bonus_claimed');
            $table->timestamp('fast_start_bonus_date')->nullable()->after('fast_start_bonus_amount');
        });

        Schema::create('fast_start_bonus_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained('users')->cascadeOnDelete();
            $table->string('qualifying_type', 40);
            $table->decimal('bonus_amount', 28, 8);
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('status', 40)->default('paid');
            $table->timestamps();
            $table->unique('user_id', 'fast_start_bonus_logs_user_unique');
            $table->index('sponsor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fast_start_bonus_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'plan_activated_at',
                'fast_start_bonus_claimed',
                'fast_start_bonus_amount',
                'fast_start_bonus_date',
            ]);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('plan_code');
        });
    }
};
