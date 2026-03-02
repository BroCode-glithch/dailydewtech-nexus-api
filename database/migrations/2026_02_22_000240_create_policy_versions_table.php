<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('policy_versions')) {
            Schema::create('policy_versions', function (Blueprint $table) {
                $table->id();
                $table->string('policy_key', 50);
                $table->string('version', 50);
                $table->string('title', 150)->nullable();
                $table->date('effective_date')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->unique(['policy_key', 'version']);
                $table->index(['policy_key', 'is_active']);
            });
        }

        $now = now();
        $defaults = [
            [
                'policy_key' => 'terms',
                'version' => '2026-02-22',
                'title' => 'Terms and Conditions',
                'effective_date' => '2026-02-22',
                'is_active' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'policy_key' => 'privacy',
                'version' => '2026-02-22',
                'title' => 'Privacy Policy',
                'effective_date' => '2026-02-22',
                'is_active' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'policy_key' => 'ip_policy',
                'version' => '2026-02-22',
                'title' => 'IP and Content Ownership Policy',
                'effective_date' => '2026-02-22',
                'is_active' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'policy_key' => 'community_guidelines',
                'version' => '2026-02-22',
                'title' => 'Community Guidelines',
                'effective_date' => '2026-02-22',
                'is_active' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($defaults as $row) {
            DB::table('policy_versions')->updateOrInsert(
                ['policy_key' => $row['policy_key'], 'version' => $row['version']],
                $row
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('policy_versions')) {
            Schema::drop('policy_versions');
        }
    }
};
