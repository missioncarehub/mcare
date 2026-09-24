<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni_profiles', function (Blueprint $table) {
            $table->string('rank', 20)->default('junior')->after('user_id');
            $table->timestamp('rank_promoted_at')->nullable()->after('rank');
            $table->foreignId('rank_promoted_by_id')->nullable()->after('rank_promoted_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alumni_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rank_promoted_by_id');
            $table->dropColumn(['rank', 'rank_promoted_at']);
        });
    }
};
