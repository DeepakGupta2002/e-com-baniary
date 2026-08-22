<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('general_settings')) {
            Schema::table('general_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('general_settings', 'gst_status')) {
                    $table->boolean('gst_status')->default(false);
                }
                if (!Schema::hasColumn('general_settings', 'gst_type')) {
                    $table->string('gst_type', 20)->default('exclusive');
                }
                if (!Schema::hasColumn('general_settings', 'gst_percent')) {
                    $table->decimal('gst_percent', 8, 2)->default(0);
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'subtotal')) {
                    $table->decimal('subtotal', 28, 8)->default(0)->after('price');
                }
                if (!Schema::hasColumn('orders', 'gst_status')) {
                    $table->boolean('gst_status')->default(false)->after('subtotal');
                }
                if (!Schema::hasColumn('orders', 'gst_type')) {
                    $table->string('gst_type', 20)->nullable()->after('gst_status');
                }
                if (!Schema::hasColumn('orders', 'gst_percent')) {
                    $table->decimal('gst_percent', 8, 2)->default(0)->after('gst_type');
                }
                if (!Schema::hasColumn('orders', 'gst_amount')) {
                    $table->decimal('gst_amount', 28, 8)->default(0)->after('gst_percent');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                foreach (['subtotal', 'gst_status', 'gst_type', 'gst_percent', 'gst_amount'] as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('general_settings')) {
            Schema::table('general_settings', function (Blueprint $table) {
                foreach (['gst_status', 'gst_type', 'gst_percent'] as $column) {
                    if (Schema::hasColumn('general_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
