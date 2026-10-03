<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_inquiries', function (Blueprint $table) {
            $table->json('placement_certificate')->nullable()->after('credential_paths');
            $table->string('certificate_status', 30)->nullable()->after('placement_certificate');
            $table->text('certificate_admin_notes')->nullable()->after('certificate_status');
            $table->foreignId('certificate_reviewed_by_id')->nullable()->after('certificate_admin_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('certificate_reviewed_at')->nullable()->after('certificate_reviewed_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('career_inquiries', function (Blueprint $table) {
            $table->dropForeign(['certificate_reviewed_by_id']);
            $table->dropColumn([
                'placement_certificate',
                'certificate_status',
                'certificate_admin_notes',
                'certificate_reviewed_by_id',
                'certificate_reviewed_at',
            ]);
        });
    }
};
