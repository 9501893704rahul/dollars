<?php

namespace App\Http\Controllers;

use App\Models\CleaningSession;
use App\Models\RoomPhoto;
use App\Services\ImageTimestampService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function store(Request $request, CleaningSession $session, $roomId)
    {
        $request->validate([
            'photos.*' => ['required', 'image', 'max:5120'], // 5MB per image
        ]);

        $room   = $session->property->rooms()->findOrFail($roomId);
        $saved  = [];
        
        // Get files from request - ensure we only process unique files
        $files = $request->file('photos', []);
        
        // Remove duplicates by comparing file content hash
        $processedHashes = [];
        foreach ($files as $file) {
            // Create a hash of the file content to detect duplicates
            $fileHash = md5_file($file->getRealPath());
            
            // Skip if we've already processed this file
            if (in_array($fileHash, $processedHashes)) {
                continue;
            }
            
            $processedHashes[] = $fileHash;
            
            $filename = $file->store('room_photos', 'public');
            $photo    = $session->photos()->create([
                'room_id'     => $room->id,
                'path'        => $filename,
                'captured_at' => now(),
                // optionally set has_timestamp_overlay = true if your service overlays it
            ]);
            $saved[] = [
                'id'          => $photo->id,
                'url'         => asset('storage/' . $filename),
                'captured_at' => $photo->captured_at->format('H:i'),
            ];
        }

        // For AJAX calls, return JSON. The front end can add these to the gallery without reloading.
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => count($saved) . ' photos uploaded.',
                'photos'  => $saved,
            ]);
        }

        // Fallback to standard redirect if not an AJAX request
        return back()->with('ok', count($saved) . ' photos uploaded.');
    }

    public function destroy(CleaningSession $session, RoomPhoto $photo)
    {
        // Verify the photo belongs to this session
        if ($photo->session_id !== $session->id) {
            return response()->json(['success' => false, 'message' => 'Photo not found'], 404);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($photo->path)) {
            Storage::disk('public')->delete($photo->path);
        }

        // Delete from database
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully',
        ]);
    }
}
