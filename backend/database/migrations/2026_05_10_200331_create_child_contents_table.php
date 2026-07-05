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
        Schema::create('child_contents', function (Blueprint $table) {

            $table->foreignId('content_id')->primary()->constrained('contents')->onDelete('cascade');

            $table->foreignId('child_profile_id')->constrained('child_profiles')->onDelete('cascade');
            $table->enum('content_category_type', ['story', 'drawing', 'other'])
                ->default('other');

            // Points awarded by the Admin after reviewing the parent's request
            $table->integer('points_awarded')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_contents');
    }
};
