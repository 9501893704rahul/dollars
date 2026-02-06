<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyRoomAttachController extends Controller
{
    public function store(Request $request, Property $property)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['admin', 'owner']), 403, 'Only administrators and owners can attach rooms to properties.');

        $data = $request->validate([
            'room_ids'   => ['array'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
            'room_names' => ['array'],
            'room_names.*' => ['string', 'max:255'],
        ]);

        $roomIds = $data['room_ids'] ?? [];
        $roomNames = array_filter($data['room_names'] ?? [], fn($v) => trim($v) !== '');

        // Create new rooms from free-text names if any
        if (!empty($roomNames)) {
            foreach ($roomNames as $name) {
                $room = Room::firstOrCreate(
                    ['name' => trim($name)],
                    ['is_default' => false]
                );
                $roomIds[] = $room->id;
            }
        }

        if (empty($roomIds)) {
            return back()->with('warn', 'No rooms selected.');
        }

        DB::transaction(function () use ($property, $roomIds) {
            // Determine next sort order
            $nextSort = (int) $property->rooms()->max('property_room.sort_order');

            // Filter out already-attached room IDs
            $already = $property->rooms()->pluck('rooms.id')->all();
            $toAttach = array_values(array_diff($roomIds, $already));
            if (empty($toAttach)) {
                return;
            }

            $attachPayload = [];
            foreach ($toAttach as $id) {
                $attachPayload[$id] = ['sort_order' => ++$nextSort];
            }

            $property->rooms()->syncWithoutDetaching($attachPayload);
        });

        return back()->with('ok', 'Rooms attached successfully.');
    }
}
