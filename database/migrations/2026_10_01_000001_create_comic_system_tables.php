<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Genres table
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Comics table
        Schema::create('comics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('other_names')->nullable();
            $table->string('author')->nullable();
            $table->string('artist')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_image')->default('/images/default.png');
            $table->string('banner_image')->nullable();
            $table->enum('status', ['ongoing', 'completed', 'dropped'])->default('ongoing');
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('monthly_views')->default(0);
            $table->unsignedBigInteger('weekly_views')->default(0);
            $table->unsignedBigInteger('daily_views')->default(0);
            $table->decimal('rating_score', 3, 2)->default(5.00);
            $table->unsignedInteger('rating_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->timestamps();
        });

        // 3. Comic - Genre pivot
        Schema::create('comic_genre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->constrained('comics')->onDelete('cascade');
            $table->foreignId('genre_id')->constrained('genres')->onDelete('cascade');
            $table->unique(['comic_id', 'genre_id']);
        });

        // 4. Chapters table
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->constrained('comics')->onDelete('cascade');
            $table->decimal('chapter_number', 8, 2);
            $table->string('title')->nullable();
            $table->string('slug');
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();

            $table->unique(['comic_id', 'chapter_number']);
        });

        // 5. Chapter Pages table
        Schema::create('chapter_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
            $table->unsignedInteger('page_number');
            $table->string('image_url', 1000);
            $table->string('cloudinary_public_id')->nullable();
            $table->timestamps();

            $table->index(['chapter_id', 'page_number']);
        });

        // 6. Bookmarks / Favorites table
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('comic_id')->constrained('comics')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'comic_id']);
        });

        // 7. Reading Histories table
        Schema::create('reading_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('comic_id')->constrained('comics')->onDelete('cascade');
            $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
            $table->timestamp('last_read_at');
            $table->timestamps();

            $table->unique(['user_id', 'comic_id']);
        });

        // 8. Comments table
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('comic_id')->constrained('comics')->onDelete('cascade');
            $table->foreignId('chapter_id')->nullable()->constrained('chapters')->onDelete('cascade');
            $table->text('content');
            $table->unsignedInteger('likes')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });

        // 9. Ratings table
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('comic_id')->constrained('comics')->onDelete('cascade');
            $table->unsignedTinyInteger('score'); // 1-5
            $table->timestamps();

            $table->unique(['user_id', 'comic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('reading_histories');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('chapter_pages');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('comic_genre');
        Schema::dropIfExists('comics');
        Schema::dropIfExists('genres');
    }
};
