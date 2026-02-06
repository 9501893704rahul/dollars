<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Edit Assignment</h2>
    </x-slot>

    <x-card>
        {{-- UPDATE FORM --}}
        <form id="assignment-update-form" method="post" action="{{ route('manage.sessions.update', $session) }}"
            class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('put')

            <div>
                <x-form.label value="Property" />
                <x-form.select name="property_id" class="w-full rounded border-gray-300" required>
                    @foreach ($properties as $p)
                        <option value="{{ $p->id }}" @selected(old('property_id', $session->property_id) == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.error :messages="$errors->get('property_id')" />
            </div>

            <div>
                <x-form.label value="Housekeeper" />
                <x-form.select name="housekeeper_id" class="w-full rounded border-gray-300" required>
                    @foreach ($housekeepers as $hk)
                        <option value="{{ $hk->id }}" @selected(old('housekeeper_id', $session->housekeeper_id) == $hk->id)>{{ $hk->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.error :messages="$errors->get('housekeeper_id')" />
            </div>

            <div>
                <x-form.label value="Scheduled date" />
                <x-form.input type="date" name="scheduled_date"
                    value="{{ old('scheduled_date', $session->scheduled_date->toDateString()) }}" required />
                <x-form.error :messages="$errors->get('scheduled_date')" />
            </div>

            <div>
                <x-form.label value="Scheduled time" />
                <x-form.input type="time" name="scheduled_time"
                    value="{{ old('scheduled_time', $session->scheduled_time ? (is_string($session->scheduled_time) ? substr($session->scheduled_time, 0, 5) : $session->scheduled_time->format('H:i')) : '') }}" />
                <x-form.error :messages="$errors->get('scheduled_time')" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional: Set the time for this assignment</p>
            </div>

            <div>
                <x-form.label value="Status" />
                <x-form.select name="status" class="w-full rounded border-gray-300" required>
                    @foreach (['pending', 'in_progress', 'completed'] as $st)
                        <option value="{{ $st }}" @selected(old('status', $session->status) === $st)>
                            {{ ucfirst(str_replace('_', ' ', $st)) }}
                        </option>
                    @endforeach
                </x-form.select>
                <x-form.error :messages="$errors->get('status')" />
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                {{-- Submit the UPDATE form explicitly --}}
                <x-button type="submit" form="assignment-update-form">Update</x-button>

                <x-button href="{{ route('manage.sessions.index') }}">Back</x-button>

                {{-- Trigger the DELETE form (defined below) --}}
                <x-button type="submit" variant="danger" form="delete_assignment"
                    onclick="return confirm('Delete assignment?')">
                    Delete
                </x-button>
            </div>
        </form>

        {{-- DELETE FORM (separate, not nested) --}}
        <form id="delete_assignment" method="post" action="{{ route('manage.sessions.destroy', $session) }}"
            class="hidden">
            @csrf
            @method('delete')
        </form>
    </x-card>
</x-app-layout>
