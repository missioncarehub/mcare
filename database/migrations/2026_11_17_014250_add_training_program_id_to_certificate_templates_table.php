<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->foreignId('training_program_id')
                ->nullable()
                ->after('type')
                ->constrained('training_programs')
                ->nullOnDelete();

            $table->index(['type', 'training_program_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropForeign(['training_program_id']);
            $table->dropIndex(['type', 'training_program_id', 'is_active']);
            $table->dropColumn('training_program_id');
        });
    }
};
