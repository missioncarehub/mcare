<?php

namespace Tests\Feature\DocumentStudio;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentStudioMigrationTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = DB::getDefaultConnection();
        $connection = getenv('MCARE_MYSQL_MIGRATION_TEST') === '1'
            ? ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => getenv('MCARE_MYSQL_TEST_PORT') ?: '3306',
                'database' => 'mcare_migration_test', 'username' => 'root', 'password' => getenv('MCARE_MYSQL_TEST_PASSWORD') ?: '',
                'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true]
            : ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.studio_migration_test' => $connection]);
        DB::purge('studio_migration_test');
        DB::setDefaultConnection('studio_migration_test');
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('training_programs', fn (Blueprint $table) => $table->id());
        Schema::create('official_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('template_version')->default(1);
        });
        DB::table('official_documents')->insert(['id' => 1, 'template_version' => 7]);
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['document_assets', 'official_documents', 'document_template_versions', 'document_templates', 'training_programs', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
        DB::setDefaultConnection($this->previousConnection);
        DB::purge('studio_migration_test');
        parent::tearDown();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_07_000001_create_document_studio_tables.php');
    }

    public function test_repeat_install_keeps_existing_document_history(): void
    {
        $migration = $this->migration();
        $migration->up();
        $migration->up();
        $this->assertSame(7, (int) DB::table('official_documents')->value('template_version'));
        $this->assertTrue(Schema::hasTable('document_assets'));
        $this->assertTrue(Schema::hasColumn('official_documents', 'document_template_version_id'));
        $this->assertCount(1, Schema::getForeignKeys('official_documents'));
        $migration->down();
        $this->assertSame(7, (int) DB::table('official_documents')->value('template_version'));
        $this->assertFalse(Schema::hasTable('document_templates'));
    }

    public function test_partial_install_recovers_a_missing_version_pointer_foreign_key(): void
    {
        $migration = $this->migration();
        $migration->up();
        Schema::table('official_documents', fn (Blueprint $table) => $table->dropForeign(['document_template_version_id']));
        $migration->up();
        $this->assertCount(1, Schema::getForeignKeys('official_documents'));
        $this->assertSame(7, (int) DB::table('official_documents')->value('template_version'));
    }
}
