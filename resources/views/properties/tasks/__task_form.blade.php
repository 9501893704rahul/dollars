@props([
    'property',
    'room',
    'task' => null, // null for create, Task model for edit
    'pivot' => null, // Pivot data for edit mode
    'suggestUrl',
    'mode' => 'create', // 'create' or 'edit'
])

@php
    $isEdit = $mode === 'edit' && $task;
    $storeUrl = $isEdit
        ? route('properties.tasks.update', [$property, $room, $task])
        : route('properties.tasks.store', [$property, $room]);
    $panelName = $isEdit
        ? "edit-task-{$property->id}-{$room->id}-{$task->id}"
        : "add-task-{$property->id}-{$room->id}";
@endphp

<div class="p-0 sm:p-2 md:p-4 lg:p-6 space-y-4 sm:space-y-6 max-w-full" x-data="propertyTaskForm({
    suggestUrl: @js($suggestUrl),
    storeUrl: @js($storeUrl),
    csrf: @js(csrf_token()),
    propertyId: @js($property->id),
    roomId: @js($room->id),
    taskId: @js($task?->id),
    mode: @js($mode),
    panelName: @js($panelName),
    initialData: @js($isEdit ? [
        'name' => $task->name ?? '',
        'type' => $task->type ?? 'room',
        'instructions' => $pivot->instructions ?? '',
        'visible_to_owner' => (bool) ($pivot->visible_to_owner ?? true),
        'visible_to_housekeeper' => (bool) ($pivot->visible_to_housekeeper ?? true),
    ] : [
        'name' => '',
        'type' => 'room',
        'instructions' => '',
        'visible_to_owner' => true,
        'visible_to_housekeeper' => true,
    ])
})">
    <form @submit.prevent="submitForm" enctype="multipart/form-data" class="space-y-6">
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Task Name with Autocomplete --}}
        <div x-data="taskAutocomplete({ suggestUrl: @js($suggestUrl) })" x-init="@if($isEdit) q = @js($task->name) @endif">
            <label for="task-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Task Name <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <input
                    x-model="q"
                    x-ref="nameInput"
                    name="name"
                    id="task-name"
                    type="text"
                    required
                    autocomplete="off"
                    placeholder="e.g., Wipe counters, Make bed"
                    class="w-full max-w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                           transition-all duration-200 px-3 sm:px-4 py-2.5 text-sm"
                    @input="onInput"
                    @focus="onFocus"
                    @keydown="keyDown"
                    aria-autocomplete="list"
                    aria-expanded="open"
                    aria-controls="task-suggest"
                />

                {{-- Autocomplete Dropdown --}}
                <div
                    x-cloak
                    x-show="open"
                    id="task-suggest"
                    role="listbox"
                    class="absolute z-50 mt-1 w-full max-w-full rounded-lg border border-gray-200 dark:border-gray-700
                           bg-white dark:bg-gray-800 shadow-xl overflow-hidden"
                >
                    <div x-show="loading" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Searching…
                    </div>

                    <template x-if="hasResults">
                        <ul class="max-h-60 overflow-y-auto">
                            <template x-for="(item,idx) in items" :key="item.id">
                                <li
                                    class="px-4 py-2.5 cursor-pointer text-sm hover:bg-gray-50 dark:hover:bg-gray-700
                                           flex items-center justify-between transition-colors"
                                    :class="{'bg-gray-50 dark:bg-gray-700': focusedIndex===idx}"
                                    @mouseenter="focusedIndex=idx"
                                    @mouseleave="focusedIndex=-1"
                                    @click="choose(item)"
                                >
                                    <span x-text="item.name" class="text-gray-900 dark:text-gray-100 font-medium"></span>
                                    <span class="text-[10px] uppercase ml-2 px-2 py-0.5 rounded-full font-medium"
                                          :class="item.type==='inventory'
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-400/20 dark:text-blue-300'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                                        <span x-text="item.type"></span>
                                    </span>
                                </li>
                            </template>
                            <li
                                x-show="q && !items.some(i=>i.name.toLowerCase()===q.toLowerCase())"
                                class="px-4 py-2.5 cursor-pointer text-sm text-indigo-700 dark:text-indigo-300
                                       bg-indigo-50/60 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50
                                       transition-colors flex items-center gap-2"
                                :class="{'ring-2 ring-indigo-400': focusedIndex===items.length}"
                                @mouseenter="focusedIndex=items.length"
                                @mouseleave="focusedIndex=-1"
                                @click="open=false"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create "<span x-text="q" class="font-semibold"></span>"
                            </li>
                        </ul>
                    </template>

                    <div
                        x-show="!loading && !hasResults && q"
                        class="px-4 py-2.5 text-sm text-indigo-700 dark:text-indigo-300
                               bg-indigo-50/60 dark:bg-indigo-900/30 flex items-center gap-2"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create "<span x-text="q" class="font-semibold"></span>"
                    </div>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Start typing to search existing tasks or create a new one</p>
        </div>

        {{-- Task Type --}}
        <div>
            <label for="task-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Type <span class="text-rose-500">*</span>
            </label>
            <select
                name="type"
                id="task-type"
                x-model="formData.type"
                class="w-full max-w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100
                       focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                       transition-all duration-200 px-3 sm:px-4 py-2.5 text-sm"
            >
                <option value="room">Room</option>
                <option value="inventory">Inventory</option>
            </select>
        </div>

        {{-- Instructions --}}
        <div>
            <label for="task-instructions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Instructions <span class="text-xs text-gray-500">(optional)</span>
            </label>
            <textarea
                name="instructions"
                id="task-instructions"
                rows="4"
                x-model="formData.instructions"
                placeholder="Short steps, tips, or link to SOP..."
                class="w-full max-w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100
                       focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                       transition-all duration-200 px-3 sm:px-4 py-2.5 text-sm resize-none"
            ></textarea>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Visible to staff during cleaning sessions</p>
        </div>

        {{-- Visibility Options --}}
        <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Visibility
            </label>
            <div class="space-y-2">
                <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700
                              hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                    <x-form.checkbox
                        name="visible_to_owner"
                        value="1"
                        x-model="formData.visible_to_owner"
                    />
                    <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Owner can view</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Task visible to property owners</div>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700
                              hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                    <x-form.checkbox
                        name="visible_to_housekeeper"
                        value="1"
                        x-model="formData.visible_to_housekeeper"
                    />
                    <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Housekeeper can view</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Task visible during cleaning sessions</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Media Upload (only for create mode) --}}
        @if(!$isEdit)
            <div x-data="{ files: [], previews: [] }" x-init="
                $watch('files', f => {
                    previews = [...f].map(file => ({
                        url: URL.createObjectURL(file),
                        type: file.type.startsWith('video') ? 'video':'image',
                        name: file.name
                    }))
                })
            ">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Instructional Media <span class="text-xs text-gray-500">(optional)</span>
                </label>
                <div class="mt-2">
                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed
                                  border-gray-300 dark:border-gray-700 rounded-lg cursor-pointer
                                  hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-10 h-10 mb-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-semibold">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Images or videos up to 20MB each</p>
                        </div>
                        <input
                            type="file"
                            name="media[]"
                            multiple
                            accept="image/*,video/*"
                            class="hidden"
                            x-on:change="files = Array.from($event.target.files)"
                        />
                    </label>
                </div>

                {{-- Media Previews --}}
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3" x-show="previews.length" x-cloak>
                    <template x-for="(p, i) in previews" :key="i">
                        <div class="relative group rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <template x-if="p.type==='image'">
                                <img :src="p.url" class="w-full h-32 object-cover" />
                            </template>
                            <template x-if="p.type==='video'">
                                <video :src="p.url" class="w-full h-32 object-cover" muted></video>
                            </template>
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button
                                    type="button"
                                    @click="files = files.filter((_, idx) => idx !== i); previews = previews.filter((_, idx) => idx !== i)"
                                    class="text-white hover:text-rose-300"
                                >
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <input
                                type="text"
                                name="captions[]"
                                placeholder="Caption (optional)"
                                class="w-full max-w-full border-t border-gray-200 dark:border-gray-700 px-2 sm:px-3 py-2 text-xs
                                       dark:bg-gray-800 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>
                    </template>
                </div>
            </div>
        @endif

        {{-- Error Message --}}
        <div x-show="error" x-cloak class="p-3 rounded-lg bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
            <p class="text-sm text-rose-800 dark:text-rose-200" x-text="error"></p>
        </div>

        {{-- Success Message --}}
        <div x-show="success" x-cloak class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
            <p class="text-sm text-emerald-800 dark:text-emerald-200" x-text="success"></p>
        </div>

        {{-- Footer Actions - Sticky at bottom --}}
        <div class="sticky bottom-0 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 pt-4 pb-2 sm:pb-0 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 -mx-0 sm:-mx-2 md:-mx-4 lg:-mx-6 px-0 sm:px-2 md:px-4 lg:px-6 mt-4 z-10">
            <button
                type="button"
                @click="$dispatch('close-preview-panel', panelName)"
                class="w-full sm:flex-1 px-4 py-3 sm:py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300
                       bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700
                       rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                Cancel
            </button>
            <button
                type="submit"
                :disabled="submitting"
                :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                class="w-full sm:flex-1 px-4 py-3 sm:py-2.5 text-sm font-medium text-white bg-indigo-600
                       hover:bg-indigo-700 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
                <svg x-show="submitting" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="submitting ? 'Saving...' : (mode === 'edit' ? 'Save Changes' : 'Create Task')"></span>
            </button>
        </div>
    </form>
</div>

