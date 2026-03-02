<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('creative_import_logs')) {
            return;
        }

        Schema::create('creative_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('story_id')->nullable()->constrained('stories')->nullOnDelete();
            $table->string('source_type', 20)->default('docx');
            $table->string('original_filename', 255);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('completed');
            $table->json('warnings_json')->nullable();
            $table->json('errors_json')->nullable();
            $table->string('import_reference', 100)->unique();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creative_import_logs');
    }
};
