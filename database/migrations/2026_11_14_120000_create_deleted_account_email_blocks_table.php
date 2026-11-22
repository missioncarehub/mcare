<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deleted_account_email_blocks')) {
            Schema::create('deleted_account_email_blocks', function (Blueprint $table) {
                $table->id();
                $table->char('email_hash', 64)->unique();
                $table->string('deleted_role', 30);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_account_email_blocks');
    }
};