<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['uploaded_by', 'created_at']);
        });

        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 500)->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('cover_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('language', 10)->default('en');
            $table->enum('status', ['draft', 'pending', 'published', 'archived'])->default('draft');
            $table->enum('visibility', ['public', 'unlisted', 'private'])->default('public');
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['author_id', 'status']);
            $table->index(['is_featured', 'published_at']);
        });

        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->unsignedInteger('chapter_number');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->longText('content_html');
            $table->text('excerpt')->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('read_time_minutes')->default(1);
            $table->enum('status', ['draft', 'pending', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['story_id', 'chapter_number']);
            $table->index(['story_id', 'status', 'published_at']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('story_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['story_id', 'category_id']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->timestamps();
        });

        Schema::create('story_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['story_id', 'tag_id']);
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('likeable');
            $table->timestamps();

            $table->unique(['user_id', 'likeable_type', 'likeable_id']);
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'story_id']);
        });

        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained('chapters')->nullOnDelete();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'story_id']);
            $table->index(['user_id', 'last_read_at']);
        });

        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained('chapters')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();

            $table->index(['story_id', 'viewed_at']);
            $table->index(['chapter_id', 'viewed_at']);
            $table->index(['user_id', 'viewed_at']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->enum('status', ['visible', 'hidden', 'pending'])->default('visible');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->morphs('target');
            $table->string('reason', 150);
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'reviewing', 'resolved', 'rejected'])->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120);
            $table->morphs('target');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('story_views');
        Schema::dropIfExists('reading_progress');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('story_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('story_category');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('stories');
        Schema::dropIfExists('media');
    }
};
