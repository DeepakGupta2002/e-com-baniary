<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('franchise_plans')) {
            Schema::create('franchise_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->decimal('amount', 28, 8);
                $table->decimal('direct_commission', 8, 2);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('franchise_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('franchise_applications', 'franchise_plan_id')) {
                $table->foreignId('franchise_plan_id')->nullable()->after('sponsor_user_id')->constrained('franchise_plans')->nullOnDelete();
            }
        });

        Schema::table('franchise_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('franchise_profiles', 'franchise_plan_id')) {
                $table->foreignId('franchise_plan_id')->nullable()->after('sponsor_user_id')->constrained('franchise_plans')->nullOnDelete();
            }
        });

        $plans = [
            ['name' => 'Starter Franchise', 'amount' => 120000, 'direct_commission' => 8, 'sort_order' => 1, 'status' => 1],
            ['name' => 'Growth Franchise', 'amount' => 250000, 'direct_commission' => 9, 'sort_order' => 2, 'status' => 1],
            ['name' => 'Mega Franchise', 'amount' => 500000, 'direct_commission' => 10, 'sort_order' => 3, 'status' => 1],
        ];

        foreach ($plans as $plan) {
            DB::table('franchise_plans')->updateOrInsert(
                ['name' => $plan['name']],
                array_merge($plan, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        Schema::table('franchise_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('franchise_profiles', 'franchise_plan_id')) {
                $table->dropConstrainedForeignId('franchise_plan_id');
            }
        });

        Schema::table('franchise_applications', function (Blueprint $table) {
            if (Schema::hasColumn('franchise_applications', 'franchise_plan_id')) {
                $table->dropConstrainedForeignId('franchise_plan_id');
            }
        });

        Schema::dropIfExists('franchise_plans');
    }
};
