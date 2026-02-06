<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">New Assignment</h2>
    </x-slot>

    <x-card>
        <form method="post" action="{{ route('manage.sessions.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            <div>
                <x-form.label value="Property" />
                <x-form.select name="property_id" class="w-full rounded border-gray-300" required>
                    <option value="">Select property…</option>
                    @foreach ($properties as $p)
                        <option value="{{ $p->id }}" @selected(old('property_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.error :messages="$errors->get('property_id')" />
            </div>

            <div>
                <x-form.label value="Housekeeper" />
                <x-form.select name="housekeeper_id" class="w-full rounded border-gray-300" required>
                    <option value="">Select housekeeper…</option>
                    @foreach ($housekeepers as $hk)
                        <option value="{{ $hk->id }}" @selected(old('housekeeper_id') == $hk->id)>{{ $hk->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.error :messages="$errors->get('housekeeper_id')" />
            </div>

            <div>
                <x-form.label value="Scheduled date" />
                <x-form.input type="date" name="scheduled_date"
                    value="{{ old('scheduled_date', now()->toDateString()) }}" required />
                <x-form.error :messages="$errors->get('scheduled_date')" />
            </div>

            <div>
                <x-form.label value="Scheduled time" />
                <x-form.input type="time" name="scheduled_time"
                    value="{{ old('scheduled_time') }}" />
                <x-form.error :messages="$errors->get('scheduled_time')" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional: Set the time for this assignment</p>
            </div>

            <div>
                <x-form.label value="Status" />
                <x-form.select name="status" class="w-full rounded border-gray-300">
                    @foreach (['pending', 'in_progress', 'completed'] as $st)
                        <option value="{{ $st }}" @selected(old('status', 'pending') === $st)>
                            {{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </x-form.select>
                <x-form.error :messages="$errors->get('status')" />
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <x-button>Create</x-button>
                <a href="{{ route('manage.sessions.index') }}" class="px-3 py-1  rounded border dark:border-gray-700">Cancel</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
