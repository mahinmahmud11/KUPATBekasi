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

const initializeHomeSliders = () => {
    document.querySelectorAll('[data-home-slider]').forEach((slider) => {
        const slides = [...slider.querySelectorAll('[data-home-slide]')];
        const indicators = [...slider.querySelectorAll('[data-home-slider-indicator]')];

        if (slides.length < 2 || indicators.length !== slides.length) {
            return;
        }

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let activeIndex = 0;
        let autoplayTimer = null;
        let pointerInside = false;
        let focusInside = false;
        let isTransitioning = false;

        const updateIndicators = () => {
            indicators.forEach((indicator, indicatorIndex) => {
                const isActive = indicatorIndex === activeIndex;
                const dot = indicator.querySelector('[data-home-slider-dot]');

                indicator.setAttribute('aria-current', isActive ? 'true' : 'false');
                dot?.classList.toggle('bg-gray-900', isActive);
                dot?.classList.toggle('bg-white', !isActive);
            });
        };

        const showSlideImmediately = (targetIndex) => {
            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === targetIndex;

                slide.classList.toggle('invisible', !isActive);
                slide.classList.toggle('pointer-events-none', !isActive);
                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                slide.style.removeProperty('transition');
                slide.style.removeProperty('transform');
            });

            activeIndex = targetIndex;
            updateIndicators();
        };

        const transitionTo = (index, requestedDirection = null) => {
            const targetIndex = (index + slides.length) % slides.length;

            if (targetIndex === activeIndex || isTransitioning) {
                return;
            }

            if (reducedMotion.matches) {
                showSlideImmediately(targetIndex);

                return;
            }

            const currentSlide = slides[activeIndex];
            const targetSlide = slides[targetIndex];
            const direction = requestedDirection ?? (targetIndex > activeIndex ? 1 : -1);
            let hasFinished = false;
            const finishTransition = () => {
                if (hasFinished) {
                    return;
                }

                hasFinished = true;
                currentSlide.classList.add('invisible', 'pointer-events-none');
                currentSlide.setAttribute('aria-hidden', 'true');
                targetSlide.classList.remove('pointer-events-none');
                currentSlide.style.removeProperty('transition');
                currentSlide.style.removeProperty('transform');
                targetSlide.style.removeProperty('transition');
                targetSlide.style.removeProperty('transform');
                activeIndex = targetIndex;
                isTransitioning = false;
                updateIndicators();
            };

            isTransitioning = true;
            targetSlide.classList.remove('invisible');
            targetSlide.setAttribute('aria-hidden', 'false');
            currentSlide.style.transition = 'none';
            currentSlide.style.transform = 'translateX(0)';
            targetSlide.style.transition = 'none';
            targetSlide.style.transform = `translateX(${direction * 100}%)`;
            targetSlide.offsetWidth;

            currentSlide.style.transition = 'transform 600ms ease-in-out';
            targetSlide.style.transition = 'transform 600ms ease-in-out';
            currentSlide.style.transform = `translateX(${direction * -100}%)`;
            targetSlide.style.transform = 'translateX(0)';

            targetSlide.addEventListener('transitionend', finishTransition, { once: true });
            window.setTimeout(finishTransition, 700);
        };

        const stopAutoplay = () => {
            if (autoplayTimer !== null) {
                window.clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        };

        const startAutoplay = () => {
            stopAutoplay();

            if (reducedMotion.matches || pointerInside || focusInside) {
                return;
            }

            autoplayTimer = window.setInterval(() => transitionTo(activeIndex + 1, 1), 6000);
        };

        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                transitionTo(index);
                startAutoplay();
            });
        });

        slider.addEventListener('pointerenter', () => {
            pointerInside = true;
            stopAutoplay();
        });
        slider.addEventListener('pointerleave', () => {
            pointerInside = false;
            startAutoplay();
        });
        slider.addEventListener('focusin', () => {
            focusInside = true;
            stopAutoplay();
        });
        slider.addEventListener('focusout', (event) => {
            if (!slider.contains(event.relatedTarget)) {
                focusInside = false;
                startAutoplay();
            }
        });
        reducedMotion.addEventListener('change', startAutoplay);

        startAutoplay();
    });
};

const initializeFrontend = () => {
    initializeProductGalleries();
    initializeHomeSliders();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFrontend);
} else {
    initializeFrontend();
}
