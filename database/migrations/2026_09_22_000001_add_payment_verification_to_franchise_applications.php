<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('franchise_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('franchise_applications', 'payment_method')) {
                $table->string('payment_method', 40)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('franchise_applications', 'payment_reference')) {
                $table->string('payment_reference', 100)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('franchise_applications', 'payment_proof')) {
                $table->string('payment_proof')->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('franchise_applications', 'payment_status')) {
                $table->string('payment_status', 30)->default('pending_verification')->after('payment_proof');
            }
            if (!Schema::hasColumn('franchise_applications', 'payment_verified_at')) {
                $table->timestamp('payment_verified_at')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('franchise_applications', 'payment_verified_by')) {
                $table->unsignedBigInteger('payment_verified_by')->nullable()->after('payment_verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('franchise_applications', function (Blueprint $table) {
            foreach ([
                'payment_verified_by',
                'payment_verified_at',
                'payment_status',
                'payment_proof',
                'payment_reference',
                'payment_method',
            ] as $column) {
                if (Schema::hasColumn('franchise_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
