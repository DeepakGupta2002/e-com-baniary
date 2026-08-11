<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repurchase_matching_logs', function (Blueprint $table) {
            $table->unique(['user_id', 'order_id'], 'repurchase_matching_user_order_unique');
        });
    }

    public function down(): void
    {
        Schema::table('repurchase_matching_logs', function (Blueprint $table) {
            $table->dropUnique('repurchase_matching_user_order_unique');
        });
    }
};
