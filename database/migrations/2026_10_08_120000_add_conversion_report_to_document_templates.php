<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('document_templates', 'conversion_report')) {
            Schema::table('document_templates', fn (Blueprint $table) => $table->json('conversion_report')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_templates', 'conversion_report')) {
            Schema::table('document_templates', fn (Blueprint $table) => $table->dropColumn('conversion_report'));
        }
    }
};
