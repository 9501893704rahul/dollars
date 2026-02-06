// resources/js/app.js

import './bootstrap'
import './property-panels'

import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
import PerfectScrollbar from 'perfect-scrollbar'
import Sortable from 'sortablejs'

window.Sortable = Sortable
window.PerfectScrollbar = PerfectScrollbar

// Simple UID helper
window.randomUID = function randomUID(prefix = 'id') {
    const rand = Math.random().toString(36).substring(2, 10)
    const time = Date.now().toString(36)
    return `${prefix}-${time}-${rand}`
}

// Existing local components
import roomsList from './room-list'
import roomAutocomplete from './room-autocomplete'
import taskList from './task-list'
import taskAutocomplete from './task-autocomplete'
import mediaDropzone from './media-dropzone'
import roomPicker from './room-picker'
import taskPicker from './task-picker'
import dropdown from './dropdown'

// New components
import roomsIndex from './rooms-index'
import roomTasksEditor from './room-tasks-editor'
import taskCreateForm from './task-create-form'
import roomCreateForm from './room-create-form'
import propertyTaskForm from './property-task-form'
import propertyRoomForm from './property-room-form'
import propertyPropertyTaskForm from './property-property-task-form'
import checklist from './checklist'
import checklistRenderer from './checklist-renderer'
import photoUploader from './photo-uploader'
import photoDeleteHandler from './photo-delete-handler'

import propertyAssignmentsPanel from './pages/properties/property-assignments-panel'

// ⛔️ DO NOT MODIFY — main app interaction (kept exactly as you sent)
document.addEventListener('alpine:init', () => {
    Alpine.data('mainState', () => {
        let lastScrollTop = 0
        const init = function () {
            window.addEventListener('scroll', () => {
                let st =
                    window.pageYOffset || document.documentElement.scrollTop
                if (st > lastScrollTop) {
                    // downscroll
                    this.scrollingDown = true
                    this.scrollingUp = false
                } else {
                    // upscroll
                    this.scrollingDown = false
                    this.scrollingUp = true
                    if (st == 0) {
                        //  reset
                        this.scrollingDown = false
                        this.scrollingUp = false
                    }
                }
                lastScrollTop = st <= 0 ? 0 : st // For Mobile or negative scrolling
            })
        }

        const getTheme = () => {
            if (window.localStorage.getItem('dark')) {
                return JSON.parse(window.localStorage.getItem('dark'))
            }
            return (
                !!window.matchMedia &&
                window.matchMedia('(prefers-color-scheme: dark)').matches
            )
        }
        const setTheme = (value) => {
            window.localStorage.setItem('dark', value)
        }
        return {
            init,
            isDarkMode: getTheme(),
            toggleTheme() {
                this.isDarkMode = !this.isDarkMode
                setTheme(this.isDarkMode)
            },
            isSidebarOpen: window.innerWidth > 1024,
            isSidebarHovered: false,
            handleSidebarHover(value) {
                if (window.innerWidth < 1024) {
                    return
                }
                this.isSidebarHovered = value
            },
            handleWindowResize() {
                if (window.innerWidth <= 1024) {
                    this.isSidebarOpen = false
                } else {
                    this.isSidebarOpen = true
                }
            },
            scrollingDown: false,
            scrollingUp: false,
        }
    })
})

// Second alpine:init for all other components
document.addEventListener('alpine:init', () => {
    // Existing components
    Alpine.data('mediaDropzone', mediaDropzone)
    Alpine.data('taskList', taskList)
    Alpine.data('taskAutocomplete', taskAutocomplete)

    Alpine.data('roomsList', roomsList)
    Alpine.data('roomAutocomplete', roomAutocomplete)
    Alpine.data('roomPicker', roomPicker)
    Alpine.data('taskPicker', taskPicker)
    Alpine.data('dropdown', dropdown)

    // New: rooms index (bulk assign tasks)
    Alpine.data('roomsIndex', roomsIndex)

    // New: edit room + tasks on same page
    Alpine.data('roomTasksEditor', roomTasksEditor)

    // New: task create form
    Alpine.data('taskCreateForm', taskCreateForm)

    // New: room create form
    Alpine.data('roomCreateForm', roomCreateForm)

    // New: property task form (create/edit)
    Alpine.data('propertyTaskForm', propertyTaskForm)

    // New: property room form (create/edit)
    Alpine.data('propertyRoomForm', propertyRoomForm)

    // New: property property task form (create/edit)
    Alpine.data('propertyPropertyTaskForm', propertyPropertyTaskForm)

    // Checklist AJAX handler
    Alpine.data('checklist', checklist)

    // Checklist renderer (dynamic rendering)
    Alpine.data('checklistRenderer', checklistRenderer)

    // Photo uploader component
    Alpine.data('photoUploader', photoUploader)

    // Photo delete handler component
    Alpine.data('photoDeleteHandler', photoDeleteHandler)

    Alpine.data('propertyAssignmentsPanel', propertyAssignmentsPanel)

    // Bulk task form (defined inline in blade, but register here for consistency)
    Alpine.data('bulkTaskForm', function(config) {
        return {
            tasks: [],
            defaultType: 'room',
            status: null,
            message: '',
            taskInput: null,

            init() {
                this.$refs.taskInput?.focus();
            },

            // Capitalize text to title case (e.g., "Open Windows For Airing")
            capitalizeText(text) {
                if (!text) return '';
                return text.toLowerCase()
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
            },

            addTaskFromInput() {
                const input = this.$refs.taskInput;
                if (!input) return;

                const value = input.value.trim();
                if (!value) return;

                // Capitalize the task name
                const capitalizedValue = this.capitalizeText(value);

                // Check for duplicates (case-insensitive)
                const exists = this.tasks.some(t => t.name.toLowerCase() === capitalizedValue.toLowerCase());
                if (exists) {
                    this.showMessage('error', `"${capitalizedValue}" is already in the list`);
                    return;
                }

                this.tasks.push({ name: capitalizedValue });
                input.value = '';
                this.status = null;
            },

            addSuggestedTask(taskName) {
                if (!taskName || !taskName.trim()) return;

                const value = taskName.trim();
                // Capitalize the task name
                const capitalizedValue = this.capitalizeText(value);

                // Check for duplicates (case-insensitive)
                const exists = this.tasks.some(t => t.name.toLowerCase() === capitalizedValue.toLowerCase());
                if (exists) {
                    this.showMessage('error', `"${capitalizedValue}" is already in the list`);
                    return;
                }

                this.tasks.push({ name: capitalizedValue });
                this.status = null;
            },

            handlePaste(event) {
                event.preventDefault();
                const pastedText = (event.clipboardData || window.clipboardData).getData('text');
                const lines = pastedText.split('\n').map(line => line.trim()).filter(line => line.length > 0);

                if (lines.length > 0) {
                    lines.forEach(line => {
                        // Capitalize each line
                        const capitalizedLine = this.capitalizeText(line);
                        const exists = this.tasks.some(t => t.name.toLowerCase() === capitalizedLine.toLowerCase());
                        if (!exists) {
                            this.tasks.push({ name: capitalizedLine });
                        }
                    });
                    this.$refs.taskInput.value = '';
                }
            },

            removeTask(index) {
                this.tasks.splice(index, 1);
            },

            clearAll() {
                if (confirm(`Remove all ${this.tasks.length} tasks from the list?`)) {
                    this.tasks = [];
                    this.status = null;
                }
            },

            showMessage(status, message) {
                this.status = status;
                this.message = message;
                if (status === 'saved') {
                    setTimeout(() => {
                        this.status = null;
                    }, 3000);
                }
            },

            async saveAll() {
                if (this.tasks.length === 0) return;

                this.status = 'saving';
                this.message = `Creating ${this.tasks.length} task(s)...`;

                const formData = new FormData();
                formData.append('_token', config.csrf);
                formData.append('tasks', JSON.stringify(this.tasks.map(t => t.name)));
                formData.append('default_type', this.defaultType);

                try {
                    const response = await fetch(config.storeUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.showMessage('saved', `Successfully created ${data.created || this.tasks.length} task(s)!`);
                        this.tasks = [];

                        // Reload page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showMessage('error', data.message || 'Failed to create tasks. Please try again.');
                    }
                } catch (error) {
                    console.error('Error saving bulk tasks:', error);
                    this.showMessage('error', 'An error occurred while saving tasks. Please try again.');
                }
            }
        };
    })
})

Alpine.plugin(collapse)
Alpine.start()
