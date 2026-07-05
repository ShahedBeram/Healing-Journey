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
        Schema::create('awareness_motivational_content', function (Blueprint $table) {

            $table->foreignId('content_id')->primary()->constrained('contents')->onDelete('cascade');

            $table->enum('content_category_type', ['awareness', 'motivational'])->default('awareness');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awareness_motivational_content');
    }
};
