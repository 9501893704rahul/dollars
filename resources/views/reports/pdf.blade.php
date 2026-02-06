<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cleaning Report - {{ $session->property->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 20px; color: #1f2937; margin-bottom: 5px; }
        h2 { font-size: 16px; color: #374151; margin: 20px 0 10px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        h3 { font-size: 14px; color: #4b5563; margin: 15px 0 8px 0; }
        .header { text-align: center; margin-bottom: 30px; }
        .header p { color: #6b7280; margin: 5px 0; }
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-table td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        .summary-table .label { color: #6b7280; width: 30%; }
        .summary-table .value { font-weight: 600; color: #1f2937; }
        .progress-bar { background: #e5e7eb; height: 10px; border-radius: 5px; overflow: hidden; margin: 5px 0; }
        .progress-fill { background: #4f46e5; height: 100%; }
        .task-list { margin: 0; padding: 0; list-style: none; }
        .task-item { padding: 6px 0; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; }
        .task-checkbox { width: 16px; height: 16px; border: 2px solid #d1d5db; border-radius: 3px; margin-right: 10px; display: inline-block; vertical-align: middle; }
        .task-checkbox.checked { background: #10b981; border-color: #10b981; position: relative; }
        .task-checkbox.checked::after { content: "✓"; color: white; font-size: 12px; position: absolute; top: -2px; left: 2px; }
        .task-name { flex: 1; }
        .task-note { color: #6b7280; font-size: 10px; font-style: italic; margin-left: 10px; }
        .room-section { margin-bottom: 25px; page-break-inside: avoid; }
        .photo-grid { display: table; width: 100%; }
        .photo-item { display: inline-block; width: 23%; margin: 1%; vertical-align: top; }
        .photo-item img { width: 100%; height: 60px; object-fit: cover; border-radius: 4px; }
        .photo-time { font-size: 9px; color: #6b7280; text-align: center; }
        .footer { margin-top: 30px; text-align: center; color: #9ca3af; font-size: 10px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Cleaning Completion Report</h1>
        <p>{{ $session->property->name }}</p>
        <p>Generated: {{ now()->format('F d, Y H:i') }}</p>
    </div>

    <h2>Session Summary</h2>
    <table class="summary-table">
        <tr>
            <td class="label">Property</td>
            <td class="value">{{ $session->property->name }}</td>
        </tr>
        <tr>
            <td class="label">Address</td>
            <td class="value">{{ $session->property->address }}</td>
        </tr>
        <tr>
            <td class="label">Housekeeper</td>
            <td class="value">{{ $session->housekeeper->name }}</td>
        </tr>
        <tr>
            <td class="label">Owner</td>
            <td class="value">{{ $session->owner?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Scheduled Date</td>
            <td class="value">{{ $session->scheduled_date->format('F d, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Started At</td>
            <td class="value">{{ $session->started_at?->format('H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Completed At</td>
            <td class="value">{{ $session->ended_at?->format('H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Duration</td>
            <td class="value">{{ $duration ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td class="value">
                <span class="badge {{ $session->status === 'completed' ? 'badge-completed' : 'badge-pending' }}">
                    {{ ucfirst($session->status) }}
                </span>
            </td>
        </tr>
    </table>

    <h2>Completion Progress</h2>
    <p>{{ $completedTasks }} of {{ $totalTasks }} tasks completed ({{ $completionRate }}%)</p>
    <div class="progress-bar">
        <div class="progress-fill" style="width: {{ $completionRate }}%"></div>
    </div>

    @foreach($rooms as $room)
        <div class="room-section">
            <h2>{{ $room->name }}</h2>
            
            @php
                $roomItems = $session->checklistItems->where('room_id', $room->id);
                $roomPhotos = $photosByRoom->get($room->id, collect());
            @endphp

            <h3>Tasks</h3>
            <ul class="task-list">
                @foreach($room->tasks as $task)
                    @php
                        $item = $roomItems->first(fn($i) => $i->task_id === $task->id);
                    @endphp
                    <li class="task-item">
                        <span class="task-checkbox {{ $item?->checked ? 'checked' : '' }}"></span>
                        <span class="task-name">{{ $task->name }}</span>
                        @if($item?->note)
                            <span class="task-note">Note: {{ Str::limit($item->note, 30) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if($roomPhotos->count() > 0)
                <h3>Photos ({{ $roomPhotos->count() }})</h3>
                <div class="photo-grid">
                    @foreach($roomPhotos->take(8) as $photo)
                        <div class="photo-item">
                            <img src="{{ public_path('storage/' . $photo->path) }}" alt="Room photo">
                            <div class="photo-time">{{ $photo->captured_at?->format('H:i') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <p>This report was automatically generated by the HK Checklist System.</p>
        <p>© {{ date('Y') }} All rights reserved.</p>
    </div>
</body>
</html>
