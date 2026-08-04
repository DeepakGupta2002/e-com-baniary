<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('total_level_income', 28, 8)->default(0)->after('total_binary_com');
        });

        Schema::create('level_income_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receiver_user_id');
            $table->unsignedBigInteger('source_user_id');
            $table->unsignedBigInteger('matching_transaction_id');
            $table->unsignedInteger('level_no');
            $table->decimal('matching_income', 28, 8);
            $table->decimal('percentage', 10, 2);
            $table->decimal('amount', 28, 8)->default(0);
            $table->string('status', 40)->default('paid');
            $table->timestamps();

            $table->index('receiver_user_id');
            $table->index('source_user_id');
            $table->index('matching_transaction_id');
            $table->index('level_no');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_income_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('total_level_income');
        });
    }
};
