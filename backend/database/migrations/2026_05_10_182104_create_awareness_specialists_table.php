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
        Schema::create('awareness_specialists', function (Blueprint $table) {
            // ربط المستخدم بالجدول
            $table->foreignId('user_id')->primary()->constrained('users')->onDelete('cascade');

            // حقول اختيارية (nullable)
            $table->string('specialty')->nullable();
            $table->text('bio')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awareness_specialists');
    }
};
