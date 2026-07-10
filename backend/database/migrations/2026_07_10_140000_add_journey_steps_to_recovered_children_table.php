<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recovered_children', function (Blueprint $table) {
            $table->json('journey_steps')->nullable()->after('recovery_story');
        });
    }

    public function down(): void
    {
        Schema::table('recovered_children', function (Blueprint $table) {
            $table->dropColumn('journey_steps');
        });
    }
};
