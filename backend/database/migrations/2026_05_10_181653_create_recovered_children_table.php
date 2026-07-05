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
        Schema::create('recovered_children', function (Blueprint $table) {

            $table->foreignId('user_id')->primary()->constrained('users')->onDelete('cascade');
            $table->integer('age')->nullable();
            $table->string('cancer_type', 100)->nullable();
            $table->date('recovery_date')->nullable();
            $table->string('recovery_duration', 100)->nullable();
            $table->string('location', 150)->nullable();
            $table->text('recovery_story')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovered_children');
    }
};
