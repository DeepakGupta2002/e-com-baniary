<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'company_name')) {
                $table->string('company_name')->nullable()->after('gst_percent');
            }
            if (!Schema::hasColumn('general_settings', 'company_address')) {
                $table->text('company_address')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('general_settings', 'company_mobile')) {
                $table->string('company_mobile', 40)->nullable()->after('company_address');
            }
            if (!Schema::hasColumn('general_settings', 'company_email')) {
                $table->string('company_email')->nullable()->after('company_mobile');
            }
            if (!Schema::hasColumn('general_settings', 'company_gstin')) {
                $table->string('company_gstin', 40)->nullable()->after('company_email');
            }
            if (!Schema::hasColumn('general_settings', 'company_pan')) {
                $table->string('company_pan', 20)->nullable()->after('company_gstin');
            }
            if (!Schema::hasColumn('general_settings', 'invoice_prefix')) {
                $table->string('invoice_prefix', 20)->default('INV')->after('company_pan');
            }
        });

        $general = DB::table('general_settings')->first();
        if (!$general) {
            return;
        }

        $footerAddress = $this->footerAddress();

        DB::table('general_settings')->where('id', $general->id)->update([
            'company_name' => $general->company_name ?: $general->site_name,
            'company_address' => $general->company_address ?: $footerAddress,
            'company_email' => $general->company_email ?: $general->email_from,
            'invoice_prefix' => $general->invoice_prefix ?: 'INV',
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            foreach (['company_name', 'company_address', 'company_mobile', 'company_email', 'company_gstin', 'company_pan', 'invoice_prefix'] as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function footerAddress(): ?string
    {
        $footer = DB::table('frontends')->where('data_keys', 'footer.content')->value('data_values');
        $footerData = $footer ? json_decode($footer) : null;

        if (!empty($footerData->website_footer_address)) {
            return $footerData->website_footer_address;
        }

        $location = DB::table('frontends')
            ->where('data_keys', 'contact_us.element')
            ->where('data_values', 'like', '%Company Location%')
            ->value('data_values');
        $locationData = $location ? json_decode($location) : null;

        return $locationData->content ?? null;
    }
};
