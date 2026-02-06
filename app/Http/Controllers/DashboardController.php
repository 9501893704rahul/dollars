<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\CleaningSession;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;

class DashboardController extends Controller
{
    /** Resolve acting role (admin wins unless ?as=owner and user also has owner). */
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
        if ($isHK) return 'housekeeper';
        return 'forbidden';
    }

    /** Base scoped sessions query by acting role. */
    private function baseSessions(string $acting, int $userId)
    {
        $q = CleaningSession::query()->with(['property:id,name,owner_id', 'housekeeper:id,name']);
        if ($acting === 'housekeeper') {
            $q->where('housekeeper_id', $userId);
        } elseif ($acting === 'owner') {
            $q->whereHas('property', fn($p) => $p->where('owner_id', $userId));
        }
        // admin: no scope
        return $q;
    }

    /** Visible property ids for counts/lists. */
    private function visiblePropertyIds(string $acting, int $userId)
    {
        if ($acting === 'admin') {
            // null => unscoped (use carefully)
            return null;
        }
        if ($acting === 'owner') {
            return Property::where('owner_id', $userId)->pluck('id');
        }
        // housekeeper: distinct properties from their sessions (last 180d + next 180d for practicality)
        $from = Carbon::now()->subDays(180)->toDateString();
        $to   = Carbon::now()->addDays(180)->toDateString();
        return CleaningSession::where('housekeeper_id', $userId)
            ->whereBetween('scheduled_date', [$from, $to])
            ->distinct()
            ->pluck('property_id');
    }

    public function __invoke(Request $request)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        // ----- KPIs -----
        $propIds = $this->visiblePropertyIds($acting, $u->id);

        // Properties count
        $propertiesCount = is_null($propIds)
            ? Property::count()
            : Property::whereIn('id', $propIds)->count();

        // Rooms count - count distinct rooms attached to visible properties
        $roomsCount = is_null($propIds)
            ? Room::count()
            : Room::whereHas('properties', fn($q) => $q->whereIn('properties.id', $propIds))->count();

        // Upcoming 7d (role-scoped)
        $today = Carbon::today()->toDateString();
        $in7   = Carbon::today()->addDays(7)->toDateString();
        $upcoming7Count = $this->baseSessions($acting, $u->id)
            ->whereBetween('scheduled_date', [$today, $in7])
            ->count();

        // Completed last 30d
        $last30 = Carbon::today()->subDays(30)->toDateString();
        $completed30Count = $this->baseSessions($acting, $u->id)
            ->where('status', 'completed')
            ->whereBetween('scheduled_date', [$last30, $today])
            ->count();

        $stats = [
            'properties'     => $propertiesCount,
            'rooms'          => $roomsCount,
            'upcoming_7d'    => $upcoming7Count,
            'completed_30d'  => $completed30Count,
        ];

        // ----- Lists -----
        // Properties mini list
        $propertiesMini = Property::query()
            ->when(!is_null($propIds), fn($q) => $q->whereIn('id', $propIds))
            ->withCount('rooms')
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name']);

        // Upcoming sessions (next 14 days)
        $in14 = Carbon::today()->addDays(14)->toDateString();
        $upcomingSessions = $this->baseSessions($acting, $u->id)
            ->whereBetween('scheduled_date', [$today, $in14])
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get(['id', 'property_id', 'housekeeper_id', 'scheduled_date', 'status']);

        // Recent completed (last 10)
        $recentSessions = $this->baseSessions($acting, $u->id)
            ->where('status', 'completed')
            ->orderByDesc('scheduled_date')
            ->limit(10)
            ->get(['id', 'property_id', 'housekeeper_id', 'scheduled_date', 'status']);

        // Housekeeper: today’s assignments
        $hkTodaySessions = collect();
        if ($u->hasRole('housekeeper')) {
            $hkTodaySessions = CleaningSession::with(['property:id,name'])
                ->where('housekeeper_id', $u->id)
                ->whereDate('scheduled_date', $today)
                ->orderBy('scheduled_date')
                ->get(['id', 'property_id', 'scheduled_date', 'status', 'housekeeper_id']);
        }

        return view('dashboard', [
            'stats'            => $stats,
            'propertiesMini'   => $propertiesMini,
            'upcomingSessions' => $upcomingSessions,
            'recentSessions'   => $recentSessions,
            'hkTodaySessions'  => $hkTodaySessions,
            // not used by the blade but handy for debugging/scope badges if needed
            'acting'           => $acting,
        ]);
    }
}
