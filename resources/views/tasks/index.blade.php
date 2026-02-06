<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
            Tasks
        </h2>
    </x-slot>

    {{-- Toolbar: search + type filter + create --}}
    <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 px-1 sm:px-0">
        <form method="get" action="{{ route('tasks.index') }}" class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 min-w-0">
            <x-form.input name="q" placeholder="Search tasks…" value="{{ request('q') }}" class="w-full sm:flex-1 min-w-0 max-w-full" />

            <x-form.select name="type" class="w-full sm:w-auto sm:min-w-[140px] sm:max-w-[200px] !py-1">
                <option value="">All types</option>
                <option value="room" @selected(request('type') === 'room')>Room</option>
                <option value="inventory" @selected(request('type') === 'inventory')>Inventory</option>
            </x-form.select>

            <x-button type="submit" class="w-full sm:w-auto whitespace-nowrap">Filter</x-button>

            @if (request()->hasAny(['q', 'type', 'room_id']))
                <a href="{{ route('tasks.index') }}"
                    class="text-xs sm:text-sm underline text-gray-600 dark:text-gray-300 text-center sm:text-left">Reset</a>
            @endif
        </form>

        <x-button variant="primary" href="{{ route('tasks.create') }}" class="w-full sm:w-auto whitespace-nowrap">
            + New Task
        </x-button>
    </div>

    <x-card class="!px-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="uppercase text-xs tracking-wide">
                    <tr class="text-gray-600 dark:text-gray-300">
                        <th class="px-4 py-1 text-left">#</th>
                        <th class="px-4 py-1 text-left">Name</th>
                        <th class="px-4 py-1 text-center">Type</th>
                        <th class="px-4 py-1 text-center">Default?</th>
                        <th class="px-4 py-1 text-center">Created</th>
                        <th class="px-4 py-1 w-40 text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y dark:divide-gray-700">
                    @forelse($tasks as $t)
                        <tr>
                            <td class="px-4 py-1 text-left">{{ ($tasks->firstItem() ?? 0) + $loop->index }}</td>
                            <td class="px-4 py-1 font-medium text-gray-900 dark:text-gray-100">
                                {{ $t->name }}
                            </td>
                            <td class="px-4 py-1 text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs
                                    {{ $t->type === 'inventory'
                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-400/20 dark:text-blue-300'
                                        : 'bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ ucfirst($t->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-1 text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs
                                    {{ $t->is_default
                                        ? 'bg-green-100 text-green-800 dark:bg-green-400/20 dark:text-green-300'
                                        : 'bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ $t->is_default ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-1 text-center">
                                {{ $t->created_at?->format('Y-m-d') }}
                            </td>
                            <td class="px-4 py-1 text-right whitespace-nowrap">
                                <x-action-dropdown align="right" width="w-48" label="Task actions">
                                    <x-dropdown.item href="{{ route('tasks.edit', $t) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M3 17.25V21h3.75l11-11-3.75-3.75-11 11zM20.71 7.04a1.003 1.003 0 0 0 0-1.42L18.37 3.29a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.83z" />
                                        </svg>
                                        <span>Edit</span>
                                    </x-dropdown.item>

                                    <x-dropdown.item as="form" method="POST" href="{{ route('tasks.destroy', $t) }}"
                                        onclick="return confirm('Delete this task?')">
                                        @csrf
                                        @method('DELETE')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9 3a1 1 0 0 0-1 1v1H4a1 1 0 1 0 0 2h1v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7h1a1 1 0 1 0 0-2h-4V4a1 1 0 0 0-1-1H9zm2 4a1 1 0 1 0-2 0v10a1 1 0 1 0 2 0V7zm4 0a1 1 0 1 0-2 0v10a1 1 0 1 0 2 0V7z" />
                                        </svg>
                                        <span>Delete</span>
                                    </x-dropdown.item>
                                </x-action-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-10 text-center text-gray-500 dark:text-gray-400" colspan="6">
                                No tasks found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($tasks, 'links'))
            <div class="px-4 py-3">
                {{ $tasks->links() }}
            </div>
        @endif
    </x-card>
</x-app-layout>
