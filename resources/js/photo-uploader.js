/**
 * Photo Uploader Component
 * Handles drag & drop, preview, and upload for room photos
 */

export default function photoUploader(roomId) {
    return {
        roomId: roomId,
        previews: [],
        hover: false,
        uploading: false,
        uploadProgress: 0,

        init() {
            // Initialize empty state
        },

        handleDrop(event) {
            event.preventDefault();
            this.hover = false;

            const files = Array.from(event.dataTransfer.files);
            this.addFiles(files);
        },

        handleFiles(event) {
            const files = Array.from(event.target.files || []);
            this.addFiles(files);
        },

        addFiles(files) {
            files.forEach(file => {
                if (!file.type.startsWith('image/')) {
                    return;
                }

                // Check file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    // Get image dimensions
                    const img = new Image();
                    img.onload = () => {
                        this.previews.push({
                            file: file,
                            url: e.target.result,
                            size: file.size,
                            name: file.name,
                            width: img.width,
                            height: img.height
                        });
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        },

        removePreview(index) {
            // Revoke object URL to free memory
            if (this.previews[index].url.startsWith('blob:')) {
                URL.revokeObjectURL(this.previews[index].url);
            }
            this.previews.splice(index, 1);
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        async handleSubmit(event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent event from bubbling to other handlers

            if (this.previews.length === 0) {
                return;
            }

            // Prevent double submission
            if (this.uploading) {
                return;
            }

            const form = event.currentTarget;
            const formData = new FormData();

            // Only add files from previews array (not from file input to avoid duplicates)
            this.previews.forEach(preview => {
                formData.append('photos[]', preview.file);
            });

            this.uploading = true;
            this.uploadProgress = 0;

            try {
                const response = await window.api.post(form.action, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                    onUploadProgress: (progressEvent) => {
                        if (progressEvent.total) {
                            this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        }
                    },
                });

                if (response.success || response.photos) {
                    // Clear previews
                    this.previews.forEach(preview => {
                        if (preview.url.startsWith('blob:')) {
                            URL.revokeObjectURL(preview.url);
                        }
                    });
                    this.previews = [];

                    // Clear file input
                    this.$refs.fileInput.value = '';

                    // Refresh session data to show new photos
                    const renderer = document.querySelector('[x-data*="checklistRenderer"]')?._x_dataStack?.[0];
                    if (renderer) {
                        await renderer.refresh();
                    }

                    // Show success message
                    const checklistHandler = window.checklistHandler;
                    if (checklistHandler) {
                        checklistHandler.showSuccess(response.message || 'Photos uploaded successfully');
                    }
                } else {
                    throw new Error(response.message || 'Upload failed');
                }
            } catch (error) {
                console.error('Upload error:', error);
                const errorMessage = error.response?.data?.message || error.message || 'Failed to upload photos';
                const checklistHandler = window.checklistHandler;
                if (checklistHandler) {
                    checklistHandler.showError(errorMessage);
                } else {
                    alert(errorMessage);
                }
            } finally {
                this.uploading = false;
                this.uploadProgress = 0;
            }
        },
    };
}
