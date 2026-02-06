<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use App\Models\CleaningSession;


class CalendarController extends Controller
{
    /**
     * Determine acting role per request.
     * If user has admin, admin wins unless ?as=owner given and user also has owner.
     */
    private function actingRole(Request $request): string
    {
        $u = $request->user();
        $isAdmin = $u?->hasRole('admin') ?? false;
        $isOwner = $u?->hasRole('owner') ?? false;
        $isHK    = $u?->hasRole('housekeeper') ?? false;

        if ($isAdmin) {
            if ($isOwner && $request->query('as') === 'owner') return 'owner';
            return 'admin';
        }
        if ($isOwner) return 'owner';
        if ($isHK)    return 'housekeeper';

        return 'forbidden';
    }

    public function index(Request $request)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        // Month selection (YYYY-MM), defaults to current month
        $monthParam = (string) $request->query('month', now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m-d', $monthParam . '-01')->startOfMonth();
        $monthEnd   = (clone $monthStart)->endOfMonth();

        // Grid boundaries (full weeks)
        $gridStart = (clone $monthStart)->startOfWeek(); // Mon/Sun per app locale; adjust if needed
        $gridEnd   = (clone $monthEnd)->endOfWeek();

        // Day selected (for sidebar/list)
        $selectedDay = $request->query('day');
        $selectedDay = $selectedDay ? Carbon::parse($selectedDay)->toDateString() : null;

        // Base query scoped by acting role
        $q = CleaningSession::query()
            ->with(['property:id,name,owner_id', 'housekeeper:id,name'])
            ->whereBetween('scheduled_date', [$gridStart->toDateString(), $gridEnd->toDateString()]);

        if ($acting === 'housekeeper') {
            $q->where('housekeeper_id', $u->id);
        } elseif ($acting === 'owner') {
            $q->whereHas('property', fn($p) => $p->where('owner_id', $u->id));
        } // admin -> no scope

        $sessions = $q->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // Group sessions by date for calendar dots/counts
        $byDate = $sessions->groupBy(fn($s) => Carbon::parse($s->scheduled_date)->toDateString());

        // Optional: a list for the selected day, sorted by time (earliest to latest)
        $daySessions = $selectedDay ? ($byDate[$selectedDay] ?? collect()) : collect();
        
        // Sort day sessions by scheduled_time (earliest to latest)
        if ($daySessions->isNotEmpty()) {
            $daySessions = $daySessions->sortBy(function($session) {
                return $session->scheduled_time ? $session->scheduled_time->format('H:i:s') : '23:59:59';
            })->values();
        }

        // Build day cells
        $days = [];
        foreach (CarbonPeriod::create($gridStart, '1 day', $gridEnd) as $date) {
            $d = $date->toDateString();
            $days[] = [
                'date'          => $d,
                'isToday'       => $d === now()->toDateString(),
                'inMonth'       => $date->betweenIncluded($monthStart, $monthEnd),
                'count'         => ($byDate[$d] ?? collect())->count(),
            ];
        }

        // Prev/next month params
        $prevMonth = (clone $monthStart)->subMonth()->format('Y-m');
        $nextMonth = (clone $monthStart)->addMonth()->format('Y-m');

        return view('calendar.index', [
            'acting'      => $acting,
            'monthStart'  => $monthStart,
            'prevMonth'   => $prevMonth,
            'nextMonth'   => $nextMonth,
            'days'        => $days,
            'selectedDay' => $selectedDay,
            'daySessions' => $daySessions,
        ]);
    }

    /**
     * Export sessions as iCal format.
     */
    public function ical(Request $request)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        // Get sessions for the next 90 days
        $startDate = now()->toDateString();
        $endDate = now()->addDays(90)->toDateString();

        $q = CleaningSession::query()
            ->with(['property:id,name,address,owner_id', 'housekeeper:id,name'])
            ->whereBetween('scheduled_date', [$startDate, $endDate]);

        if ($acting === 'housekeeper') {
            $q->where('housekeeper_id', $u->id);
        } elseif ($acting === 'owner') {
            $q->whereHas('property', fn($p) => $p->where('owner_id', $u->id));
        }

        $sessions = $q->orderBy('scheduled_date')->orderBy('scheduled_time')->get();

        // Build iCal content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//HK Checklist//Cleaning Sessions//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:Cleaning Sessions\r\n";

        foreach ($sessions as $session) {
            $uid = 'session-' . $session->id . '@' . parse_url(config('app.url'), PHP_URL_HOST);
            $dtstart = $session->scheduled_date->format('Ymd');
            $dtend = $session->scheduled_date->addDay()->format('Ymd');

            // If time is set, use datetime format
            if ($session->scheduled_time) {
                $dtstart = $session->scheduled_date->format('Ymd') . 'T' . 
                           $session->scheduled_time->format('His');
                $dtend = $session->scheduled_date->format('Ymd') . 'T' . 
                         $session->scheduled_time->addHours(2)->format('His');
            }

            $summary = $this->escapeIcal($session->property->name . ' - Cleaning');
            $description = $this->escapeIcal(
                "Property: " . $session->property->name . "\n" .
                "Address: " . ($session->property->address ?? 'N/A') . "\n" .
                "Housekeeper: " . ($session->housekeeper->name ?? 'N/A') . "\n" .
                "Status: " . ucfirst($session->status)
            );
            $location = $this->escapeIcal($session->property->address ?? '');

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:" . $uid . "\r\n";
            if ($session->scheduled_time) {
                $ical .= "DTSTART:" . $dtstart . "\r\n";
                $ical .= "DTEND:" . $dtend . "\r\n";
            } else {
                $ical .= "DTSTART;VALUE=DATE:" . $session->scheduled_date->format('Ymd') . "\r\n";
                $ical .= "DTEND;VALUE=DATE:" . $session->scheduled_date->addDay()->format('Ymd') . "\r\n";
            }
            $ical .= "SUMMARY:" . $summary . "\r\n";
            $ical .= "DESCRIPTION:" . $description . "\r\n";
            if ($location) {
                $ical .= "LOCATION:" . $location . "\r\n";
            }
            $ical .= "STATUS:" . ($session->status === 'completed' ? 'COMPLETED' : 'CONFIRMED') . "\r\n";
            $ical .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return response($ical)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="cleaning-sessions.ics"');
    }

    /**
     * Escape special characters for iCal format.
     */
    private function escapeIcal(string $text): string
    {
        $text = str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $text);
        $text = str_replace(["\r\n", "\r", "\n"], '\\n', $text);
        return $text;
    }
}
