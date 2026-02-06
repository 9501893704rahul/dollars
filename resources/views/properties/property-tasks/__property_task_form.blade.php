@props([
    'property',
    'task' => null, // null for create, Task model for edit
    'pivot' => null, // Pivot data for edit mode
    'suggestUrl',
    'mode' => 'create', // 'create' or 'edit'
    'defaultPhase' => 'pre_cleaning', // Default phase for create mode
])

@php
    $isEdit = $mode === 'edit' && $task;
    $storeUrl = $isEdit
        ? route('properties.property-tasks.update', [$property, $task])
        : route('properties.property-tasks.store', $property);
    $panelName = $isEdit
        ? "edit-property-task-{$property->id}-{$task->id}"
        : "add-property-task-{$property->id}";
@endphp

<div class="p-0 sm:p-2 md:p-4 lg:p-6 space-y-4 sm:space-y-6 max-w-full"
     x-data="propertyPropertyTaskForm({
        suggestUrl: @js($suggestUrl),
        storeUrl: @js($storeUrl),
        csrf: @js(csrf_token()),
        propertyId: @js($property->id),
        taskId: @js($task?->id),
        mode: @js($mode),
        panelName: @js($panelName),
        initialData: @js($isEdit ? [
            'name' => $task->name ?? '',
            'phase' => $task->phase ?? 'pre_cleaning',
            'instructions' => $pivot->instructions ?? '',
            'visible_to_owner' => (bool) ($pivot->visible_to_owner ?? true),
            'visible_to_housekeeper' => (bool) ($pivot->visible_to_housekeeper ?? true),
        ] : [
            'name' => '',
            'phase' => $defaultPhase,
            'instructions' => '',
            'visible_to_owner' => true,
            'visible_to_housekeeper' => true,
        ])
    })"
    @if(!$isEdit)
    x-on:set-property-task-phase.window="
        const phaseMap = {
            'pre': 'pre_cleaning',
            'during': 'during_cleaning',
            'post': 'post_cleaning'
        };
        if (formData && phaseMap[$event.detail]) {
            formData.phase = phaseMap[$event.detail];
        }
    "
    x-init="
        // Try to get activeTab from parent on init
        setTimeout(() => {
            let el = $el;
            while (el && el !== document.body) {
                if (el.__x && el.__x.$data && el.__x.$data.activeTab) {
                    const phaseMap = {
                        'pre': 'pre_cleaning',
                        'during': 'during_cleaning',
                        'post': 'post_cleaning'
                    };
                    if (formData && phaseMap[el.__x.$data.activeTab]) {
                        formData.phase = phaseMap[el.__x.$data.activeTab];
                    }
                    break;
                }
                el = el.parentElement;
            }
        }, 100);
    "
    @endif>
    <form @submit.prevent="submitForm" class="space-y-6">
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Task Name with Autocomplete --}}
        <div x-data="taskAutocomplete({ suggestUrl: @js($suggestUrl) })" @if($isEdit) x-init="q = @js($task->name)" @endif>
            <label for="property-task-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Task Name <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <input
                    x-model="q"
                    x-ref="nameInput"
                    name="name"
                    id="property-task-name"
                    type="text"
                    required
                    autocomplete="off"
                    placeholder="e.g., Check inventory, Final inspection"
                    class="w-full max-w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                           transition-all duration-200 px-3 sm:px-4 py-2.5 text-sm"
                    @input="onInput"
                    @focus="onFocus"
                    @keydown="keyDown"
                    aria-autocomplete="list"
                    aria-expanded="open"
                    aria-controls="property-task-suggest"
                />

                {{-- Autocomplete Dropdown --}}
                <div
                    x-cloak
                    x-show="open"
                    id="property-task-suggest"
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

        {{-- Phase Selection --}}
        <div>
            <label for="property-task-phase" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                When to Perform <span class="text-rose-500">*</span>
            </label>
            <select
                name="phase"
                id="property-task-phase"
                x-model="formData.phase"
                required
                class="w-full max-w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100
                       focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                       transition-all duration-200 px-3 sm:px-4 py-2.5 text-sm"
            >
                <option value="pre_cleaning">Before Cleaning Starts</option>
                <option value="during_cleaning">During Cleaning</option>
                <option value="post_cleaning">After Cleaning is Done</option>
            </select>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">When should this task be performed during the cleaning session?</p>
        </div>

        {{-- Instructions --}}
        <div>
            <label for="property-task-instructions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Instructions <span class="text-xs text-gray-500">(optional)</span>
            </label>
            <textarea
                name="instructions"
                id="property-task-instructions"
                rows="4"
                x-model="formData.instructions"
                placeholder="Specific steps or notes for this task..."
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

