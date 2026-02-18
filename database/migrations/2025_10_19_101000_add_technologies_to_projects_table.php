<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects') && !Schema::hasColumn('projects', 'technologies')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->json('technologies')->nullable()->after('category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'technologies')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('technologies');
            });
        }
    }
};
