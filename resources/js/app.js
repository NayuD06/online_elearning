import Alpine from 'alpinejs';

/**
 * Preview images picked in a file input before they are uploaded.
 * Usage: <div x-data="imagePreview()"> <input type="file" @change="pick($event)"> ...
 */
Alpine.data('imagePreview', () => ({
    previews: [],
    pick(event) {
        this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
        this.previews = Array.from(event.target.files).map((file) => ({
            name: file.name,
            url: URL.createObjectURL(file),
        }));
    },
}));

window.Alpine = Alpine;
Alpine.start();

if (document.querySelector('[data-editor]')) {
    import('./editor').then(({ initEditors }) => initEditors());
}

if (document.querySelector('[data-chart]')) {
    import('./charts').then(({ initCharts }) => initCharts());
}
