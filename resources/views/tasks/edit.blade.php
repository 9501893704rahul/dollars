{{-- resources/views/tasks/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl flex items-center gap-2">
            Edit Task — {{ $task->name }}
        </h2>
    </x-slot>

    {{-- UPDATE FORM --}}
    <x-card>
        <form method="post" action="{{ route('tasks.update', $task) }}" class="space-y-6" x-data="{
            taskName: @js(old('name', $task->name)),
            _capitalizeTimer: null,
            capitalizeText(text) {
                if (!text) return '';
                return text.toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
            },
            debounceCapitalize() {
                clearTimeout(this._capitalizeTimer);
                this._capitalizeTimer = setTimeout(() => {
                    if (this.taskName && this.taskName.trim()) {
                        const capitalized = this.capitalizeText(this.taskName);
                        if (capitalized !== this.taskName) {
                            this.taskName = capitalized;
                        }
                    }
                }, 500);
            },
            handleSubmit(event) {
                // Ensure capitalized value is set before submission
                if (this.taskName && this.taskName.trim()) {
                    const capitalized = this.capitalizeText(this.taskName.trim());
                    const nameInput = event.target.querySelector('#name');
                    if (nameInput) {
                        nameInput.value = capitalized;
                    }
                }
            }
        }" @submit="handleSubmit($event)">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Name --}}
                <div class="md:col-span-2">
                    <x-form.label for="name" value="Name" />
                    <input 
                        id="name" 
                        name="name" 
                        type="text"
                        x-model="taskName"
                        @input="debounceCapitalize()"
                        class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                        required />
                    <x-form.error :messages="$errors->get('name')" />
                </div>

                {{-- Type --}}
                <div>
                    <x-form.label for="type" value="Type" />
                    <select id="type" name="type"
                        class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <option value="room" @selected(old('type', $task->type) === 'room')>Room</option>
                        <option value="inventory" @selected(old('type', $task->type) === 'inventory')>Inventory</option>
                    </select>
                    <x-form.error :messages="$errors->get('type')" />
                </div>

                {{-- Default template --}}
                <div class="flex items-center gap-2 pt-6">
                    <x-form.checkbox id="is_default" name="is_default" value="1" :checked="old('is_default', $task->is_default)" />
                    <label for="is_default">Mark as default template</label>
                </div>

                {{-- Instructions --}}
                <div class="md:col-span-2">
                    <x-form.label for="instructions" value="Instructions (optional)" />
                    <textarea id="instructions" name="instructions" rows="6"
                        class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        placeholder="Write step-by-step guidance for performing this task...">{{ old('instructions', $task->instructions) }}</textarea>
                    <x-form.error :messages="$errors->get('instructions')" />
                </div>

                {{-- Meta (optional) --}}
                <div class="md:col-span-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400 flex flex-wrap gap-x-4">
                        <span>Created: {{ $task->created_at?->format('Y-m-d H:i') }}</span>
                        <span>Updated: {{ $task->updated_at?->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit">Update</x-button>
                <x-button variant="secondary" href="{{ route('tasks.index') }}">Cancel</x-button>
            </div>
        </form>
    </x-card>

</x-app-layout>
