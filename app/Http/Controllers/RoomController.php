<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{

    public function index(Request $request)
    {

        $rooms = Room::withCount('tasks')
            ->withCount('properties')
            ->with(['properties' => function ($query) {
                $query->select('properties.id', 'properties.name', 'properties.address');
            }])
            ->when($request->search ?? false, function ($query, $search) {
                $query->where('name', 'like', "%$search%");
            })
            ->latest()
            ->paginate(20);

        $tasks = Task::orderBy('type')->orderBy('name')->get([
            'id',
            'name',
            'type',
            'is_default',
        ]);

        return view('rooms.index', [
            'rooms'       => $rooms,
            'tasks'       => $tasks
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can create rooms.');

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'assign_defaults'   => ['sometimes', 'boolean']
        ]);

        $room = Room::create([
            'name'       => $data['name'],
            'is_default' => (bool)($data['is_default'] ?? false),
        ]);

        // Return JSON for AJAX requests, otherwise redirect
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Room added.',
                'room' => $room,
            ]);
        }

        return redirect()->route('rooms.index')->with('ok', 'Room added.');
    }

    public function edit(Request $request, Room $room)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can edit rooms.');

        // Load current tasks for this room with their sort_order from pivot
        $room->load(['tasks' => function ($query) {
            $query->orderBy('room_task.sort_order');
        }]);

        // Format room tasks with pivot data for the frontend
        $roomTasks = $room->tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'name' => $task->name,
                'type' => $task->type,
                'is_default' => $task->is_default,
                'sort_order' => $task->pivot->sort_order ?? 0,
            ];
        })->values();

        return view('rooms.edit', [
            'room'  => $room,
            'roomTasks' => $roomTasks,
        ]);
    }

    public function update(Request $request, Room $room)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can update rooms.');

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $room->update([
            'name'       => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return redirect()
            ->route('rooms.index')
            ->with('ok', 'Room updated.');
    }


    public function destroy(Request $request, Room $room)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can delete rooms.');

        $room->delete();

        return redirect()->route('rooms.index')->with('ok', 'Room deleted.');
    }


    public function bulkAttachTasks(Request $request)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can bulk attach tasks.');

        $validated = $request->validate([
            'room_ids'   => ['required', 'array', 'min:1'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
            'task_ids'   => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:tasks,id'],
        ]);

        $rooms = Room::whereIn('id', $validated['room_ids'])->get();

        foreach ($rooms as $room) {
            // assumes many-to-many relationship: Room::tasks()
            $room->tasks()->syncWithoutDetaching($validated['task_ids']);
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()
            ->route('rooms.index')
            ->with('ok', 'Tasks assigned to selected rooms.');
    }

    /**
     * GET /rooms/{room}/tasks
     * Show tasks for a specific room with ability to edit, add, and reorder.
     */
    public function tasks(Room $room)
    {
        $tasks = $room->tasks()
            ->withPivot(['sort_order', 'instructions', 'visible_to_owner', 'visible_to_housekeeper'])
            ->with('media')
            ->orderBy('room_task.sort_order')
            ->get();

        return view('rooms.tasks.index', [
            'room' => $room,
            'tasks' => $tasks,
        ]);
    }

    /**
     * POST /rooms/{room}/tasks
     */
    public function storeTask(Request $request, Room $room)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can add tasks to rooms.');

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

                $path = $file->store('task-media', 'public');
                $mime = $file->getMimeType();
                $type = str_starts_with($mime, 'video') ? 'video' : 'image';

                $task->media()->create([
                    'type'       => $type,
                    'url'        => $path,
                    'thumbnail'  => $type === 'image' ? $path : null,
                    'caption'    => $request->input("captions.$i"),
                    'sort_order' => $i + 1,
                ]);
            }
        }

        // Return JSON for AJAX requests, otherwise redirect
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => "Task attached: {$task->name}",
                'task' => $task->load('media'),
            ]);
        }

        return redirect()->route('rooms.tasks.index', $room)
            ->with('status', "Task attached: {$task->name}");
    }

    /**
     * GET /rooms/{room}/tasks/{task}/edit
     */
    public function editTask(Request $request, Room $room, Task $task)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can edit tasks.');
        abort_unless($room->tasks()->where('tasks.id', $task->id)->exists(), 404, 'Task not found in the specified room.');

        $task->load(['media' => fn($q) => $q->orderBy('sort_order')]);
        $pivot = $room->tasks()->where('tasks.id', $task->id)->firstOrFail()->pivot;

        return view('rooms.tasks.edit', [
            'room' => $room,
            'task' => $task,
            'pivot' => $pivot,
        ]);
    }

    /**
     * PUT /rooms/{room}/tasks/{task}
     */
    public function updateTask(Request $request, Room $room, Task $task)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can update tasks.');
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

            return redirect()->route('rooms.tasks.index', $room)
                ->with('status', "Switched to task: {$existing->name}");
        }

        // update current task + pivot
        $task->update(['name' => $newName, 'type' => $data['type']]);
        $room->tasks()->updateExistingPivot($task->id, [
            'instructions' => $data['instructions'] ?? null,
            'visible_to_owner' => (bool)($data['visible_to_owner'] ?? true),
            'visible_to_housekeeper' => (bool)($data['visible_to_housekeeper'] ?? true),
        ]);
        return redirect()->route('rooms.tasks.index', $room)
            ->with('status', "Task updated: {$task->name}");
    }

    /**
     * DELETE /rooms/{room}/tasks/{task}
     */
    public function detachTask(Request $request, Room $room, Task $task)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can detach tasks.');

        $room->tasks()->detach($task->id);
        return redirect()->route('rooms.tasks.index', $room)
            ->with('status', "Detached task: {$task->name}");
    }

    /**
     * POST /rooms/{room}/tasks/bulk
     * Bulk create and attach tasks to a room
     */
    public function bulkStoreTask(Request $request, Room $room)
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

                $isNewTask = false;
                if (!$task) {
                    $task = Task::create([
                        'name' => $taskName,
                        'type' => $defaultType,
                        'is_default' => false,
                    ]);
                    $isNewTask = true;
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
                    if ($isNewTask) {
                        $created++;
                    }
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

            return redirect()->route('rooms.tasks.index', $room)
                ->with('status', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Failed to create tasks: ' . $e->getMessage()], 500);
            }

            return redirect()->back()
                ->withErrors(['tasks' => 'Failed to create tasks. Please try again.']);
        }
    }
}
