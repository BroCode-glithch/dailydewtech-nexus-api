<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->nullable()->index();
                $table->string('thumbnail')->nullable();
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->json('technologies')->nullable();
                $table->string('link')->nullable();
                $table->enum('status', ['draft', 'published'])->default('published');
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
