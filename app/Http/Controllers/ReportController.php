<?php

namespace App\Http\Controllers;

use App\Models\CleaningSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Generate a completion report for a session.
     */
    public function show(CleaningSession $session)
    {
        // Check authorization
        $user = Auth::user();
        $canView = $user->hasRole('admin') 
            || $session->owner_id === $user->id 
            || $session->housekeeper_id === $user->id
            || ($user->hasRole('company') && (
                $session->owner?->company_id === $user->id 
                || $session->housekeeper?->company_id === $user->id
            ));
        
        abort_unless($canView, 403, 'You do not have permission to view this report.');

        $data = $this->prepareReportData($session);
        return view('reports.show', $data);
    }

    /**
     * Download completion report as PDF.
     */
    public function download(CleaningSession $session)
    {
        // Check authorization
        $user = Auth::user();
        $canView = $user->hasRole('admin') 
            || $session->owner_id === $user->id 
            || $session->housekeeper_id === $user->id
            || ($user->hasRole('company') && (
                $session->owner?->company_id === $user->id 
                || $session->housekeeper?->company_id === $user->id
            ));
        
        abort_unless($canView, 403, 'You do not have permission to download this report.');

        $data = $this->prepareReportData($session);
        $pdf = Pdf::loadView('reports.pdf', $data);
        
        $filename = 'cleaning-report-' . $session->id . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Email completion report.
     */
    public function email(Request $request, CleaningSession $session)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Check authorization
        $user = Auth::user();
        $canView = $user->hasRole('admin') 
            || $session->owner_id === $user->id 
            || $session->housekeeper_id === $user->id
            || ($user->hasRole('company') && (
                $session->owner?->company_id === $user->id 
                || $session->housekeeper?->company_id === $user->id
            ));
        
        abort_unless($canView, 403, 'You do not have permission to email this report.');

        $data = $this->prepareReportData($session);
        $pdf = Pdf::loadView('reports.pdf', $data);
        $pdfContent = $pdf->output();

        // Send email with PDF attachment
        Mail::send('emails.completion-report', $data, function ($message) use ($request, $session, $pdfContent) {
            $message->to($request->input('email'))
                    ->subject('Cleaning Completion Report - ' . $session->property->name)
                    ->attachData($pdfContent, 'cleaning-report-' . $session->id . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
        });

        return back()->with('success', 'Report sent to ' . $request->input('email'));
    }

    /**
     * Prepare data for the report.
     */
    private function prepareReportData(CleaningSession $session): array
    {
        $session->load([
            'property',
            'housekeeper',
            'owner',
            'checklistItems.task',
            'photos.room',
        ]);

        $rooms = $session->property->rooms()
            ->with(['tasks' => fn($q) => $q->orderBy('room_task.sort_order')])
            ->orderBy('property_room.sort_order')
            ->get();

        // Get property-level tasks
        $propertyTasks = $session->property->propertyTasks()
            ->orderBy('property_tasks.sort_order')
            ->get();

        // Calculate completion stats
        $totalTasks = $session->checklistItems->count();
        $completedTasks = $session->checklistItems->where('checked', true)->count();
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        // Group photos by room
        $photosByRoom = $session->photos->groupBy('room_id');

        // Duration
        $duration = null;
        if ($session->started_at && $session->ended_at) {
            $duration = $session->started_at->diffForHumans($session->ended_at, ['parts' => 2, 'short' => true]);
        }

        return [
            'session' => $session,
            'rooms' => $rooms,
            'propertyTasks' => $propertyTasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'completionRate' => $completionRate,
            'photosByRoom' => $photosByRoom,
            'duration' => $duration,
        ];
    }
}
