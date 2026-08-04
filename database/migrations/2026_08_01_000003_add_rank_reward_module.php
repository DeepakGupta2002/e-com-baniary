<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('required_team_dp', 28, 8)->default(0);
            $table->decimal('reward_amount', 28, 8)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::create('rank_reward_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rank_id')->constrained('ranks')->cascadeOnDelete();
            $table->decimal('team_dp', 28, 8)->default(0);
            $table->decimal('reward_amount', 28, 8)->default(0);
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('status', 40)->default('paid');
            $table->timestamps();
            $table->unique(['user_id', 'rank_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_rank_id')->nullable()->after('plan_id')->constrained('ranks')->nullOnDelete();
            $table->decimal('total_team_dp', 28, 8)->default(0)->after('total_binary_com');
            $table->decimal('total_rank_reward', 28, 8)->default(0)->after('total_team_dp');
        });

        DB::table('ranks')->insert([
            ['name' => 'ORIVA Star', 'required_team_dp' => 100000, 'reward_amount' => 5000, 'sort_order' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA Bronze', 'required_team_dp' => 300000, 'reward_amount' => 15000, 'sort_order' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA Silver', 'required_team_dp' => 700000, 'reward_amount' => 35000, 'sort_order' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA Gold', 'required_team_dp' => 1500000, 'reward_amount' => 75000, 'sort_order' => 4, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA Platinum', 'required_team_dp' => 3000000, 'reward_amount' => 150000, 'sort_order' => 5, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA Diamond', 'required_team_dp' => 6000000, 'reward_amount' => 300000, 'sort_order' => 6, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA Crown', 'required_team_dp' => 10000000, 'reward_amount' => 500000, 'sort_order' => 7, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA Ambassador', 'required_team_dp' => 20000000, 'reward_amount' => 1000000, 'sort_order' => 8, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA President', 'required_team_dp' => 50000000, 'reward_amount' => 2500000, 'sort_order' => 9, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORIVA Chairman', 'required_team_dp' => 100000000, 'reward_amount' => 5000000, 'sort_order' => 10, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_rank_id');
            $table->dropColumn(['total_team_dp', 'total_rank_reward']);
        });

        Schema::dropIfExists('rank_reward_logs');
        Schema::dropIfExists('ranks');
    }
};
