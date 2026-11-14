<?php

use App\Models\CertificateTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CertificateTemplate::query()->where('type', 'cor')->delete();

        if (Schema::hasColumn('public_site_settings', 'cor_statement')) {
            Schema::table('public_site_settings', function (Blueprint $table): void {
                $table->dropColumn('cor_statement');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('public_site_settings', 'cor_statement')) {
            Schema::table('public_site_settings', function (Blueprint $table): void {
                $table->text('cor_statement')->nullable()->after('tor_title');
            });
        }
    }
};