<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('mobile', 40);
                $table->text('address');
                $table->string('city', 120);
                $table->string('state', 120);
                $table->string('zip', 40);
                $table->string('country', 120);
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['user_id', 'is_default']);
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'delivery_name')) {
                    $table->string('delivery_name')->nullable()->after('trx');
                }
                if (!Schema::hasColumn('orders', 'delivery_mobile')) {
                    $table->string('delivery_mobile', 40)->nullable()->after('delivery_name');
                }
                if (!Schema::hasColumn('orders', 'delivery_address')) {
                    $table->text('delivery_address')->nullable()->after('delivery_mobile');
                }
                if (!Schema::hasColumn('orders', 'delivery_city')) {
                    $table->string('delivery_city', 120)->nullable()->after('delivery_address');
                }
                if (!Schema::hasColumn('orders', 'delivery_state')) {
                    $table->string('delivery_state', 120)->nullable()->after('delivery_city');
                }
                if (!Schema::hasColumn('orders', 'delivery_zip')) {
                    $table->string('delivery_zip', 40)->nullable()->after('delivery_state');
                }
                if (!Schema::hasColumn('orders', 'delivery_country')) {
                    $table->string('delivery_country', 120)->nullable()->after('delivery_zip');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $columns = [
                    'delivery_name',
                    'delivery_mobile',
                    'delivery_address',
                    'delivery_city',
                    'delivery_state',
                    'delivery_zip',
                    'delivery_country',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('user_addresses');
    }
};
