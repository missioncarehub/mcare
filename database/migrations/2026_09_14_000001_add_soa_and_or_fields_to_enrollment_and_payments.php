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
            $table->decimal('owwa_deduction', 10, 2)->default(0.00)->after('total_program_fee');
            $table->string('trainee_id_number', 50)->nullable()->after('enrollment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table): void {
            $table->dropColumn(['owwa_deduction', 'trainee_id_number']);
        });
    }
};
