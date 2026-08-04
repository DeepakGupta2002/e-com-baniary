<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('level_income_logs', function (Blueprint $table) {
            $table->unique(
                ['receiver_user_id', 'matching_transaction_id', 'level_no'],
                'level_income_logs_receiver_matching_level_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('level_income_logs', function (Blueprint $table) {
            $table->dropUnique('level_income_logs_receiver_matching_level_unique');
        });
    }
};
