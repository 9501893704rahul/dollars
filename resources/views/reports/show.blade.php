{{-- resources/views/reports/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl flex items-center gap-2">
                Completion Report — {{ $session->property->name }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.download', $session) }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Summary Card --}}
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Property</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $session->property->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $session->property->address }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Housekeeper</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $session->housekeeper->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $session->housekeeper->email }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
                    <div class="mt-1">
                        @if($session->status === 'completed')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Completed
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                {{ ucfirst($session->status) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled Date</h3>
                    <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                        {{ $session->scheduled_date->format('M d, Y') }}
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Started At</h3>
                    <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                        {{ $session->started_at?->format('H:i') ?? '—' }}
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed At</h3>
                    <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                        {{ $session->ended_at?->format('H:i') ?? '—' }}
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</h3>
                    <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                        {{ $duration ?? '—' }}
                    </p>
                </div>
            </div>

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            {{-- Completion Progress --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Completion Rate</h3>
                    <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $completionRate }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                         style="width: {{ $completionRate }}%"></div>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $completedTasks }} of {{ $totalTasks }} tasks completed
                </p>
            </div>
        </x-card>

        {{-- Email Report --}}
        <x-card>
            <h3 class="text-lg font-semibold mb-4">Email Report</h3>
            <form method="POST" action="{{ route('reports.email', $session) }}" class="flex items-end gap-4">
                @csrf
                <div class="flex-1">
                    <x-form.label for="email" value="Recipient Email" />
                    <x-form.input type="email" name="email" id="email" class="w-full" placeholder="owner@example.com" required />
                </div>
                <x-button type="submit">Send Report</x-button>
            </form>
            @if(session('success'))
                <p class="mt-2 text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
            @endif
        </x-card>

        {{-- Room Tasks --}}
        @foreach($rooms as $room)
            <x-card>
                <h3 class="text-lg font-semibold mb-4">{{ $room->name }}</h3>
                
                @php
                    $roomItems = $session->checklistItems->where('room_id', $room->id);
                    $roomPhotos = $photosByRoom->get($room->id, collect());
                @endphp

                {{-- Tasks --}}
                <div class="space-y-2 mb-4">
                    @foreach($room->tasks as $task)
                        @php
                            $item = $roomItems->first(fn($i) => $i->task_id === $task->id);
                        @endphp
                        <div class="flex items-center gap-3 p-2 rounded-lg {{ $item?->checked ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-800' }}">
                            @if($item?->checked)
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2" />
                                </svg>
                            @endif
                            <span class="{{ $item?->checked ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $task->name }}
                            </span>
                            @if($item?->note)
                                <span class="ml-auto text-xs text-gray-500 dark:text-gray-400 italic">
                                    Note: {{ Str::limit($item->note, 50) }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Room Photos --}}
                @if($roomPhotos->count() > 0)
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Photos ({{ $roomPhotos->count() }})</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($roomPhotos as $photo)
                            <div class="relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                <img src="{{ asset('storage/' . $photo->path) }}" alt="Room photo" class="w-full h-24 object-cover" />
                                <span class="absolute bottom-1 right-1 text-[10px] px-1.5 py-0.5 rounded bg-black/60 text-white">
                                    {{ $photo->captured_at?->format('H:i') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        @endforeach
    </div>
</x-app-layout>
