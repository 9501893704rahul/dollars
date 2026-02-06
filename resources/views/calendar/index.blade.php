<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl">
                {{ $acting === 'housekeeper' ? 'My Schedule' : 'Cleaning Sessions Calendar' }}
            </h2>
            <div class="flex items-center gap-2">
                {{-- Role scope indicator (admin can act as owner via ?as=owner) --}}
                <span class="text-xs px-2 py-1 rounded border bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                    Scope: {{ ucfirst($acting) }}
                </span>
                <x-button variant="secondary"
                    href="{{ route('calendar.index', ['month' => $prevMonth] + request()->except('page')) }}"
                    class="!py-1">← Prev</x-button>
                <x-button variant="secondary" href="{{ route('calendar.index') }}" class="!py-1">Today</x-button>
                <x-button variant="secondary"
                    href="{{ route('calendar.index', ['month' => $nextMonth] + request()->except('page')) }}"
                    class="!py-1">Next →</x-button>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Calendar grid --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="font-medium">
                        {{ $monthStart->format('F Y') }}
                    </div>
                    {{-- Quick legend --}}
                    <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-3">
                        <span class="inline-flex items-center gap-1"><span
                                class="h-2 w-2 rounded-full bg-indigo-600 inline-block"></span> has assignments</span>
                    </div>
                </div>

                <div class="p-2">
                    {{-- Weekday headers --}}
                    <div class="grid grid-cols-7 text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $w)
                            <div class="px-2 py-2 text-center">{{ $w }}</div>
                        @endforeach
                    </div>

                    {{-- Days --}}
                    <div class="grid grid-cols-7 text-sm">
                        @foreach ($days as $day)
                            @php
                                $isSel = $selectedDay === $day['date'];
                            @endphp
                            <a href="{{ route('calendar.index', ['month' => \Carbon\Carbon::parse($day['date'])->format('Y-m'), 'day' => $day['date']] + request()->except('page')) }}"
                                class="h-20 border -m-[0.5px] p-2 relative
                  {{ $day['inMonth'] ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900/40' }}
                  border-gray-200 dark:border-gray-700
                  {{ $isSel ? 'ring-2 ring-indigo-600 z-10' : '' }}
                ">
                                <div class="flex items-start justify-between">
                                    <span
                                        class="text-xs {{ $day['inMonth'] ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400' }}">
                                        {{ \Carbon\Carbon::parse($day['date'])->format('j') }}
                                    </span>
                                    @if ($day['isToday'])
                                        <span
                                            class="text-[10px] px-1 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">Today</span>
                                    @endif
                                </div>
                                @if ($day['count'] > 0)
                                    <div class="absolute bottom-2 left-2 right-2 flex items-center justify-between">
                                        <span class="text-[11px] text-gray-600 dark:text-gray-300">{{ $day['count'] }}
                                            assignment{{ $day['count'] > 1 ? 's' : '' }}</span>
                                        <span class="h-2 w-2 rounded-full bg-indigo-600"></span>
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Day details --}}
        <div>
            <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="font-medium">
                        {{ $selectedDay ? \Carbon\Carbon::parse($selectedDay)->toFormattedDateString() : 'Select a day' }}
                    </div>
                </div>
                <div class="p-4">
                    @if (!$selectedDay)
                        <p class="text-sm text-gray-600 dark:text-gray-300">Click a date to view assignments.</p>
                    @else
                        @if ($daySessions->isEmpty())
                            <p class="text-sm text-gray-600 dark:text-gray-300">No assignments on this date.</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($daySessions as $s)
                                    <li class="border border-gray-200 dark:border-gray-700 rounded p-3 hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $s->property->name }}</div>
                                                <div class="flex items-center gap-3 mt-1">
                                                    @if ($s->scheduled_time)
                                                        <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                                            {{ \Carbon\Carbon::parse($s->scheduled_time)->format('g:i A') }}
                                                        </div>
                                                    @endif
                                                    @if ($acting !== 'housekeeper')
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            Housekeeper: {{ $s->housekeeper->name }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <a href="{{ route('sessions.show', $s) }}"
                                                class="ml-3 px-3 py-1.5 text-sm rounded-md bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                                View
                                            </a>
                                        </div>
                                        <div class="mt-2">
                                            <x-status-badge :status="$s->status" />
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Quick tips for HKs --}}
            @if ($acting === 'housekeeper')
                <div class="mt-4 text-xs text-gray-600 dark:text-gray-400">
                    Tip: Start a session from this list → GPS confirm → complete room tasks → inventory → upload ≥8
                    photos per room → submit.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
