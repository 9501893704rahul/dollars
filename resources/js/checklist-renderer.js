/**
 * Checklist Renderer - Renders checklist dynamically from API data
 * Reduces Blade code by handling all rendering in JavaScript
 */

export default function checklistRenderer(config = {}) {
    return {
        sessionId: null,
        sessionData: null,
        loading: false,
        error: null,
        renderedContent: '',
        dataUrl: config.dataUrl || null,
        photoDeleteUrl: config.photoDeleteUrl || null,

        init() {
            // Get session ID from data attribute or URL
            const container = document.querySelector('[data-session-id]');
            this.sessionId = container?.dataset.sessionId ||
                            window.location.pathname.match(/\/sessions\/(\d+)/)?.[1];

            if (!this.sessionId) {
                console.error('Session ID not found');
                return;
            }

            // Get CSRF token
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            // Build data URL if not provided (fallback for backward compatibility)
            if (!this.dataUrl) {
                this.dataUrl = `/api/sessions/${this.sessionId}/data`;
            }

            // Load initial data
            this.loadSessionData();
        },

        async loadSessionData() {
            this.loading = true;
            this.error = null;

            try {
                const response = await window.api.get(this.dataUrl);

                if (response.success && response.data) {
                    this.sessionData = response.data;
                    this.renderChecklist();
                } else {
                    throw new Error('Failed to load session data');
                }
            } catch (error) {
                console.error('Error loading session data:', error);

                // Provide user-friendly error messages
                const status = error.response?.status;
                let errorMessage = 'Failed to load checklist. Please try again.';

                if (status === 401 || status === 403) {
                    errorMessage = 'You do not have permission to access this checklist. Please ensure you are logged in with the correct account.';
                } else if (status === 404) {
                    errorMessage = 'This session could not be found. It may have been deleted or you may not have access.';
                } else if (status === 500) {
                    errorMessage = 'A server error occurred. Please try again or contact support if the problem persists.';
                } else if (!navigator.onLine) {
                    errorMessage = 'You appear to be offline. Please check your internet connection and try again.';
                } else if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }

                this.error = errorMessage;
            } finally {
                this.loading = false;
            }
        },

        renderChecklist() {
            if (!this.sessionData) return;

            const stage = this.sessionData.stage;

            // Update stage indicator
            const stageElement = document.querySelector('[data-current-stage]');
            if (stageElement) {
                stageElement.textContent = stage.replace(/_/g, ' ').split(' ').map(w =>
                    w.charAt(0).toUpperCase() + w.slice(1)
                ).join(' ');
            }

            // Store rendered HTML in Alpine reactive property
            let renderedHtml = '';

            // Render based on stage
            switch (stage) {
                case 'pre_cleaning':
                    renderedHtml = this.renderPropertyTasks('pre_cleaning', 'Pre-Cleaning Tasks');
                    break;
                case 'rooms':
                    renderedHtml = this.renderRooms();
                    break;
                case 'during_cleaning':
                    renderedHtml = this.renderPropertyTasks('during_cleaning', 'During-Cleaning Tasks');
                    break;
                case 'post_cleaning':
                    renderedHtml = this.renderPropertyTasks('post_cleaning', 'Post-Cleaning Tasks');
                    break;
                case 'inventory':
                    renderedHtml = this.renderInventory();
                    break;
                case 'photos':
                    renderedHtml = this.renderPhotos();
                    break;
                case 'summary':
                    renderedHtml = this.renderSummary();
                    break;
                default:
                    renderedHtml = '<p class="text-gray-500">Unknown stage</p>';
            }

            // Store in Alpine reactive property
            this.renderedContent = renderedHtml;

            // Use setTimeout to ensure Alpine processes the update and DOM is ready
            setTimeout(() => {
                // Re-initialize event handlers after rendering
                this.setupEventHandlers();
            }, 100);
        },

        renderPropertyTasks(phase, title) {
            // Ensure tasks is an array
            let tasks = this.sessionData.property_tasks[phase] || [];
            if (!Array.isArray(tasks)) {
                tasks = Object.values(tasks);
            }
            const counts = this.sessionData.counts[phase] || { total: 0, checked: 0 };

            if (tasks.length === 0) {
                return `
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No ${title.toLowerCase()} defined.</p>
                    </div>
                `;
            }

            return `
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">${title}</h2>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${counts.checked}/${counts.total}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">completed</div>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-full rounded-full bg-blue-600 transition-all duration-500"
                                 style="width: ${counts.total > 0 ? (counts.checked / counts.total * 100) : 0}%"></div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        ${tasks.map(task => this.renderTaskItem(task, null)).join('')}
                    </div>
                </div>
            `;
        },

        renderRooms() {
            // Ensure rooms is an array
            let rooms = this.sessionData.rooms || [];
            if (!Array.isArray(rooms)) {
                rooms = Object.values(rooms);
            }

            // Note: Room locking has been removed to allow housekeepers to skip around rooms

            return `
                <div class="space-y-6">
                    ${rooms.map((room, index) => {
                        // Ensure room.tasks is an array
                        const roomTasksArray = Array.isArray(room.tasks) ? room.tasks : Object.values(room.tasks || {});
                        const roomTasks = roomTasksArray.filter(t => room.room_tasks.includes(t.id));
                        const checkedCount = roomTasks.filter(t => t.checklist_item?.checked).length;
                        const totalCount = roomTasks.length;
                        const isComplete = checkedCount === totalCount && totalCount > 0;

                        return `
                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6"
                                 data-room-id="${room.id}" data-room-index="${index}">
                                <div class="mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">${room.name}</h3>
                                        ${isComplete ? `
                                            <span class="text-xs px-2 py-1 rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                                                ✓ Complete
                                            </span>
                                        ` : ''}
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="h-full rounded-full bg-blue-600 transition-all duration-500"
                                             style="width: ${totalCount > 0 ? (checkedCount / totalCount * 100) : 0}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Room Tasks Progress</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">${checkedCount}/${totalCount}</span>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    ${roomTasks.map(task => this.renderTaskItem(task, room, false)).join('')}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        },

        renderInventory() {
            // Ensure rooms is an array
            let rooms = this.sessionData.rooms || [];
            if (!Array.isArray(rooms)) {
                rooms = Object.values(rooms);
            }

            // Note: Room locking has been removed to allow skipping between rooms
            // All rooms are now accessible

            return `
                <div class="space-y-6">
                    ${rooms.map((room, index) => {
                        // Ensure room.tasks is an array
                        const roomTasksArray = Array.isArray(room.tasks) ? room.tasks : Object.values(room.tasks || {});
                        const inventoryTasks = roomTasksArray.filter(t => room.inventory_tasks.includes(t.id));

                        if (inventoryTasks.length === 0) return '';

                        const checkedCount = inventoryTasks.filter(t => t.checklist_item?.checked).length;
                        const totalCount = inventoryTasks.length;
                        const isComplete = checkedCount === totalCount && totalCount > 0;

                        return `
                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6"
                                 data-room-id="${room.id}">
                                <div class="mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">${room.name} — Inventory</h3>
                                        ${isComplete ? `
                                            <span class="text-xs px-2 py-1 rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                                                ✓ Complete
                                            </span>
                                        ` : ''}
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="h-full rounded-full bg-purple-600 transition-all duration-500"
                                             style="width: ${totalCount > 0 ? (checkedCount / totalCount * 100) : 0}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Inventory Progress</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">${checkedCount}/${totalCount}</span>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    ${inventoryTasks.map(task => this.renderTaskItem(task, room, false)).join('')}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        },

        renderPhotos() {
            // Ensure rooms is an array
            let rooms = this.sessionData.rooms || [];
            if (!Array.isArray(rooms)) {
                rooms = Object.values(rooms);
            }
            const photoCounts = this.sessionData.photo_counts || {};
            const photosByRoom = this.sessionData.photos_by_room || {};

            return `
                <div class="space-y-6">
                    ${rooms.map(room => {
                        const photoCount = photoCounts[room.id] || 0;
                        const photos = photosByRoom[room.id] || [];

                        return `
                            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6" data-room-photos data-room-id="${room.id}">
                                <div class="mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">${room.name} — Photos</h3>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400" data-photo-count>${photoCount}/8 photos</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="h-full rounded-full bg-green-600 transition-all duration-500"
                                             style="width: ${Math.min((photoCount / 8) * 100, 100)}%"></div>
                                    </div>
                                </div>

                                <div x-data="photoUploader(${room.id})" class="mb-6">
                                    <form method="post" enctype="multipart/form-data"
                                          action="/sessions/${this.sessionId}/rooms/${room.id}/photos"
                                          data-checklist-photo-form
                                          data-room-id="${room.id}"
                                          @submit.prevent.stop="handleSubmit($event)">

                                        <div class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-4 sm:p-6 mb-4
                                                   border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50
                                                   hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors cursor-pointer"
                                             @dragover.prevent="hover = true"
                                             @dragleave.prevent="hover = false"
                                             @drop.prevent="handleDrop($event)"
                                             @click="$refs.fileInput.click()"
                                             :class="hover ? 'border-blue-400 bg-blue-50/50 dark:bg-blue-900/10' : ''">
                                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-gray-400 dark:text-gray-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 text-center px-2">
                                                <span class="text-blue-600 dark:text-blue-400 font-medium">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-500 mt-1">PNG, JPG, JPEG up to 5MB</p>

                                            <input x-ref="fileInput"
                                                   type="file"
                                                   name="photos[]"
                                                   multiple
                                                   accept="image/*"
                                                   capture="environment"
                                                   class="hidden"
                                                   @change="handleFiles($event)" />
                                        </div>

                                        <div x-show="previews.length > 0" x-cloak class="mb-4">
                                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 sm:gap-3">
                                                <template x-for="(preview, index) in previews" :key="index">
                                                    <div class="relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
                                                         x-data="{ fullscreen: false }">
                                                        <button type="button" @click="fullscreen = true" class="w-full">
                                                            <img :src="preview.url"
                                                                 class="w-full aspect-square object-cover"
                                                                 :alt="'Preview ' + (index + 1)"
                                                                 :width="preview.width || 200"
                                                                 :height="preview.height || 200"
                                                                 loading="lazy" />
                                                        </button>
                                                        <div x-show="fullscreen"
                                                             x-cloak
                                                             @click.self="fullscreen = false"
                                                             @keydown.escape.window="fullscreen = false"
                                                             class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center p-2 sm:p-4">
                                                            <img :src="preview.url"
                                                                 class="max-h-full max-w-full object-contain rounded-lg"
                                                                 :alt="'Preview ' + (index + 1)"
                                                                 :width="preview.width || 1920"
                                                                 :height="preview.height || 1080" />
                                                            <button type="button"
                                                                    @click="fullscreen = false"
                                                                    class="absolute top-2 right-2 sm:top-4 sm:right-4 text-white text-2xl sm:text-3xl hover:text-gray-300 transition-colors bg-black/50 rounded-full w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center">
                                                                ×
                                                            </button>
                                                        </div>
                                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                            <button type="button"
                                                                    @click.stop="removePreview(index)"
                                                                    class="px-2 sm:px-3 py-1 sm:py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-xs sm:text-sm font-medium">
                                                                Remove
                                                            </button>
                                                        </div>
                                                        <div class="absolute top-1 right-1 sm:top-2 sm:right-2">
                                                            <span class="text-[10px] sm:text-xs px-1.5 sm:px-2 py-0.5 sm:py-1 rounded bg-black/60 text-white" x-text="formatFileSize(preview.size)"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                                            <button type="submit"
                                                    :disabled="previews.length === 0 || uploading"
                                                    class="w-full sm:flex-1 px-4 py-2.5 sm:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium text-sm sm:text-base">
                                                <span x-show="!uploading">Upload <span x-text="previews.length"></span> Photo<span x-show="previews.length !== 1">s</span></span>
                                                <span x-show="uploading" class="flex items-center gap-2">
                                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Uploading...
                                                </span>
                                            </button>
                                        </div>

                                        <div x-show="uploading && uploadProgress > 0" x-cloak class="mt-3">
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                     :style="'width: ' + uploadProgress + '%'"></div>
                                            </div>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 text-center" x-text="uploadProgress + '%'"></p>
                                        </div>
                                    </form>
                                </div>

                                ${photos.length > 0 ? `
                                    <div data-photo-gallery class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 sm:gap-3">
                                        ${photos.map(photo => {
                                            const photoUrl = photo.url || '';
                                            const timeStr = photo.captured_at ? new Date(photo.captured_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '';
                                            const deleteUrlConfig = this.photoDeleteUrl ? `, { deleteUrl: '${this.photoDeleteUrl}' }` : '';
                                            return `
                                                <div class="relative group"
                                                     x-data="photoDeleteHandler(${photo.id}, '${this.sessionId}'${deleteUrlConfig})"
                                                     data-photo-id="${photo.id}">
                                                    <button type="button" @click="fullscreen = true" class="w-full">
                                                        <img src="${photoUrl}"
                                                             alt="Photo"
                                                             width="300"
                                                             height="300"
                                                             class="aspect-square w-full object-cover rounded-xl border transition hover:opacity-90"
                                                             loading="lazy" />
                                                        ${timeStr ? `
                                                            <span class="absolute bottom-1 right-1 text-[10px] sm:text-xs px-1.5 sm:px-2 py-0.5 sm:py-1 rounded bg-black/60 text-white">
                                                                ${timeStr}
                                                            </span>
                                                        ` : ''}
                                                    </button>
                                                    <button type="button"
                                                            @click.stop="handleDeletePhoto()"
                                                            :disabled="deleting"
                                                            class="absolute top-1 right-1 sm:top-2 sm:right-2 p-1 sm:p-1.5 bg-red-600 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-700 disabled:opacity-50"
                                                            title="Delete photo">
                                                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                    <div x-show="fullscreen"
                                                         x-cloak
                                                         @click.self="fullscreen = false"
                                                         @keydown.escape.window="fullscreen = false"
                                                         class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center p-2 sm:p-4">
                                                        <img src="${photoUrl}"
                                                             class="max-h-full max-w-full object-contain rounded-lg"
                                                             alt="Fullscreen photo"
                                                             width="1920"
                                                             height="1080" />
                                                        <button type="button"
                                                                @click="fullscreen = false"
                                                                class="absolute top-2 right-2 sm:top-4 sm:right-4 text-white text-2xl sm:text-3xl hover:text-gray-300 transition-colors bg-black/50 rounded-full w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center">
                                                            ×
                                                        </button>
                                                    </div>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                ` : `
                                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">No photos uploaded yet.</p>
                                `}
                            </div>
                        `;
                    }).join('')}

                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <form method="post" action="/sessions/${this.sessionId}/complete" class="text-center">
                            <input type="hidden" name="_token" value="${this.csrfToken}" />
                            <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                                Submit Checklist
                            </button>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                Requires ≥8 photos per room. Timestamp overlay is automatic on upload.
                            </p>
                        </form>
                    </div>
                </div>
            `;
        },

        renderSummary() {
            // Summary view - can be implemented similarly
            return '<p class="text-gray-500">Summary view - to be implemented</p>';
        },

        renderTaskItem(task, room, disabled = false) {
            const isPropertyTask = room === null;
            const toggleUrl = isPropertyTask
                ? `/sessions/${this.sessionId}/property-tasks/${task.id}/toggle`
                : `/sessions/${this.sessionId}/rooms/${room.id}/tasks/${task.id}/toggle`;
            const noteUrl = isPropertyTask
                ? `/sessions/${this.sessionId}/property-tasks/${task.id}/note`
                : `/sessions/${this.sessionId}/rooms/${room.id}/tasks/${task.id}/note`;
            const photoUrl = isPropertyTask
                ? `/sessions/${this.sessionId}/property-tasks/${task.id}/photo`
                : `/sessions/${this.sessionId}/rooms/${room.id}/tasks/${task.id}/photo`;

            const checked = task.checklist_item?.checked || false;
            const existingNote = task.checklist_item?.note || '';
            const hasInstructions = task.instructions && task.instructions.trim().length > 0;
            const hasMedia = task.media && task.media.length > 0;
            const showDetails = hasInstructions || hasMedia;
            const isViewOnly = this.sessionData.is_view_only || false;
            const taskDisabled = disabled || isViewOnly;

            return `
                <div data-task-item data-task-id="${task.id}"
                     class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 ${checked ? 'opacity-90' : ''}"
                     x-data="{
                         detailsOpen: ${!checked && showDetails ? 'true' : 'false'},
                         galleryOpen: false,
                         gallerySrc: null,
                         noteValue: '${(task.checklist_item?.note || '').replace(/'/g, "\\'")}',
                         noteSaving: false
                     }">
                    <div class="p-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 pt-0.5">
                                <button type="button"
                                        data-checklist-toggle
                                        data-toggle-url="${toggleUrl}"
                                        data-checked="${checked}"
                                        ${taskDisabled ? 'disabled' : ''}
                                        class="relative w-6 h-6 rounded-md border-2 flex items-center justify-center transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${taskDisabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:scale-110'} ${checked ? 'bg-green-600 border-green-600 text-white' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600'}"
                                        aria-label="${checked ? 'Mark as incomplete' : 'Mark as complete'}">
                                    ${checked ? `
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    ` : ''}
                                </button>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col gap-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 data-task-name class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1 transition-all ${checked ? 'line-through text-gray-500 dark:text-gray-400' : ''}">
                                            ${task.name}
                                        </h3>

                                        ${showDetails ? `
                                            <div class="flex items-center gap-2 mt-1">
                                                <button type="button" @click="detailsOpen = !detailsOpen"
                                                        class="inline-flex items-center gap-1.5 text-sm text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 transition-colors font-bold uppercase">
                                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': detailsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                    <span x-text="detailsOpen ? 'HIDE NOTES' : 'READ IMPORTANT NOTES'"></span>
                                                </button>
                                                ${hasMedia ? `
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        • ${task.media.length} ${task.media.length === 1 ? 'media' : 'media'}
                                                    </span>
                                                ` : ''}
                                            </div>
                                        ` : ''}

                                        ${hasInstructions && !checked ? `
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                                                ${this.escapeHtml(task.instructions).substring(0, 120)}${task.instructions.length > 120 ? '...' : ''}
                                            </p>
                                        ` : ''}
                                    </div>

                                    <!-- Action Icons: Notes and Photo -->
                                    <div class="flex items-center gap-2 ml-auto">
                                        <!-- Notes Icon & Popup -->
                                        <div x-data="{ noteModalOpen: false }" class="relative">
                                            <button type="button"
                                                    @click="noteModalOpen = true"
                                                    ${taskDisabled ? 'disabled' : ''}
                                                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ${taskDisabled ? 'opacity-50 cursor-not-allowed' : ''} ${existingNote ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400'}"
                                                    title="${existingNote ? 'View/Edit Note' : 'Add Note'}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            
                                            <!-- Note Modal -->
                                            <div x-show="noteModalOpen" x-cloak
                                                 @click.self="noteModalOpen = false"
                                                 @keydown.escape.window="noteModalOpen = false"
                                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                                                <div @click.stop class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                                                    <div class="flex items-center justify-between mb-4">
                                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Task Note</h3>
                                                        <button type="button" @click="noteModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div data-note-container>
                                                        <textarea data-note-input
                                                                  x-model="noteValue"
                                                                  placeholder="Enter your note here..."
                                                                  rows="4"
                                                                  class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
                                                        <div class="flex justify-end gap-2 mt-4">
                                                            <button type="button" @click="noteModalOpen = false"
                                                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                                Cancel
                                                            </button>
                                                            <button type="button"
                                                                    data-checklist-note-save="true"
                                                                    data-note-url="${noteUrl || ''}"
                                                                    data-note-saving="false"
                                                                    @click="noteModalOpen = false"
                                                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                                                Save Note
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Photo Upload Icon -->
                                        <div x-data="{ photoModalOpen: false, photoNote: '', uploading: false, photoPreview: null }" class="relative">
                                            <button type="button"
                                                    @click="photoModalOpen = true"
                                                    ${taskDisabled ? 'disabled' : ''}
                                                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ${taskDisabled ? 'opacity-50 cursor-not-allowed' : ''} text-gray-500 dark:text-gray-400"
                                                    title="Upload Photo">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                            </button>
                                            
                                            <!-- Photo Upload Modal -->
                                            <div x-show="photoModalOpen" x-cloak
                                                 @click.self="photoModalOpen = false"
                                                 @keydown.escape.window="photoModalOpen = false"
                                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                                                <div @click.stop class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-6">
                                                    <div class="flex items-center justify-between mb-4">
                                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upload Photo</h3>
                                                        <button type="button" @click="photoModalOpen = false; photoPreview = null; photoNote = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    
                                                    <form data-task-photo-form action="${photoUrl || ''}" method="POST" enctype="multipart/form-data">
                                                        <!-- Photo Preview -->
                                                        <div x-show="photoPreview" class="mb-4">
                                                            <img :src="photoPreview" class="w-full h-48 object-cover rounded-lg" alt="Preview" />
                                                        </div>
                                                        
                                                        <!-- Camera Input (only allows camera capture) -->
                                                        <div x-show="!photoPreview" class="mb-4">
                                                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                </svg>
                                                                <span class="text-sm text-gray-500 dark:text-gray-400">Take a photo</span>
                                                                <input type="file" name="photo" accept="image/*" capture="environment" class="hidden"
                                                                       @change="if ($event.target.files[0]) { photoPreview = URL.createObjectURL($event.target.files[0]); }" />
                                                            </label>
                                                        </div>
                                                        
                                                        <!-- Note (required when uploading photo) -->
                                                        <div class="mb-4">
                                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note (required)</label>
                                                            <textarea name="note" x-model="photoNote" placeholder="Describe what's in this photo..." rows="3" required
                                                                      class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
                                                        </div>
                                                        
                                                        <div class="flex justify-end gap-2">
                                                            <button type="button" @click="photoModalOpen = false; photoPreview = null; photoNote = ''"
                                                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                                Cancel
                                                            </button>
                                                            <button type="submit" :disabled="!photoPreview || !photoNote.trim() || uploading"
                                                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                                <span x-show="!uploading">Upload Photo</span>
                                                                <span x-show="uploading">Uploading...</span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                ${showDetails ? `
                                    <div x-show="detailsOpen" x-collapse x-cloak class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        ${hasInstructions ? `
                                            <div class="mb-4">
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Instructions:</h4>
                                                <div class="prose dark:prose-invert prose-sm max-w-none bg-gray-50 dark:bg-gray-900/40 rounded-lg p-4">
                                                    ${this.formatInstructions(task.instructions)}
                                                </div>
                                            </div>
                                        ` : ''}

                                        ${hasMedia ? `
                                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                                ${task.media.map(media => `
                                                    <div class="relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 group">
                                                        ${media.type === 'image' ? `
                                                            <button type="button" @click="galleryOpen = true; gallerySrc = '${media.url}'" class="block w-full">
                                                                <img src="${media.thumbnail || media.url}" alt="${media.caption || 'Task media'}"
                                                                     class="w-full h-32 object-cover transition-transform group-hover:scale-105" loading="lazy" />
                                                            </button>
                                                        ` : `
                                                            <video src="${media.url}" class="w-full h-32 object-cover" controls muted></video>
                                                        `}
                                                        ${media.caption ? `
                                                            <span class="absolute bottom-1 left-1 text-xs px-2 py-1 rounded bg-black/60 text-white">
                                                                ${this.escapeHtml(media.caption.substring(0, 20))}
                                                            </span>
                                                        ` : ''}
                                                    </div>
                                                `).join('')}
                                            </div>
                                        ` : ''}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>

                    <div x-show="galleryOpen" x-cloak @click.self="galleryOpen = false" @keydown.escape.window="galleryOpen = false"
                         class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4">
                        <img :src="gallerySrc" class="max-h-[90vh] max-w-[90vw] rounded-lg shadow-2xl" alt="Gallery view" />
                        <button type="button" @click="galleryOpen = false" class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300 transition-colors">×</button>
                    </div>
                </div>
            `;
        },

        formatInstructions(instructions) {
            if (!instructions) return '';
            // Convert newlines to <br> and escape HTML
            return this.escapeHtml(instructions).replace(/\n/g, '<br>');
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        setupEventHandlers() {
            // Event handlers are set up by the checklist.js module
            // This is called after rendering to ensure new elements are handled
            if (window.checklistHandler) {
                window.checklistHandler.setupFormHandlers();
            }
        },

        // Public method to refresh data and re-render
        async refresh() {
            await this.loadSessionData();
        },

        // Check if a room is complete and unlock next room
        checkRoomCompletion() {
            if (!this.sessionData) return;

            // Re-calculate completion status
            let rooms = this.sessionData.rooms || [];
            if (!Array.isArray(rooms)) {
                rooms = Object.values(rooms);
            }
            rooms.forEach((room, index) => {
                // Ensure room.tasks is an array
                const roomTasksArray = Array.isArray(room.tasks) ? room.tasks : Object.values(room.tasks || {});
                const roomTasks = roomTasksArray.filter(t => room.room_tasks.includes(t.id));
                const checkedCount = roomTasks.filter(t => t.checklist_item?.checked).length;
                const totalCount = roomTasks.length;
                const isComplete = checkedCount === totalCount && totalCount > 0;

                // If room just completed, refresh to unlock next room
                if (isComplete) {
                    // Small delay to ensure database is updated
                    setTimeout(() => {
                        this.refresh();
                    }, 500);
                }
            });
        },
    };
}
