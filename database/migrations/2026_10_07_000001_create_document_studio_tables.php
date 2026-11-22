<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_templates')) {
            Schema::create('document_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 150);
                $table->string('slug', 150)->unique();
                $table->string('document_kind', 30)->index(); // 'cotc', 'tor', 'soa', 'general'
                $table->string('engine', 30); // 'pdfme', 'fabric', 'rk_word'
                $table->foreignId('training_program_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('active_version_id')->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system_default')->default(false);
                $table->unsignedInteger('draft_revision')->default(1);
                $table->longText('draft_state')->nullable();
                $table->timestamp('draft_updated_at')->nullable();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('document_template_versions')) {
            Schema::create('document_template_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('document_template_id')->constrained()->restrictOnDelete();
                $table->unsignedSmallInteger('version_number');
                $table->string('schema_version', 20)->default('1.0');
                $table->string('engine', 30);
                $table->string('engine_version', 30)->nullable();
                $table->json('page_settings')->nullable();
                $table->longText('editor_state');
                $table->json('placeholder_contract')->nullable();
                $table->string('sha256', 64);
                $table->text('changelog')->nullable();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(
                    ['document_template_id', 'version_number'],
                    'document_template_version_unique'
                );
            });
        }

        if (! $this->hasForeignKey('document_templates', 'active_version_id')) {
            try {
                Schema::table('document_templates', function (Blueprint $table): void {
                    $table->foreign('active_version_id')
                        ->references('id')
                        ->on('document_template_versions')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
            }
        }

        if (! Schema::hasTable('document_assets')) {
            Schema::create('document_assets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('document_template_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('type', 30)->index(); // 'image', 'pdf_base', 'font', 'signature', 'logo'
                $table->string('disk', 40)->default('local');
                $table->string('file_path', 255);
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('file_size')->default(0);
                $table->json('dimensions')->nullable();
                $table->string('sha256', 64);
                $table->boolean('is_system')->default(false);
                $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('official_documents', 'document_template_version_id')) {
            Schema::table('official_documents', function (Blueprint $table): void {
                $table->unsignedBigInteger('document_template_version_id')->nullable()->after('template_version');
            });
        }
        if (! $this->hasForeignKey('official_documents', 'document_template_version_id')) {
            try {
                Schema::table('official_documents', function (Blueprint $table): void {
                    $table->foreign('document_template_version_id')->references('id')->on('document_template_versions')->restrictOnDelete();
                });
            } catch (\Throwable) {
            }
        }
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        try {
            return collect(Schema::getForeignKeys($table))->contains(fn ($key) => ($key['columns'] ?? []) === [$column]);
        } catch (\Throwable) {
            return false;
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('official_documents', 'document_template_version_id')) {
            Schema::table('official_documents', function (Blueprint $table): void {
                if ($this->hasForeignKey('official_documents', 'document_template_version_id')) {
                    $table->dropForeign(['document_template_version_id']);
                }
                $table->dropColumn('document_template_version_id');
            });
        }

        Schema::dropIfExists('document_assets');

        if (Schema::hasTable('document_templates') && $this->hasForeignKey('document_templates', 'active_version_id')) {
            Schema::table('document_templates', function (Blueprint $table): void {
                $table->dropForeign(['active_version_id']);
            });
        }

        Schema::dropIfExists('document_template_versions');
        Schema::dropIfExists('document_templates');
    }
};
