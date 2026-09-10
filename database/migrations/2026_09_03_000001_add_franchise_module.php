<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'franchise_apply_amount')) {
                $table->decimal('franchise_apply_amount', 28, 8)->default(120000)->after('invoice_prefix');
            }
            if (!Schema::hasColumn('general_settings', 'franchise_direct_commission')) {
                $table->decimal('franchise_direct_commission', 8, 2)->default(5)->after('franchise_apply_amount');
            }
        });

        Schema::create('franchise_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('sponsor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('franchise_code', 40)->unique();
            $table->decimal('wallet_balance', 28, 8)->default(0);
            $table->decimal('total_commission', 28, 8)->default(0);
            $table->decimal('total_transfer_sent', 28, 8)->default(0);
            $table->decimal('total_transfer_received', 28, 8)->default(0);
            $table->unsignedInteger('total_direct_referrals')->default(0);
            $table->decimal('application_amount', 28, 8)->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('franchise_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sponsor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 28, 8);
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('franchise_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_profile_id')->constrained('franchise_profiles')->cascadeOnDelete();
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trx', 40)->index();
            $table->string('remark', 50)->nullable()->index();
            $table->string('trx_type', 1);
            $table->decimal('amount', 28, 8);
            $table->decimal('charge', 28, 8)->default(0);
            $table->decimal('post_balance', 28, 8);
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('franchise_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_profile_id')->constrained('franchise_profiles')->cascadeOnDelete();
            $table->string('invoice_no', 40)->unique();
            $table->decimal('amount', 28, 8);
            $table->string('status', 20)->default('pending');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_invoices');
        Schema::dropIfExists('franchise_transactions');
        Schema::dropIfExists('franchise_applications');
        Schema::dropIfExists('franchise_profiles');

        Schema::table('general_settings', function (Blueprint $table) {
            foreach (['franchise_apply_amount', 'franchise_direct_commission'] as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
