/**
 * Modern Checklist AJAX Handler
 * Handles all checklist interactions without page reloads
 */

export default function checklist(config = {}) {
    return {
        // State
        loading: false,
        error: null,
        success: null,
        renderer: null,
        dataUrl: config.dataUrl || null,

        init() {
            // Store reference for external access
            window.checklistHandler = this;

            // Intercept all checklist form submissions
            this.setupFormHandlers();
        },

        setupFormHandlers() {
            // Handle toggle buttons - use event delegation for dynamically added elements
            document.addEventListener('click', (e) => {
                const button = e.target.closest('[data-checklist-toggle]');
                if (button && !button.disabled) {
                    e.preventDefault();
                    this.handleToggle(e, button);
                }
            });

            // Handle note saves - use event delegation for dynamically added elements
            document.removeEventListener('click', this.handleNoteSaveDelegate);
            this.handleNoteSaveDelegate = (e) => {
                // Check if clicked element or its parent has the save button attribute
                const saveButton = e.target.closest('[data-checklist-note-save]');
                if (saveButton) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleNoteSave(e, saveButton);
                }
            };
            document.addEventListener('click', this.handleNoteSaveDelegate);

            // Handle photo uploads - use event delegation to prevent double binding
            // Only handle forms that don't have the photoUploader component (legacy support)
            // Remove any existing listeners first
            document.removeEventListener('submit', this.handlePhotoUploadDelegate);
            this.handlePhotoUploadDelegate = (e) => {
                // Skip if form is handled by photoUploader component (has @submit.prevent)
                const form = e.target;
                if (form && form.hasAttribute('x-data') && form.getAttribute('x-data').includes('photoUploader')) {
                    return; // Let photoUploader handle it
                }
                if (form.matches('[data-checklist-photo-form]')) {
                    this.handlePhotoUpload(e);
                }
                // Handle task-level photo uploads
                if (form.matches('[data-task-photo-form]')) {
                    this.handleTaskPhotoUpload(e, form);
                }
            };
            document.addEventListener('submit', this.handlePhotoUploadDelegate);
        },

        async handleTaskPhotoUpload(event, form) {
            event.preventDefault();
            event.stopPropagation();

            const url = form.getAttribute('action');
            if (!url) {
                console.error('Photo upload URL not found');
                return;
            }

            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn?.textContent;

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Uploading...';
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Photo uploaded successfully');
                    // Close the modal
                    form.closest('[x-data]')?.dispatchEvent(new CustomEvent('close-modal'));
                    // Reset form
                    form.reset();
                } else {
                    throw new Error(data.message || 'Failed to upload photo');
                }
            } catch (error) {
                console.error('Task photo upload error:', error);
                this.showError(error.message || 'Failed to upload photo');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
        },

        async handleToggle(event, button) {
            event.preventDefault();
            event.stopPropagation();

            if (!button) {
                button = event.currentTarget;
            }

            const url = button.dataset.toggleUrl;
            if (!url) {
                console.error('Toggle URL not found');
                return;
            }

            // Store the current state for potential rollback
            const currentChecked = button.dataset.checked === 'true';
            const newChecked = !currentChecked;

            // OPTIMISTIC UPDATE: Update UI immediately before API call
            button.dataset.checked = newChecked ? 'true' : 'false';
            this.updateToggleUI(button, newChecked);

            // Disable button during request to prevent double-clicks
            button.disabled = true;

            try {
                // Get CSRF token from meta tag (already set up in bootstrap.js)
                const data = await window.api.post(url, {});

                if (data.success) {
                    // Confirm the update with server response (in case of any discrepancy)
                    button.dataset.checked = data.checked ? 'true' : 'false';
                    this.updateToggleUI(button, data.checked);

                    // Update progress indicators
                    this.updateProgress();

                    // Refresh session data and re-render checklist
                    const sessionContainer = document.querySelector('[data-session-id]');
                    const sessionId = sessionContainer?.dataset.sessionId ||
                                     window.location.pathname.match(/\/sessions\/(\d+)/)?.[1];
                    if (sessionId) {
                        // Refresh and re-render checklist
                        // This will check room completion and unlock next room if needed
                        this.refreshAndRerender(sessionId);
                    }

                    // Show success feedback
                    this.showSuccess('Task updated');
                } else {
                    throw new Error(data.message || 'Failed to update task');
                }
            } catch (error) {
                console.error('Toggle error:', error);
                const errorMessage = error.response?.data?.message || error.message || 'An error occurred';
                this.showError(errorMessage);
                // ROLLBACK: Restore original state on error
                button.dataset.checked = currentChecked ? 'true' : 'false';
                this.updateToggleUI(button, currentChecked);
            } finally {
                button.disabled = false;
            }
        },

        async handleNoteSave(event, button = null) {
            event.preventDefault();
            event.stopPropagation();

            // Get button element - either passed as parameter or from event
            button = button || event.currentTarget || event.target.closest('[data-checklist-note-save]');

            if (!button) {
                console.error('Save button not found');
                return;
            }

            // Get URL from data attribute (use getAttribute as it's more reliable)
            const url = button.getAttribute('data-note-url') || (button.dataset && button.dataset.noteUrl);
            const noteInput = button.closest('[data-note-container]')?.querySelector('[data-note-input]');

            if (!url) {
                console.error('Note save URL not found on button. Button:', button, 'Attributes:', button.attributes);
                return;
            }

            if (!noteInput) {
                console.error('Note input not found');
                return;
            }

            const noteValue = noteInput.value || '';
            const originalText = button.textContent;
            const noteSaving = button.dataset.noteSaving === 'true';

            if (noteSaving) {
                return; // Prevent double submission
            }

            button.dataset.noteSaving = 'true';
            button.disabled = true;
            button.textContent = 'Saving...';

            try {
                const data = await window.api.post(url, {
                    note: noteValue
                });

                if (data.success) {
                    this.showSuccess('Note saved');
                    button.textContent = 'Saved!';
                    setTimeout(() => {
                        button.textContent = originalText;
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to save note');
                }
            } catch (error) {
                console.error('Note save error:', error);
                const errorMessage = error.response?.data?.message || error.message || 'Failed to save note';
                this.showError(errorMessage);
                button.textContent = originalText;
            } finally {
                button.dataset.noteSaving = 'false';
                button.disabled = false;
            }
        },

        async handlePhotoUpload(event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent double submission

            const form = event.currentTarget;

            // Prevent double submission - check if dataset exists
            if (form && form.dataset && form.dataset.uploading === 'true') {
                return;
            }
            if (form && form.dataset) {
                form.dataset.uploading = 'true';
            }

            const url = form.action;
            const formData = new FormData(form);
            const fileInput = form.querySelector('input[type="file"]');
            const submitButton = form.querySelector('button[type="submit"]');
            const progressContainer = form.querySelector('[data-upload-progress]');

            if (!fileInput || !fileInput.files.length) {
                if (form && form.dataset) {
                    form.dataset.uploading = 'false';
                }
                this.showError('Please select photos to upload');
                return;
            }

            // Show progress indicator
            if (progressContainer) {
                progressContainer.classList.remove('hidden');
                progressContainer.innerHTML = '<div class="text-sm text-gray-600">Uploading...</div>';
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Uploading...';
            }

            try {
                const data = await window.api.post(url, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                    onUploadProgress: (progressEvent) => {
                        if (progressContainer && progressEvent.total) {
                            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            progressContainer.innerHTML = `<div class="text-sm text-gray-600">Uploading... ${percentCompleted}%</div>`;
                        }
                    },
                });

                // Add new photos to gallery
                if (data.photos && Array.isArray(data.photos)) {
                    this.addPhotosToGallery(data.photos, form);
                }

                // Clear file input and preview
                fileInput.value = '';
                const previewContainer = form.querySelector('[data-photo-preview]');
                if (previewContainer) {
                    previewContainer.innerHTML = '';
                    previewContainer.classList.add('hidden');
                }

                // Update photo count
                this.updatePhotoCount(form);

                // Refresh session data to get updated photo counts
                const sessionContainer = document.querySelector('[data-session-id]');
                const sessionId = sessionContainer?.dataset.sessionId ||
                                 window.location.pathname.match(/\/sessions\/(\d+)/)?.[1];
                if (sessionId) {
                    this.refreshAndRerender(sessionId);
                }

                this.showSuccess(data.message || 'Photos uploaded successfully');
            } catch (error) {
                console.error('Photo upload error:', error);
                const errorMessage = error.response?.data?.message || error.message || 'Failed to upload photos';
                this.showError(errorMessage);
            } finally {
                if (form && form.dataset) {
                    form.dataset.uploading = 'false';
                }
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Upload Photos';
                }
                if (progressContainer) {
                    progressContainer.classList.add('hidden');
                }
            }
        },

        updateToggleUI(button, checked) {
            // Update button appearance
            if (checked) {
                button.classList.remove('bg-white', 'dark:bg-gray-700', 'border-gray-300', 'dark:border-gray-600');
                button.classList.add('bg-green-600', 'border-green-600', 'text-white');
                button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>';
                button.setAttribute('aria-label', 'Mark as incomplete');
            } else {
                button.classList.remove('bg-green-600', 'border-green-600', 'text-white');
                button.classList.add('bg-white', 'dark:bg-gray-700', 'border-gray-300', 'dark:border-gray-600');
                button.innerHTML = '';
                button.setAttribute('aria-label', 'Mark as complete');
            }

            // Update task text styling
            const taskItem = button.closest('[data-task-item]');
            if (taskItem) {
                const taskName = taskItem.querySelector('[data-task-name]');
                if (taskName) {
                    if (checked) {
                        taskName.classList.add('line-through', 'text-gray-500', 'dark:text-gray-400');
                        taskName.classList.remove('text-gray-800', 'dark:text-gray-200');
                    } else {
                        taskName.classList.remove('line-through', 'text-gray-500', 'dark:text-gray-400');
                        taskName.classList.add('text-gray-800', 'dark:text-gray-200');
                    }
                }
            }
        },

        updateProgress() {
            // Update progress indicators if they exist
            const progressElements = document.querySelectorAll('[data-progress-update]');
            progressElements.forEach(el => {
                // Trigger a custom event that can be handled by Alpine components
                el.dispatchEvent(new CustomEvent('progress-update'));
            });
        },

        addPhotosToGallery(photos, form) {
            const gallery = form.closest('[data-room-photos]')?.querySelector('[data-photo-gallery]');
            if (!gallery) return;

            photos.forEach(photo => {
                const photoElement = document.createElement('div');
                photoElement.className = 'relative group';
                photoElement.innerHTML = `
                    <button type="button" class="w-full" data-photo-view="${photo.id}">
                        <img src="${photo.url}" alt="Photo"
                             class="aspect-square w-full object-cover rounded-xl border transition hover:opacity-90" />
                        <span class="absolute bottom-1 right-1 text-[10px] px-1.5 py-0.5 rounded bg-black/60 text-white">
                            ${photo.captured_at}
                        </span>
                    </button>
                `;
                gallery.appendChild(photoElement);
            });
        },

        updatePhotoCount(form) {
            const countElement = form.closest('[data-room-photos]')?.querySelector('[data-photo-count]');
            if (!countElement) return;

            const gallery = form.closest('[data-room-photos]')?.querySelector('[data-photo-gallery]');
            const currentCount = gallery?.children.length || 0;
            const roomId = form.dataset.roomId;

            // Update count display
            countElement.textContent = `${currentCount}/8 photos`;

            // Update progress bar if exists
            const progressBar = form.closest('[data-room-photos]')?.querySelector('[data-photo-progress]');
            if (progressBar) {
                const progress = Math.min((currentCount / 8) * 100, 100);
                progressBar.style.width = `${progress}%`;
            }
        },

        showSuccess(message) {
            this.success = message;
            this.error = null;
            setTimeout(() => {
                this.success = null;
            }, 3000);
        },

        showError(message) {
            this.error = message;
            this.success = null;
            setTimeout(() => {
                this.error = null;
            }, 5000);
        },


        /**
         * Refresh session data from API
         * Call this after completing tasks to update progress, stage, etc.
         */
        async refreshSessionData(sessionId) {
            try {
                const response = await window.api.get(`/api/sessions/${sessionId}/data`);

                if (response.success && response.data) {
                    // Update progress indicators
                    this.updateProgressFromData(response.data);

                    // Dispatch event for components to update
                    document.dispatchEvent(new CustomEvent('session-data-updated', {
                        detail: response.data
                    }));

                    return response.data;
                }
            } catch (error) {
                console.error('Failed to refresh session data:', error);
                this.showError('Failed to refresh session data');
            }
        },

        /**
         * Refresh session data and re-render the entire checklist
         */
        async refreshAndRerender(sessionId) {
            try {
                // Use provided route URL or fallback to hardcoded path
                const url = this.dataUrl
                    ? this.dataUrl.replace('{session}', sessionId)
                    : `/api/sessions/${sessionId}/data`;
                const response = await window.api.get(url);

                if (response.success && response.data) {
                    // Trigger re-render if renderer exists
                    const rendererElement = document.querySelector('[x-data*="checklistRenderer"]');
                    if (rendererElement && rendererElement._x_dataStack) {
                        const renderer = rendererElement._x_dataStack[0];
                        if (renderer && typeof renderer.refresh === 'function') {
                            // Update session data and re-render
                            renderer.sessionData = response.data;
                            renderer.renderChecklist();
                        }
                    }

                    // Also dispatch event for other components
                    document.dispatchEvent(new CustomEvent('session-data-updated', {
                        detail: response.data
                    }));
                }
            } catch (error) {
                console.error('Failed to refresh and re-render:', error);
                // Don't show error - silent fail
            }
        },

        /**
         * Update progress indicators from API data
         */
        updateProgressFromData(data) {
            // Update stage indicator if exists
            const stageElement = document.querySelector('[data-current-stage]');
            if (stageElement) {
                stageElement.textContent = data.stage.replace('_', ' ').toUpperCase();
            }

            // Update progress bars
            const progressBars = document.querySelectorAll('[data-progress-update]');
            progressBars.forEach(bar => {
                const roomId = bar.dataset.roomId;
                if (roomId && data.photo_counts[roomId] !== undefined) {
                    const count = data.photo_counts[roomId];
                    const progress = Math.min((count / 8) * 100, 100);
                    bar.style.width = `${progress}%`;

                    const countElement = bar.closest('[data-room-photos]')?.querySelector('[data-photo-count]');
                    if (countElement) {
                        countElement.textContent = `${count}/8 photos`;
                    }
                }
            });

            // Update task counts if needed
            if (data.counts) {
                // Update property-level task counts
                ['pre_cleaning', 'during_cleaning', 'post_cleaning'].forEach(phase => {
                    const countEl = document.querySelector(`[data-${phase}-count]`);
                    if (countEl && data.counts[phase]) {
                        countEl.textContent = `${data.counts[phase].checked}/${data.counts[phase].total}`;
                    }
                });
            }
        },
    };
}
