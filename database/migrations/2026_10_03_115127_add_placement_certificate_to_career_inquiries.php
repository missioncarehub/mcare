<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_inquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('career_inquiries', 'placement_certificate')) {
                $table->json('placement_certificate')->nullable()->after('credential_paths');
            }
            if (! Schema::hasColumn('career_inquiries', 'certificate_status')) {
                $table->string('certificate_status', 30)->nullable()->after('placement_certificate');
            }
            if (! Schema::hasColumn('career_inquiries', 'certificate_admin_notes')) {
                $table->text('certificate_admin_notes')->nullable()->after('certificate_status');
            }
            if (! Schema::hasColumn('career_inquiries', 'certificate_reviewed_by_id')) {
                $table->foreignId('certificate_reviewed_by_id')->nullable()->after('certificate_admin_notes')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('career_inquiries', 'certificate_reviewed_at')) {
                $table->timestamp('certificate_reviewed_at')->nullable()->after('certificate_reviewed_by_id');
            }
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
