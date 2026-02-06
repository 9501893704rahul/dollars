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

    {{-- MEDIA UPLOAD SECTION (for showing examples to housekeepers) --}}
    <x-card class="mt-6">
        <h3 class="text-lg font-semibold mb-4">Example Media</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Upload photos or videos to show housekeepers how this task should be done.
        </p>

        {{-- Existing Media --}}
        @if($task->media->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mb-6">
                @foreach($task->media as $media)
                    <div class="relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                        @if($media->type === 'image')
                            <img src="{{ Storage::disk('public')->url($media->url) }}" alt="{{ $media->caption ?? 'Task example' }}"
                                 class="w-full h-32 object-cover" />
                        @else
                            <video src="{{ Storage::disk('public')->url($media->url) }}" class="w-full h-32 object-cover" controls muted></video>
                        @endif
                        @if($media->caption)
                            <span class="absolute bottom-1 left-1 text-xs px-2 py-1 rounded bg-black/60 text-white truncate max-w-[90%]">
                                {{ Str::limit($media->caption, 30) }}
                            </span>
                        @endif
                        <form method="POST" action="{{ route('tasks.media.destroy', [$task, $media]) }}" class="absolute top-1 right-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this media?')"
                                    class="p-1 bg-red-500 text-white rounded-full hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Upload New Media --}}
        <form method="POST" action="{{ route('tasks.media.store', $task) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <x-form.label for="media" value="Add Photos or Videos" />
                <input type="file" name="media[]" id="media" multiple
                       accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime"
                       class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-full file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100
                              dark:file:bg-gray-700 dark:file:text-gray-300" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Supported: JPEG, PNG, WebP images and MP4, MOV videos (max 20MB each)
                </p>
                <x-form.error :messages="$errors->get('media.*')" />
            </div>
            <x-button type="submit">Upload Media</x-button>
        </form>
    </x-card>

</x-app-layout>
