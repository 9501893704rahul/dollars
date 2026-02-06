<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\CleaningSession;
use App\Models\Property;
use App\Models\User;

class ManageSessionController extends Controller
{
    /**
     * Resolve acting role for the current request.
     * admin wins by default; admin+owner may opt into owner scope via ?as=owner.
     */
    private function actingRole(Request $request): string
    {
        $u = $request->user();
        $isAdmin = $u?->hasRole('admin') ?? false;
        $isOwner = $u?->hasRole('owner') ?? false;

        if ($isAdmin) {
            // Allow explicit owner view if they also have owner role
            if ($isOwner && $request->query('as') === 'owner') {
                return 'owner';
            }
            return 'admin';
        }
        if ($isOwner) return 'owner';

        return 'forbidden';
    }

    public function index(Request $request)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        $filters = [
            'property_id'    => $request->integer('property_id') ?: null,
            'housekeeper_id' => $request->integer('housekeeper_id') ?: null,
            'status'         => ($request->filled('status') ? (string)$request->string('status') : null),
            'date_from'      => $request->input('date_from') ?: null,
            'date_to'        => $request->input('date_to') ?: null,
        ];

        $q = CleaningSession::query()
            ->with([
                'property:id,name,owner_id',
                'housekeeper:id,name',
            ])
            // admin: full system, owner: only their properties
            ->when(
                $acting === 'owner',
                fn($qry) => $qry->where('owner_id', $u->id)
            )
            ->when($filters['property_id'], fn($qry, $v) => $qry->where('property_id', $v))
            ->when($filters['housekeeper_id'], fn($qry, $v) => $qry->where('housekeeper_id', $v))
            ->when($filters['status'], fn($qry, $v) => $qry->where('status', $v))
            ->when($filters['date_from'], fn($qry, $v) => $qry->whereDate('scheduled_date', '>=', $v))
            ->when($filters['date_to'], fn($qry, $v) => $qry->whereDate('scheduled_date', '<=', $v))
            ->orderByDesc('scheduled_date');

        $sessions = $q->paginate(20)->withQueryString();

        $properties = Property::query()
            ->when($acting === 'owner', fn($qry) => $qry->where('owner_id', $u->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        // For owners: list all housekeepers (or scope to those who have sessions with this owner if you prefer)
        $housekeepers = User::role('housekeeper')->orderBy('name')->get(['id', 'name']);

        return view('sessions.manage.index', compact('sessions', 'properties', 'housekeepers', 'filters', 'acting'));
    }

    public function create(Request $request)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        $properties = Property::query()
            ->when($acting === 'owner', fn($qry) => $qry->where('owner_id', $u->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $housekeepers = User::role('housekeeper')->orderBy('name')->get(['id', 'name']);

        return view('sessions.manage.create', compact('properties', 'housekeepers', 'acting'));
    }

    public function store(Request $request)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        $data = $request->validate([
            'property_id'    => ['required', 'integer', 'exists:properties,id'],
            'housekeeper_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'status'         => ['nullable', Rule::in(['pending', 'in_progress', 'completed'])],
        ]);


        $property = Property::with(['rooms.tasks'])->find($data['property_id']);
        $taskCount = $property?->rooms->flatMap->tasks->count() ?? 0;
        if ($taskCount < 1) {
            return back()
                ->withErrors(['property_id' => 'The selected property has no rooms with tasks. Please define rooms and tasks before scheduling a session.'])
                ->withInput();
        }


        // owner may create only for own properties
        if ($acting === 'owner') {
            abort_unless(
                Property::whereKey($data['property_id'])->where('owner_id', $u->id)->exists(),
                403,
                'You can schedule only for your properties.'
            );
        }

        // assignee must be a housekeeper
        abort_unless(
            User::role('housekeeper')->whereKey($data['housekeeper_id'])->exists(),
            422,
            'Assignee must have the housekeeper role.'
        );

        // prevent duplicates (also enforced by unique index)
        $dup = CleaningSession::query()
            ->where('property_id', $data['property_id'])
            ->where('housekeeper_id', $data['housekeeper_id'])
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->exists();
        if ($dup) {
            return back()
                ->withErrors(['scheduled_date' => 'Duplicate assignment for this housekeeper/property/date.'])
                ->withInput();
        }

        // owner_id: if admin is acting as admin -> use property's real owner_id; else use self (owner)
        $ownerId = ($acting === 'admin')
            ? Property::find($data['property_id'])->owner_id
            : $u->id;

        CleaningSession::create([
            'property_id'    => $data['property_id'],
            'owner_id'       => $ownerId,
            'housekeeper_id' => $data['housekeeper_id'],
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'status'         => $data['status'] ?? 'pending',
        ]);

        return redirect()->route('manage.sessions.index')->with('ok', 'Assignment created.');
    }

    public function edit(Request $request, CleaningSession $session)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        if ($acting === 'owner') {
            abort_unless($session->property->owner_id === $u->id, 403);
        }

        $properties = Property::query()
            ->when($acting === 'owner', fn($qry) => $qry->where('owner_id', $u->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $housekeepers = User::role('housekeeper')->orderBy('name')->get(['id', 'name']);

        return view('sessions.manage.edit', compact('session', 'properties', 'housekeepers', 'acting'));
    }

    public function update(Request $request, CleaningSession $session)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        if ($acting === 'owner') {
            abort_unless($session->property->owner_id === $u->id, 403);
        }

        $data = $request->validate([
            'property_id'    => ['required', 'integer', 'exists:properties,id'],
            'housekeeper_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'status'         => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
        ]);

        if ($acting === 'owner') {
            abort_unless(Property::whereKey($data['property_id'])->where('owner_id', $u->id)->exists(), 403);
        }
        abort_unless(User::role('housekeeper')->whereKey($data['housekeeper_id'])->exists(), 422);

        $dup = CleaningSession::query()
            ->where('property_id', $data['property_id'])
            ->where('housekeeper_id', $data['housekeeper_id'])
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->where('id', '<>', $session->id)
            ->exists();
        if ($dup) {
            return back()
                ->withErrors(['scheduled_date' => 'Duplicate assignment for this housekeeper/property/date.'])
                ->withInput();
        }

        // When admin updates, keep owner_id consistent with selected property’s owner
        if ($acting === 'admin') {
            $data['owner_id'] = Property::find($data['property_id'])->owner_id;
        }

        $session->update($data);

        return redirect()->route('manage.sessions.index')->with('ok', 'Assignment updated.');
    }

    public function destroy(Request $request, CleaningSession $session)
    {
        $u = Auth::user();
        $acting = $this->actingRole($request);
        abort_if($acting === 'forbidden', 403);

        if ($acting === 'owner') {
            abort_unless($session->property->owner_id === $u->id, 403);
        }

        $session->delete();

        return redirect()->route('manage.sessions.index')->with('ok', 'Assignment deleted.');
    }
}
