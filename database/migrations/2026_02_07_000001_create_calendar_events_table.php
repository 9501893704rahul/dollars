<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores calendar events synced from Airbnb, VRBO, Booking.com iCal feeds.
     */
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('uid', 255); // Unique ID from iCal feed
            $table->string('source', 50); // 'airbnb', 'vrbo', 'booking'
            $table->string('summary', 255)->nullable();
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('checkout_alert_sent')->default(false);
            $table->timestamps();

            // Unique constraint to prevent duplicate events
            $table->unique(['property_id', 'uid', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
