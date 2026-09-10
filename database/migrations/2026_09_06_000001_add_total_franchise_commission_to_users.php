<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'total_franchise_com')) {
                $table->decimal('total_franchise_com', 28, 8)->default(0)->after('total_ref_com');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'total_franchise_com')) {
                $table->dropColumn('total_franchise_com');
            }
        });
    }
};
