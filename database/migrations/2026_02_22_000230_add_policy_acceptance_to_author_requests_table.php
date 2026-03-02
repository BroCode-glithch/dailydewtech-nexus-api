<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('author_requests')) {
            return;
        }

        Schema::table('author_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('author_requests', 'accepted_terms_version')) {
                $table->string('accepted_terms_version', 50)->nullable()->after('sample_link');
            }
            if (!Schema::hasColumn('author_requests', 'accepted_privacy_version')) {
                $table->string('accepted_privacy_version', 50)->nullable()->after('accepted_terms_version');
            }
            if (!Schema::hasColumn('author_requests', 'accepted_ip_policy_version')) {
                $table->string('accepted_ip_policy_version', 50)->nullable()->after('accepted_privacy_version');
            }
            if (!Schema::hasColumn('author_requests', 'accepted_community_guidelines_version')) {
                $table->string('accepted_community_guidelines_version', 50)->nullable()->after('accepted_ip_policy_version');
            }
            if (!Schema::hasColumn('author_requests', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('accepted_community_guidelines_version');
            }
            if (!Schema::hasColumn('author_requests', 'accepted_ip')) {
                $table->string('accepted_ip', 45)->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('author_requests', 'accepted_user_agent')) {
                $table->string('accepted_user_agent', 255)->nullable()->after('accepted_ip');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('author_requests')) {
            return;
        }

        Schema::table('author_requests', function (Blueprint $table) {
            if (Schema::hasColumn('author_requests', 'accepted_user_agent')) {
                $table->dropColumn('accepted_user_agent');
            }
            if (Schema::hasColumn('author_requests', 'accepted_ip')) {
                $table->dropColumn('accepted_ip');
            }
            if (Schema::hasColumn('author_requests', 'accepted_at')) {
                $table->dropColumn('accepted_at');
            }
            if (Schema::hasColumn('author_requests', 'accepted_community_guidelines_version')) {
                $table->dropColumn('accepted_community_guidelines_version');
            }
            if (Schema::hasColumn('author_requests', 'accepted_ip_policy_version')) {
                $table->dropColumn('accepted_ip_policy_version');
            }
            if (Schema::hasColumn('author_requests', 'accepted_privacy_version')) {
                $table->dropColumn('accepted_privacy_version');
            }
            if (Schema::hasColumn('author_requests', 'accepted_terms_version')) {
                $table->dropColumn('accepted_terms_version');
            }
        });
    }
};
