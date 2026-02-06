<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyRoomController extends Controller
{
    public function store(Request $request, Property $property)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can add rooms to properties.');
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable', Rule::in(['0', '1'])],
        ]);

        // 1) Find or create global Room by name (case-insensitive match)
        $name = trim($validated['name']);

        $room = Room::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if (!$room) {
            $room = Room::create([
                'name'       => $name,
                'is_default' => (bool) ($validated['is_default'] ?? false),
            ]);
        } else {
            // If user ticked default, update existing flag (optional)
            if (isset($validated['is_default'])) {
                $room->is_default = (bool) $validated['is_default'];
                $room->save();
            }
        }

        // 2) Attach to property via pivot, setting next sort_order if not already attached
        $already = $property->rooms()->where('rooms.id', $room->id)->exists();

        if (!$already) {
            $nextOrder = (int) $property->rooms()->max('property_room.sort_order') + 1;

            $property->rooms()->attach($room->id, [
                'sort_order' => $nextOrder,
            ]);
        }

        $message = $already
            ? "Assigned existing room: {$room->name}"
            : "Created & attached room: {$room->name}";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'room' => $room,
            ]);
        }

        return redirect()
            ->route('properties.rooms.index', $property)
            ->with('status', $message);
    }
}
