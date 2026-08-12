<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repurchase_matching_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('period_year')->nullable()->after('order_id');
            $table->unsignedTinyInteger('period_month')->nullable()->after('period_year');
            $table->dateTime('period_start')->nullable()->after('period_month');
            $table->dateTime('period_end')->nullable()->after('period_start');
            $table->dateTime('settled_at')->nullable()->after('status');

            $table->unique(['user_id', 'period_year', 'period_month'], 'repurchase_matching_user_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('repurchase_matching_logs', function (Blueprint $table) {
            $table->dropUnique('repurchase_matching_user_period_unique');
            $table->dropColumn([
                'period_year',
                'period_month',
                'period_start',
                'period_end',
                'settled_at',
            ]);
        });
    }
};
