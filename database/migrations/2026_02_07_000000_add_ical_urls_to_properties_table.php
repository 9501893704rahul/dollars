<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds iCal calendar sync URLs for Airbnb, VRBO, and Booking.com integration.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('ical_airbnb_url', 500)->nullable()->after('geo_radius_m');
            $table->string('ical_vrbo_url', 500)->nullable()->after('ical_airbnb_url');
            $table->string('ical_booking_url', 500)->nullable()->after('ical_vrbo_url');
            $table->timestamp('ical_last_synced_at')->nullable()->after('ical_booking_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'ical_airbnb_url',
                'ical_vrbo_url',
                'ical_booking_url',
                'ical_last_synced_at',
            ]);
        });
    }
};
