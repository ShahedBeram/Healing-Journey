<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();

            // The user who liked the content
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('child_id')->nullable()->constrained('child_profiles')->onDelete('cascade');

            // Polymorphic Fields
            $table->morphs('likeable'); // This will create the likeable_id and likeable_type fields

            // To track when the like happened for dashboard analytics
            $table->timestamps();

            // Prevent a user from liking the same item twice
            $table->unique(['user_id', 'likeable_id', 'likeable_type', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
