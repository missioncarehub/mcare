<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificate_templates', 'background_path')) {
                $table->string('background_path')->nullable()->after('use_official_artwork');
            }
            if (! Schema::hasColumn('certificate_templates', 'field_layout')) {
                $table->json('field_layout')->nullable()->after('background_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropColumn(['background_path', 'field_layout']);
        });
    }
};
