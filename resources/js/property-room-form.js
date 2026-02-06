// resources/js/property-room-form.js

export default function propertyRoomForm({ suggestUrl, storeUrl, csrf, propertyId, roomId, mode, panelName, initialData }) {
    return {
        suggestUrl,
        storeUrl,
        csrf,
        propertyId,
        roomId,
        mode,
        panelName,
        submitting: false,
        error: null,
        success: null,
        showDetachModal: false,
        formData: {
            name: initialData.name || '',
            is_default: initialData.is_default !== undefined ? initialData.is_default : false,
        },

        init() {
            // Initialize form data from initialData
            this.formData = {
                name: initialData.name || '',
                is_default: initialData.is_default !== undefined ? initialData.is_default : false,
            };
        },

        async submitForm(event) {
            this.error = null;
            this.success = null;
            this.submitting = true;

            try {
                const formData = new FormData();

                // Get name from the autocomplete input
                const nameInput = event.target.querySelector('#room-name');
                if (nameInput && nameInput.value) {
                    formData.append('name', nameInput.value.trim());
                } else {
                    throw new Error('Room name is required');
                }

                // Add is_default
                formData.append('is_default', this.formData.is_default ? '1' : '0');

                // Add CSRF token
                formData.append('_token', this.csrf);

                // For edit mode, add method override
                if (this.mode === 'edit') {
                    formData.append('_method', 'PUT');
                }

                const response = await fetch(this.storeUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    // If response is not JSON, try to get text
                    const text = await response.text();
                    throw new Error(text || `Failed to ${this.mode === 'edit' ? 'update' : 'create'} room`);
                }

                if (!response.ok) {
                    // Handle validation errors
                    if (data.errors) {
                        const firstError = Object.values(data.errors)[0][0] || 'Validation failed';
                        throw new Error(firstError);
                    }
                    throw new Error(data.message || `Failed to ${this.mode === 'edit' ? 'update' : 'create'} room`);
                }

                // Success - show message and reload page after a short delay
                this.success = data.message || (this.mode === 'edit' ? 'Room updated successfully!' : 'Room created successfully!');

                // Reset form only for create mode
                if (this.mode === 'create') {
                    event.target.reset();
                    this.formData = {
                        name: '',
                        is_default: false,
                    };
                }

                // Close panel and reload after 1 second
                setTimeout(() => {
                    this.$dispatch('close-preview-panel', this.panelName);
                    window.location.reload();
                }, 1000);

            } catch (err) {
                this.error = err.message || `An error occurred while ${this.mode === 'edit' ? 'updating' : 'creating'} the room`;
                console.error('Room form error:', err);
            } finally {
                this.submitting = false;
            }
        },
    };
}

