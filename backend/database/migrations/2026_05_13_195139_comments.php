<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // The user who wrote the comment
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // The actual comment text
            $table->text('comment_text');

            /**
             * Polymorphic Fields:
             * commentable_id: Stores the ID of (Content, Campaign, or Session)
             * commentable_type: Stores the Model name (e.g., App\Models\Content)
             */
            $table->morphs('commentable'); // This will create the commentable_id and commentable_type fields

            // Crucial for monthly engagement charts in the dashboard
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
