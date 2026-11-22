<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'verified_banner_dismissed_at')) {
                $table->timestamp('verified_banner_dismissed_at')->nullable()->after('email_verified_at');
            }
        });

        Schema::table('admin_announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_announcements', 'send_sms')) {
                $table->boolean('send_sms')->default(false)->after('send_email');
            }
            if (! Schema::hasColumn('admin_announcements', 'sms_delivery')) {
                $table->json('sms_delivery')->nullable()->after('send_sms');
            }
        });

        Schema::table('public_site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('public_site_settings', 'tor_title')) {
                $table->string('tor_title')->nullable()->after('registrar_signature_path');
            }
            if (! Schema::hasColumn('public_site_settings', 'cor_statement')) {
                $table->text('cor_statement')->nullable()->after('tor_title');
            }
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
