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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('cover_image')->nullable();
            // Content format (e.g., Image, Video, PDF, Text, ...etc.)
            $table->enum('content_type', ['pdf', 'image', 'text', 'video'])->nullable();

            // Path to the uploaded media file
            $table->string('file_path')->nullable();


            // Approval status of the content
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->text('body')->nullable();

            // Reference to the user who uploaded the content (Specialist/Recovered Child/Child)
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');

            // The ID of the admin who approved or rejected the request (for accountability)
            $table->foreignId('decided_by')->nullable()->constrained('users', 'id')->onDelete('set null');

            // Total number of likes to display on dashboard cards and charts without heavy queries
            $table->integer('likes_count')->default(0);

            // Total number of comments to track engagement levels across the platform
            $table->integer('comments_count')->default(0);


            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');

            // Timestamp for when the admin approved the content
            $table->timestamp('approved_at')->nullable();

            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
