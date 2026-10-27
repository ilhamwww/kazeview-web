/**
 * KZSTRM Mouse Drag-to-Scroll for Horizontal Carousels
 * Allows smooth click & drag scrolling on desktop without triggering link clicks.
 */
(function () {
    if (window.KzDragScroll) return;

    const style = document.createElement('style');
    style.id = 'kz-drag-style';
    style.textContent = `
        @media (pointer: fine) {
            .kz-drag-scrollable {
                cursor: grab;
            }
            .kz-drag-scrollable.kz-dragging,
            .kz-drag-scrollable.kz-dragging * {
                cursor: grabbing !important;
                user-select: none !important;
                -webkit-user-select: none !important;
            }
            .kz-drag-scrollable img,
            .kz-drag-scrollable a {
                -webkit-user-drag: none !important;
                user-drag: none !important;
            }
        }
    `;
    document.head.appendChild(style);

    const isTouch = () => (window.matchMedia && matchMedia('(pointer: coarse)').matches) || navigator.maxTouchPoints > 0;

    function attachDragScroll(el) {
        if (!el || el._kzDragAttached) return;
        el._kzDragAttached = true;

        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;
        let hasDragged = false;
        let lastX = 0;
        let lastTime = 0;
        let velocity = 0;
        let momentumID = null;

        el.classList.add('kz-drag-scrollable');

        // Prevent native HTML5 image/link drag ghost
        el.addEventListener('dragstart', (e) => e.preventDefault());

        el.addEventListener('mousedown', (e) => {
            // Only primary mouse button (left click) and not on interactive form controls
            if (e.button !== 0 || isTouch()) return;
            if (e.target.closest('input, textarea, select')) return;

            cancelAnimationFrame(momentumID);
            isDown = true;
            hasDragged = false;
            startX = e.pageX;
            scrollLeft = el.scrollLeft;
            lastX = e.pageX;
            lastTime = Date.now();
            velocity = 0;

            el.classList.add('kz-dragging');
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDown) return;

            const delta = e.pageX - startX;

            if (!hasDragged && Math.abs(delta) > 5) {
                hasDragged = true;
                el.style.scrollSnapType = 'none';
            }

            if (hasDragged) {
                el.scrollLeft = scrollLeft - delta;

                const now = Date.now();
                const dt = now - lastTime;
                if (dt > 0) {
                    velocity = (e.pageX - lastX) / dt;
                    lastX = e.pageX;
                    lastTime = now;
                }
            }
        }, { passive: true });

        const endDrag = () => {
            if (!isDown) return;
            isDown = false;
            el.classList.remove('kz-dragging');

            if (hasDragged) {
                // Smooth momentum decay
                if (Math.abs(velocity) > 0.15) {
                    let v = velocity * 14;
                    const decay = () => {
                        if (Math.abs(v) > 0.5) {
                            el.scrollLeft -= v;
                            v *= 0.92;
                            momentumID = requestAnimationFrame(decay);
                        } else {
                            el.style.scrollSnapType = '';
                        }
                    };
                    decay();
                } else {
                    el.style.scrollSnapType = '';
                }

                // Suppress any click event immediately following a drag
                setTimeout(() => {
                    hasDragged = false;
                }, 80);
            }
        };

        window.addEventListener('mouseup', endDrag);

        // Intercept and cancel clicks on child elements if user was dragging
        el.addEventListener('click', (e) => {
            if (hasDragged) {
                e.preventDefault();
                e.stopPropagation();
                hasDragged = false;
            }
        }, true);
    }

    function init() {
        const targets = document.querySelectorAll('[data-drag-scroll], .overflow-x-auto');
        targets.forEach(el => {
            // Apply only to elements intended for horizontal scrolling
            if (el.scrollWidth > el.clientWidth || el.hasAttribute('data-drag-scroll')) {
                attachDragScroll(el);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('livewire:navigated', init);
    document.addEventListener('livewire:init', init);

    // Watch for dynamic DOM additions
    const observer = new MutationObserver(() => init());
    observer.observe(document.body || document.documentElement, { childList: true, subtree: true });

    window.KzDragScroll = { init, attach: attachDragScroll };
})();
