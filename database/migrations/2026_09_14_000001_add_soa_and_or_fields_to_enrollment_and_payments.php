<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('enrollment_applications', 'owwa_deduction')) {
                $table->decimal('owwa_deduction', 10, 2)->default(0.00)->after('total_program_fee');
            }
            if (! Schema::hasColumn('enrollment_applications', 'trainee_id_number')) {
                $table->string('trainee_id_number', 50)->nullable()->after('enrollment_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table): void {
            $columnsToDrop = [];
            if (Schema::hasColumn('enrollment_applications', 'owwa_deduction')) {
                $columnsToDrop[] = 'owwa_deduction';
            }
            if (Schema::hasColumn('enrollment_applications', 'trainee_id_number')) {
                $columnsToDrop[] = 'trainee_id_number';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
