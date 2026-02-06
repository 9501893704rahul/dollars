<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyStoreRequest;
use App\Models\Property;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;


class PropertyController extends Controller
{


    public function index(Request $request)
    {
        $authenticatedUser = $request->user();
        $searchTerm        = (string) $request->query('q', '');

        $propertyQuery = Property::query();

        if ($authenticatedUser->hasRole('admin')) {
            // Admin can see all properties, no extra constraints
        } elseif ($authenticatedUser->hasRole('owner')) {
            $propertyQuery->where('owner_id', $authenticatedUser->id);
        } elseif ($authenticatedUser->hasRole('housekeeper')) {
            $propertyQuery->whereIn('id', function ($subQuery) use ($authenticatedUser) {
                $subQuery->select('property_id')
                    ->from('cleaning_sessions')
                    ->where('housekeeper_id', $authenticatedUser->id);
            });
        }

        $properties = $propertyQuery
            ->when($searchTerm !== '', fn($query) => $query->where('name', 'like', "%{$searchTerm}%"))
            ->when($request->owner_id, fn($query) => $query->where('owner_id', $request->owner_id))
            ->with(['owner.roles', 'rooms'])
            ->when(
                $authenticatedUser?->hasAnyRole(['admin', 'owner']),
                fn($query) => $query->with('propertyTasks')
            )
            ->withCount('rooms')
            ->orderBy('name')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $owners = User::whereHas('roles', function ($query) {
            $query->where('name', 'owner');
        })->with('roles')->get();

        $rooms = Room::select('id', 'name', 'is_default')
            ->orderBy('name')
            ->get();

        return view('properties.index', compact('properties', 'owners', 'rooms'));
    }


    public function create(Request $request)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can create properties.');

        return view('properties.create', [
            'owners' => User::role('owner')->pluck('name', 'id')->all(),
            "rooms" => Room::where('is_default', true)->get()
        ]);
    }

    public function store(PropertyStoreRequest $request)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can create properties.');

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('properties', 'public');
        }

        $attach = $request->input('attach', 'none');

        DB::transaction(function () use ($data, $attach) {
            /** @var Property $property */
            $property = Property::create($data);

            if ($attach !== 'rooms') return;

            // -------- Attach DEFAULT ROOMS --------
            $defaultRooms = Room::query()
                ->where('is_default', true)
                ->orderBy('name')
                ->get(['id']);

            if ($defaultRooms->isNotEmpty()) {
                $nextOrder = (int) $property->rooms()->max('property_room.sort_order');
                $nextOrder = $nextOrder ? $nextOrder + 1 : 1;

                $payload = [];
                foreach ($defaultRooms as $r) {
                    if (! $property->rooms()->where('rooms.id', $r->id)->exists()) {
                        $payload[$r->id] = ['sort_order' => $nextOrder++];
                    }
                }
                if (!empty($payload)) {
                    $property->rooms()->attach($payload);
                }
            }
        });

        return redirect()
            ->route('properties.index')
            ->with('ok', match ($attach) {
                'rooms'        => 'Property created and default rooms assigned.',
                default        => 'Property created successfully.',
            });
    }

    public function edit(Request $request, Property $property)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can edit properties.');

        return view('properties.edit', [
            'property' => $property,
            'owners' => User::role('owner')->pluck('name', 'id')->all()
        ]);
    }

    public function update(Request $request, Property $property)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can update properties.');

        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $rules = [
            'name'         => ['required', 'string', 'max:255'],
            'address'      => ['nullable', 'string', 'max:255'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'geo_radius_m' => ['nullable', 'integer', 'min:50'],
            'photo'        => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['sometimes', 'boolean'],
            // iCal calendar integration URLs
            'ical_airbnb_url'  => ['nullable', 'url', 'max:500'],
            'ical_vrbo_url'    => ['nullable', 'url', 'max:500'],
            'ical_booking_url' => ['nullable', 'url', 'max:500'],
        ];

        if ($isAdmin) {
            // Admin can reassign owner
            $rules['owner_id'] = ['required', Rule::exists('users', 'id')];
        } else {
            // Non-admin cannot set/override owner_id
            // If you’re on Laravel 12, you can prohibit it like this:
            $rules['owner_id'] = ['prohibited'];
        }

        $data = $request->validate($rules);

        if (!$isAdmin) {
            // Guardrail: keep property with the same owner (should also be in a Policy)
            if ($property->owner_id !== $user->id) {
                abort(403, 'You cannot modify properties you do not own.');
            }
            unset($data['owner_id']); // ensure not mass-assigned
        }

        // Remove existing photo if requested
        if ($request->boolean('remove_photo') && $property->photo_path) {
            Storage::disk('public')->delete($property->photo_path);
            $data['photo_path'] = null;
        }

        // Replace with newly uploaded photo
        if ($request->hasFile('photo')) {
            if ($property->photo_path) {
                Storage::disk('public')->delete($property->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('properties', 'public');
        }

        $property->update($data);

        return redirect()
            ->route('properties.index')
            ->with('ok', 'Property updated successfully.');
    }

    public function destroy(Request $request, Property $property)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can delete properties.');

        $property->delete();

        return redirect()->route('properties.index')->with('ok', 'Property deleted.');
    }



    public function rooms(Property $property)
    {

        $rooms = $property->rooms()
            ->withCount('tasks')
            ->orderBy('property_room.sort_order')
            ->paginate(20);

        return view('properties.rooms.index', [
            'property'    => $property,
            'rooms'       => $rooms,
            'navProperty' => $property,
        ]);
    }



    public function updateRoom(Request $request, Property $property, Room $room)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can update rooms.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable', 'in:1'],
        ]);

        $newName = trim($data['name']);
        $isDefault = (bool) ($data['is_default'] ?? false);

        // Find an existing room by new name (case-insensitive) that's NOT the current one
        $existing = \App\Models\Room::whereRaw('LOWER(name) = ?', [mb_strtolower($newName)])
            ->where('id', '!=', $room->id)
            ->first();

        if ($existing) {
            // SWITCH attachment to the existing room (preserve pivot sort_order)
            $currentSort = (int) $property->rooms()->where('rooms.id', $room->id)->first()->pivot->sort_order ?? 0;

            // Detach old if attached
            $property->rooms()->detach($room->id);

            // Attach new if not attached
            if (! $property->rooms()->where('rooms.id', $existing->id)->exists()) {
                $property->rooms()->attach($existing->id, ['sort_order' => $currentSort ?: ($property->rooms()->max('property_room.sort_order') + 1)]);
            }

            // Optionally update default flag on target template
            $existing->is_default = $isDefault;
            $existing->save();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => "Switched to existing room: {$existing->name}",
                    'room' => $existing,
                ]);
            }

            return redirect()->route('properties.rooms.index', $property)
                ->with('status', "Switched to existing room: {$existing->name}");
        }

        // No existing match → update this room's data (global)
        $room->name = $newName;
        $room->is_default = $isDefault;
        $room->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => "Updated room: {$room->name}",
                'room' => $room,
            ]);
        }

        return redirect()->route('properties.rooms.index', $property)
            ->with('status', "Updated room: {$room->name}");
    }

    public function destroyRoom(Request $request, Property $property, Room $room)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can detach rooms.');

        // Detach room from property
        $property->rooms()->detach($room->id);

        return redirect()->route('properties.rooms.index', $property)
            ->with('status', 'Room detached from property.');
    }




    public function tasks(Property $property, Room $room)
    {
        abort_unless($property->rooms()->where('rooms.id', $room->id)->exists(), 403, 'Room does not belong to the specified property.');

        $tasks = $room->tasks()
            ->with(['media' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('room_task.sort_order')
            ->get();


        return view('properties.tasks.index', [
            'property'    => $property,
            'room'        => $room,
            'tasks'       => $tasks,
            'navProperty' => $property,
        ]);
    }


    public function storeTask(Request $request, Property $property, Room $room)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can add tasks.');

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:160'],
            'type'         => ['required', Rule::in(['room', 'inventory'])],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'visible_to_owner'       => ['nullable', 'boolean'],
            'visible_to_housekeeper' => ['nullable', 'boolean'],
            'media.*'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'], // 20MB
            'captions.*'   => ['nullable', 'string', 'max:255'],
        ]);

        // find-or-create Task by case-insensitive name
        $name = trim($validated['name']);
        $task = Task::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if (!$task) {
            $task = Task::create([
                'name'       => $name,
                'type'       => $validated['type'],
                'is_default' => false,
            ]);
        } else {
            // Optionally adopt chosen type if empty; otherwise keep existing
            $task->type = $task->type ?: $validated['type'];
            $task->save();
        }

        // attach to room with next sort + pivot fields
        if (!$room->tasks()->where('tasks.id', $task->id)->exists()) {
            $nextOrder = (int)$room->tasks()->max('room_task.sort_order') + 1;
            $room->tasks()->attach($task->id, [
                'sort_order' => $nextOrder,
                'instructions' => $validated['instructions'] ?? null,
                'visible_to_owner' => (bool)($validated['visible_to_owner'] ?? true),
                'visible_to_housekeeper' => (bool)($validated['visible_to_housekeeper'] ?? true),
            ]);
        }

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $i => $file) {
                if (!$file) continue;

                $path = $file->store('task-media', 'public'); // e.g. "task-media/abc123.jpg"
                $mime = $file->getMimeType();
                $type = str_starts_with($mime, 'video') ? 'video' : 'image';

                $task->media()->create([
                    'type'       => $type,
                    'url'        => $path,                         // <-- store path only
                    'thumbnail'  => $type === 'image' ? $path : null, // <-- path only
                    'caption'    => $request->input("captions.$i"),
                    'sort_order' => $i + 1,
                ]);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => "Task attached: {$task->name}",
                'task' => $task->load('media'),
            ]);
        }

        return redirect()->route('properties.tasks.index', [$property, $room])
            ->with('status', "Task attached: {$task->name}");
    }

    public function bulkStoreTask(Request $request, Property $property, Room $room)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can bulk add tasks.');

        $validated = $request->validate([
            'tasks' => ['required', 'string'], // JSON string
            'default_type' => ['required', Rule::in(['room', 'inventory'])],
        ]);

        $taskNames = json_decode($validated['tasks'], true);
        if (!is_array($taskNames) || empty($taskNames)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Invalid tasks data'], 422);
            }
            return redirect()->back()->withErrors(['tasks' => 'Invalid tasks data']);
        }

        $created = 0;
        $skipped = 0;
        $defaultType = $validated['default_type'];
        $nextOrder = (int)$room->tasks()->max('room_task.sort_order') + 1;

        DB::beginTransaction();
        try {
            foreach ($taskNames as $taskName) {
                $taskName = trim($taskName);
                if (empty($taskName)) {
                    $skipped++;
                    continue;
                }

                // Find or create task by case-insensitive name
                $task = Task::whereRaw('LOWER(name) = ?', [mb_strtolower($taskName)])->first();

                if (!$task) {
                    $task = Task::create([
                        'name' => $taskName,
                        'type' => $defaultType,
                        'is_default' => false,
                    ]);
                } else {
                    // Update type if not set
                    if (!$task->type) {
                        $task->type = $defaultType;
                        $task->save();
                    }
                }

                // Attach to room if not already attached
                if (!$room->tasks()->where('tasks.id', $task->id)->exists()) {
                    $room->tasks()->attach($task->id, [
                        'sort_order' => $nextOrder++,
                        'instructions' => null,
                        'visible_to_owner' => true,
                        'visible_to_housekeeper' => true,
                    ]);
                    $created++;
                } else {
                    $skipped++;
                }
            }

            DB::commit();

            $message = "Successfully created {$created} task(s)";
            if ($skipped > 0) {
                $message .= " ({$skipped} skipped - already exist)";
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $message,
                    'created' => $created,
                    'skipped' => $skipped,
                ]);
            }

            return redirect()->route('properties.tasks.index', [$property, $room])
                ->with('status', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Failed to create tasks: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Failed to create tasks. Please try again.']);
        }
    }

    public function updateTask(Request $request, Property $property, Room $room, Task $task)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can update tasks.');
        abort_unless($property->rooms()->where('rooms.id', $room->id)->exists(), 403, 'Room does not belong to the specified property.');
        abort_unless($room->tasks()->where('tasks.id', $task->id)->exists(), 404, 'Task not found in the specified room.');

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:160'],
            'type'         => ['required', Rule::in(['room', 'inventory'])],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'visible_to_owner'       => ['nullable', 'boolean'],
            'visible_to_housekeeper' => ['nullable', 'boolean'],
        ]);

        $newName = trim($data['name']);

        // switch to existing task if name matches another
        $existing = Task::whereRaw('LOWER(name) = ?', [mb_strtolower($newName)])
            ->where('id', '!=', $task->id)->first();

        $currentSort = (int) $room->tasks()->where('tasks.id', $task->id)->first()->pivot->sort_order ?? 0;


        if ($existing) {
            // detach old, attach existing (preserve order)
            $room->tasks()->detach($task->id);
            if (!$room->tasks()->where('tasks.id', $existing->id)->exists()) {
                $room->tasks()->attach($existing->id, [
                    'sort_order' => $currentSort ?: ($room->tasks()->max('room_task.sort_order') + 1),
                    'instructions' => $data['instructions'] ?? null,
                    'visible_to_owner' => (bool)($data['visible_to_owner'] ?? true),
                    'visible_to_housekeeper' => (bool)($data['visible_to_housekeeper'] ?? true),
                ]);
            } else {
                $room->tasks()->updateExistingPivot($existing->id, [
                    'instructions' => $data['instructions'] ?? null,
                    'visible_to_owner' => (bool)($data['visible_to_owner'] ?? true),
                    'visible_to_housekeeper' => (bool)($data['visible_to_housekeeper'] ?? true),
                ]);
            }
            // Update chosen type if empty; else keep existing's type
            $existing->type = $existing->type ?: $data['type'];
            $existing->save();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => "Switched to task: {$existing->name}",
                    'task' => $existing->load('media'),
                ]);
            }

            return redirect()->route('properties.tasks.index', [$property, $room])
                ->with('status', "Switched to task: {$existing->name}");
        }

        // update current task + pivot
        $task->update(['name' => $newName, 'type' => $data['type']]);
        $room->tasks()->updateExistingPivot($task->id, [
            'instructions' => $data['instructions'] ?? null,
            'visible_to_owner' => (bool)($data['visible_to_owner'] ?? true),
            'visible_to_housekeeper' => (bool)($data['visible_to_housekeeper'] ?? true),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => "Task updated: {$task->name}",
                'task' => $task->load('media'),
            ]);
        }

        return redirect()->route('properties.tasks.index', [$property, $room])
            ->with('status', "Task updated: {$task->name}");
    }



    public function detachTask(Request $request, Property $property, Room $room, Task $task)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can detach tasks.');

        $room->tasks()->detach($task->id);
        return redirect()->route('properties.tasks.index', [$property, $room])
            ->with('status', "Detached task: {$task->name}");
    }

    // Property-level tasks (not room-specific)
    public function propertyTasks(Request $request, Property $property)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can manage property tasks.');

        $property->load('propertyTasks');

        return view('properties.property-tasks.index', [
            'property' => $property,
            'navProperty' => $property,
        ]);
    }

    public function storePropertyTask(Request $request, Property $property)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can add property tasks.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phase' => ['required', Rule::in(['pre_cleaning', 'during_cleaning', 'post_cleaning'])],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'visible_to_owner' => ['nullable', 'boolean'],
            'visible_to_housekeeper' => ['nullable', 'boolean'],
        ]);

        // Find or create Task by case-insensitive name
        $name = trim($validated['name']);
        $task = Task::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if (!$task) {
            $task = Task::create([
                'name' => $name,
                'type' => 'room', // Default type, can be changed
                'phase' => $validated['phase'],
                'is_default' => false,
            ]);
        } else {
            // Update phase if not set
            if (!$task->phase) {
                $task->phase = $validated['phase'];
                $task->save();
            }
        }

        // Attach to property with next sort order
        if (!$property->propertyTasks()->where('tasks.id', $task->id)->exists()) {
            $nextOrder = (int) $property->propertyTasks()->max('property_tasks.sort_order') + 1;
            $property->propertyTasks()->attach($task->id, [
                'sort_order' => $nextOrder,
                'instructions' => $validated['instructions'] ?? null,
                'visible_to_owner' => (bool)($validated['visible_to_owner'] ?? true),
                'visible_to_housekeeper' => (bool)($validated['visible_to_housekeeper'] ?? true),
            ]);
        }

        $message = "Property task added: {$task->name}";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'task' => $task,
            ]);
        }

        return redirect()->route('properties.property-tasks.index', $property)
            ->with('status', $message);
    }

    public function updatePropertyTask(Request $request, Property $property, Task $task)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can update property tasks.');
        abort_unless($property->propertyTasks()->where('tasks.id', $task->id)->exists(), 404, 'Task not found for this property.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phase' => ['required', Rule::in(['pre_cleaning', 'during_cleaning', 'post_cleaning'])],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'visible_to_owner' => ['nullable', 'boolean'],
            'visible_to_housekeeper' => ['nullable', 'boolean'],
        ]);

        $newName = trim($validated['name']);

        // Update task name and phase if changed
        if ($task->name !== $newName) {
            $task->name = $newName;
        }
        if ($task->phase !== $validated['phase']) {
            $task->phase = $validated['phase'];
        }
        $task->save();

        // Update pivot data
        $property->propertyTasks()->updateExistingPivot($task->id, [
            'instructions' => $validated['instructions'] ?? null,
            'visible_to_owner' => (bool)($validated['visible_to_owner'] ?? true),
            'visible_to_housekeeper' => (bool)($validated['visible_to_housekeeper'] ?? true),
        ]);

        $message = "Property task updated: {$task->name}";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'task' => $task,
            ]);
        }

        return redirect()->route('properties.property-tasks.index', $property)
            ->with('status', $message);
    }

    public function detachPropertyTask(Request $request, Property $property, Task $task)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can detach property tasks.');

        $property->propertyTasks()->detach($task->id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'message' => "Detached property task: {$task->name}",
            ]);
        }

        return redirect()->route('properties.property-tasks.index', $property)
            ->with('status', "Detached property task: {$task->name}");
    }
}
