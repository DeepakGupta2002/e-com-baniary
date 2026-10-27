<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('rank_cycle_left_bv', 28, 8)->default(0)->after('total_rank_reward');
            $table->decimal('rank_cycle_right_bv', 28, 8)->default(0)->after('rank_cycle_left_bv');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rank_cycle_left_bv', 'rank_cycle_right_bv']);
        });
    }
};
