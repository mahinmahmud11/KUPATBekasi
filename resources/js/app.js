import './bootstrap';

const initializeProductGalleries = () => {
    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        const activeImage = gallery.querySelector('[data-gallery-active-image]');
        const previewImage = gallery.querySelector('[data-gallery-preview-image]');
        const previewOpen = gallery.querySelector('[data-gallery-preview-open]');
        const previewClose = gallery.querySelector('[data-gallery-preview-close]');
        const dialog = gallery.querySelector('[data-gallery-dialog]');
        const thumbnails = [...gallery.querySelectorAll('[data-gallery-thumbnail]')];

        if (!activeImage || !previewImage || !previewOpen || !previewClose || !dialog || thumbnails.length === 0) {
            return;
        }

        let activeIndex = 0;
        let previewOpener = null;

        const selectImage = (index) => {
            activeIndex = (index + thumbnails.length) % thumbnails.length;
            const thumbnail = thumbnails[activeIndex];
            const source = thumbnail.dataset.gallerySrc;
            const alt = thumbnail.dataset.galleryAlt;

            activeImage.src = source;
            activeImage.alt = alt;
            previewImage.src = source;
            previewImage.alt = alt;
            previewOpen.setAttribute('aria-label', `Perbesar gambar ${alt}`);

            thumbnails.forEach((item, itemIndex) => {
                const isActive = itemIndex === activeIndex;

                item.setAttribute('aria-current', isActive ? 'true' : 'false');
                item.classList.toggle('border-gray-900', isActive);
                item.classList.toggle('ring-2', isActive);
                item.classList.toggle('ring-gray-300', isActive);
                item.classList.toggle('border-transparent', !isActive);
            });
        };

        const closePreview = () => {
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
            previewOpener?.focus();
        };

        thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', () => selectImage(index));
        });

        gallery.querySelector('[data-gallery-previous]')?.addEventListener('click', () => selectImage(activeIndex - 1));
        gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => selectImage(activeIndex + 1));

        previewOpen.addEventListener('click', () => {
            previewOpener = previewOpen;
            dialog.classList.remove('hidden');
            dialog.classList.add('flex');
            previewClose.focus();
        });

        previewClose.addEventListener('click', closePreview);
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                closePreview();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !dialog.classList.contains('hidden')) {
                closePreview();
            }
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProductGalleries);
} else {
    initializeProductGalleries();
}
