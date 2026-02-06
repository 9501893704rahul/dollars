/**
 * Photo Delete Handler Component
 * Handles photo deletion with confirmation
 */

export default function photoDeleteHandler(photoId, sessionId, config = {}) {
    return {
        fullscreen: false,
        deleting: false,
        photoId: photoId,
        sessionId: sessionId,
        deleteUrl: config.deleteUrl || null,

        handleDeletePhoto() {
            if (confirm('Are you sure you want to delete this photo?')) {
                this.deleting = true;
                // Use provided route URL or fallback to hardcoded path
                const url = this.deleteUrl
                    ? this.deleteUrl.replace('{session}', this.sessionId).replace('{photo}', this.photoId)
                    : `/api/sessions/${this.sessionId}/photos/${this.photoId}`;
                window.api.delete(url)
                    .then(data => {
                        if (data.success) {
                            // Find and remove the photo element
                            const photoElement = document.querySelector(`[data-photo-id="${this.photoId}"]`);
                            if (photoElement) {
                                photoElement.remove();
                            }
                            // Refresh session data
                            const renderer = document.querySelector('[x-data*="checklistRenderer"]')?._x_dataStack?.[0];
                            if (renderer) {
                                renderer.refresh();
                            }
                        } else {
                            alert('Failed to delete photo');
                            this.deleting = false;
                        }
                    })
                    .catch(() => {
                        alert('Failed to delete photo');
                        this.deleting = false;
                    });
            }
        },
    };
}
