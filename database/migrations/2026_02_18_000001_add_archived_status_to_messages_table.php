<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        DB::statement("ALTER TABLE `messages` MODIFY `status` ENUM('unread','read','archived') NOT NULL DEFAULT 'unread'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        DB::table('messages')
            ->where('status', 'archived')
            ->update(['status' => 'read']);

        DB::statement("ALTER TABLE `messages` MODIFY `status` ENUM('unread','read') NOT NULL DEFAULT 'unread'");
    }
};
