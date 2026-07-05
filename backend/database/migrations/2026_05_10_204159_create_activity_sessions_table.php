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
        Schema::create('activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('cover_image')->nullable();
            $table->string('title');
            $table->text('description');
            $table->dateTime('date_time');

            $table->enum('type', ['session', 'activity']);

            $table->enum('session_category', [
                'nutrition',
                'awareness',
                'psychological',
                'motivational'
            ]);
            $table->enum('target_audience', ['parents', 'child', 'parents and child']);

            $table->string('join_link')->nullable();
            $table->string('form_link')->nullable();
            $table->string('participation_method')->nullable(false);
            $table->integer('duration')->default(60);
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'ongoing',
                'completed'
            ])->default('pending');


            // The ID of the admin who approved or rejected the request
            $table->foreignId('decided_by')->nullable()->constrained('users', 'id')->onDelete('set null');

            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');

            // Total number of likes for monthly engagement charts
            $table->integer('likes_count')->default(0);

            // Total number of comments for monthly engagement charts
            $table->integer('comments_count')->default(0);

            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_sessions');
    }
};
