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
        Schema::create('donation_campaigns', function (Blueprint $table) {
            $table->id();

            // Foreign key linking the campaign to the user (individual donor or organization)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('cover_image')->nullable();
            $table->string('title');
            $table->text('description');
            $table->date('start_date');
            $table->date('end_date');

            /**
             * Status Logic:
             * Since users create the campaigns, the default status is 'pending'
             * until the Admin reviews and verifies the reliability of the donor/organization.
             */
            $table->enum('status', ['pending', 'active', 'completed', 'rejected'])->default('pending');

            // Button and link customization 
            $table->enum('type', ['collect_donations', 'registration'])->default('collect_donations');

            $table->string('button_text')->default('Donate Now');
            $table->string('action_link')->nullable(); // External link (e.g., Google Form)
            $table->text('contact_info')->nullable(false); // Direct contact details for the campaign owner

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
        Schema::dropIfExists('donation_campaigns');
    }
};
