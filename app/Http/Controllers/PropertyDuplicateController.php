<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyDuplicateRequest;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

class PropertyDuplicateController extends Controller
{
    public function store(PropertyDuplicateRequest $request, Property $property)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can duplicate properties.');

        // Owners can only duplicate their own properties (defense-in-depth).
        // If a user has BOTH roles (admin + owner), treat them as admin here.
        if ($user->hasRole('owner') && ! $user->hasRole('admin') && $property->owner_id !== $user->id) {
            abort(403, 'You cannot duplicate properties you do not own.');
        }

        $property->load(['rooms', 'propertyTasks']);

        $data = $request->validated();

        $newProperty = DB::transaction(function () use ($property, $data) {
            // Duplicate the property record (keep same owner)
            $newData = $property->only($property->getFillable());
            $newData['name'] = trim($data['name']);
            $newData['owner_id'] = $property->owner_id;

            /** @var Property $clone */
            $clone = Property::create($newData);

            // Copy selected rooms (preserve parent's relative order)
            $roomIds = array_values(array_unique($data['room_ids'] ?? []));
            if (!empty($roomIds)) {
                $rooms = $property->rooms
                    ->whereIn('id', $roomIds)
                    ->sortBy(fn($r) => (int) ($r->pivot->sort_order ?? 0))
                    ->values();

                $payload = [];
                $order = 1;
                foreach ($rooms as $room) {
                    $payload[$room->id] = ['sort_order' => $order++];
                }
                if (!empty($payload)) {
                    $clone->rooms()->attach($payload);
                }
            }

            // Copy selected property-level tasks (preserve order + pivot metadata)
            $taskIds = array_values(array_unique($data['task_ids'] ?? []));
            if (!empty($taskIds)) {
                $tasks = $property->propertyTasks
                    ->whereIn('id', $taskIds)
                    ->sortBy(fn($t) => (int) ($t->pivot->sort_order ?? 0))
                    ->values();

                $payload = [];
                $order = 1;
                foreach ($tasks as $task) {
                    $payload[$task->id] = [
                        'sort_order' => $order++,
                        'instructions' => $task->pivot->instructions ?? null,
                        'visible_to_owner' => (bool) ($task->pivot->visible_to_owner ?? true),
                        'visible_to_housekeeper' => (bool) ($task->pivot->visible_to_housekeeper ?? true),
                    ];
                }
                if (!empty($payload)) {
                    $clone->propertyTasks()->attach($payload);
                }
            }

            return $clone;
        });

        return redirect()
            ->route('properties.edit', $newProperty)
            ->with('ok', 'Property duplicated successfully.');
    }
}
