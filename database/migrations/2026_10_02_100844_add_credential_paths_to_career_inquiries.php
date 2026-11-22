<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_inquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('career_inquiries', 'credential_paths')) {
                $table->json('credential_paths')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('career_inquiries', function (Blueprint $table) {
            $table->dropColumn('credential_paths');
        });
    }
};
