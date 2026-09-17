<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year_graduated')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year_graduated')->nullable(false)->change();
        });
    }
};
