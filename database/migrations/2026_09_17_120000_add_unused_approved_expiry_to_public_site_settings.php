<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_site_settings', function (Blueprint $table): void {
            $table->string('unused_approved_expiry_mode', 20)->default('off')->after('registrar_signature_path');
            $table->unsignedSmallInteger('unused_approved_expiry_amount')->nullable()->after('unused_approved_expiry_mode');
            $table->date('unused_approved_expiry_date')->nullable()->after('unused_approved_expiry_amount');
        });
    }

    public function down(): void
    {
        Schema::table('public_site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'unused_approved_expiry_mode',
                'unused_approved_expiry_amount',
                'unused_approved_expiry_date',
            ]);
        });
    }
};
