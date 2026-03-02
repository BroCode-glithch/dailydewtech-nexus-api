<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_slug_redirects')) {
            return;
        }

        Schema::create('post_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('old_slug', 191)->unique();
            $table->string('new_slug', 191);
            $table->timestamps();

            $table->index('new_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_slug_redirects');
    }
};
