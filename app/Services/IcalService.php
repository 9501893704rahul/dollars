<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Property;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class IcalService
{
    /**
     * Sync all iCal feeds for a property.
     */
    public function syncProperty(Property $property): array
    {
        $results = [
            'synced' => 0,
            'errors' => [],
        ];

        // Sync Airbnb calendar
        if ($property->ical_airbnb_url) {
            $result = $this->syncFeed($property, $property->ical_airbnb_url, 'airbnb');
            $results['synced'] += $result['synced'];
            if ($result['error']) {
                $results['errors']['airbnb'] = $result['error'];
            }
        }

        // Sync VRBO calendar
        if ($property->ical_vrbo_url) {
            $result = $this->syncFeed($property, $property->ical_vrbo_url, 'vrbo');
            $results['synced'] += $result['synced'];
            if ($result['error']) {
                $results['errors']['vrbo'] = $result['error'];
            }
        }

        // Sync Booking.com calendar
        if ($property->ical_booking_url) {
            $result = $this->syncFeed($property, $property->ical_booking_url, 'booking');
            $results['synced'] += $result['synced'];
            if ($result['error']) {
                $results['errors']['booking'] = $result['error'];
            }
        }

        // Update last synced timestamp
        $property->update(['ical_last_synced_at' => now()]);

        return $results;
    }

    /**
     * Sync a single iCal feed.
     */
    protected function syncFeed(Property $property, string $url, string $source): array
    {
        try {
            // Fetch the iCal feed
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return ['synced' => 0, 'error' => "HTTP {$response->status()}"];
            }

            $icalContent = $response->body();
            $events = $this->parseIcal($icalContent);

            $synced = 0;
            foreach ($events as $event) {
                if (!isset($event['uid'], $event['dtstart'], $event['dtend'])) {
                    continue;
                }

                CalendarEvent::updateOrCreate(
                    [
                        'property_id' => $property->id,
                        'uid' => $event['uid'],
                        'source' => $source,
                    ],
                    [
                        'summary' => $event['summary'] ?? null,
                        'description' => $event['description'] ?? null,
                        'start_date' => $event['dtstart'],
                        'end_date' => $event['dtend'],
                    ]
                );
                $synced++;
            }

            return ['synced' => $synced, 'error' => null];
        } catch (Throwable $e) {
            Log::error("iCal sync failed for property {$property->id} ({$source}): {$e->getMessage()}");
            return ['synced' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse iCal content into array of events.
     * Simple parser - handles basic iCal format from Airbnb, VRBO, Booking.com
     */
    protected function parseIcal(string $content): array
    {
        $events = [];
        $currentEvent = null;
        $currentKey = null;
        $currentValue = '';

        // Normalize line endings and unfold continued lines
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/\n\s/', '', $content);

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check for event start/end
            if ($line === 'BEGIN:VEVENT') {
                $currentEvent = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                if ($currentEvent) {
                    $events[] = $currentEvent;
                }
                $currentEvent = null;
                continue;
            }

            // Parse property within event
            if ($currentEvent !== null && strpos($line, ':') !== false) {
                // Handle properties with parameters (e.g., DTSTART;VALUE=DATE:20240115)
                $colonPos = strpos($line, ':');
                $keyPart = substr($line, 0, $colonPos);
                $value = substr($line, $colonPos + 1);

                // Extract just the key name (remove parameters)
                $semicolonPos = strpos($keyPart, ';');
                $key = $semicolonPos !== false ? substr($keyPart, 0, $semicolonPos) : $keyPart;
                $key = strtolower($key);

                // Parse date values
                if (in_array($key, ['dtstart', 'dtend'])) {
                    $value = $this->parseDate($value);
                }

                // Decode escaped characters
                if (in_array($key, ['summary', 'description'])) {
                    $value = $this->decodeIcalString($value);
                }

                $currentEvent[$key] = $value;
            }
        }

        return $events;
    }

    /**
     * Parse iCal date value to Y-m-d format.
     */
    protected function parseDate(string $value): ?string
    {
        // Handle both DATE and DATETIME formats
        // DATE: 20240115
        // DATETIME: 20240115T100000Z
        $value = trim($value);

        // Remove time zone suffix
        $value = preg_replace('/T\d{6}Z?$/', '', $value);

        if (strlen($value) >= 8) {
            return substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2);
        }

        return null;
    }

    /**
     * Decode iCal escaped string.
     */
    protected function decodeIcalString(string $value): string
    {
        // Decode common escape sequences
        $value = str_replace(['\\n', '\\N'], "\n", $value);
        $value = str_replace(['\\,', '\\;', '\\\\'], [',', ';', '\\'], $value);
        return $value;
    }

    /**
     * Get properties with upcoming checkouts that haven't been alerted.
     */
    public function getPropertiesWithUpcomingCheckouts(int $days = 2): array
    {
        return CalendarEvent::with('property')
            ->upcomingCheckouts($days)
            ->where('checkout_alert_sent', false)
            ->get()
            ->groupBy('property_id')
            ->map(function ($events) {
                $property = $events->first()->property;
                return [
                    'property' => $property,
                    'events' => $events,
                ];
            })
            ->values()
            ->toArray();
    }
}
