<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verified_banner_dismissed_at')->nullable()->after('email_verified_at');
        });

        Schema::table('admin_announcements', function (Blueprint $table) {
            $table->boolean('send_sms')->default(false)->after('send_email');
            $table->json('sms_delivery')->nullable()->after('send_sms');
        });

        Schema::table('public_site_settings', function (Blueprint $table) {
            $table->string('tor_title')->nullable()->after('registrar_signature_path');
            $table->text('cor_statement')->nullable()->after('tor_title');
        });
    }

    public function down(): void
    {
        Schema::table('public_site_settings', function (Blueprint $table) {
            $table->dropColumn(['tor_title', 'cor_statement']);
        });

        Schema::table('admin_announcements', function (Blueprint $table) {
            $table->dropColumn(['send_sms', 'sms_delivery']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verified_banner_dismissed_at');
        });
    }
};
