<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_templates')) {
            Schema::create('certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->string('type', 16);
                $table->unsignedInteger('version');
                $table->boolean('is_active')->default(false);
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('paper_size', 16);
                $table->string('orientation', 16);
                $table->boolean('use_official_artwork')->default(false);
                $table->string('logo_path')->nullable();
                $table->string('signatory_name')->nullable();
                $table->string('signatory_role')->nullable();
                $table->string('signature_path')->nullable();
                $table->string('second_signatory_name')->nullable();
                $table->string('second_signatory_role')->nullable();
                $table->string('second_signature_path')->nullable();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['type', 'version']);
                $table->index(['type', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
