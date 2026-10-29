/*
 * KzFs — native fullscreen + kunci orientasi landscape untuk HP/tablet.
 *
 *  - enter(el) HARUS dipanggil langsung di dalam handler tap/klik (user gesture),
 *    karena browser menolak requestFullscreen tanpa gesture.
 *  - Setelah masuk fullscreen, layar dikunci ke landscape lewat screen.orientation.lock
 *    (Android Chrome / tablet). Saat keluar fullscreen, kunci orientasi dilepas.
 *  - iPhone tidak mendukung fullscreen elemen; fallback ke fullscreen native <video>.
 *  - Kalau request ditolak, elemen menerima event 'kz-fs-failed' supaya halaman bisa fallback.
 */
(function () {
    if (window.KzFs) return;

    const style = document.createElement('style');
    style.id = 'kz-fs-style';
    style.textContent = `
        [data-kz-fs]:fullscreen, [data-kz-fs]:-webkit-full-screen {
            width: 100% !important; height: 100% !important; max-width: none !important;
            aspect-ratio: auto !important; margin: 0 !important; border: 0 !important;
            border-radius: 0 !important; box-shadow: none !important; background: #000 !important;
        }
        [data-kz-fs]:fullscreen > *, [data-kz-fs]:-webkit-full-screen > * { border-radius: 0 !important; }
        .kz-fs-btn { display: none; }
        @media (pointer: coarse) { .kz-fs-btn { display: inline-flex; } }
    `;
    document.head.appendChild(style);

    let locked = false;

    const isTouch = () => (window.matchMedia && matchMedia('(pointer: coarse)').matches) || navigator.maxTouchPoints > 0;
    const current = () => document.fullscreenElement || document.webkitFullscreenElement || null;
    const supportsElement = (el) => !!(el && (el.requestFullscreen || el.webkitRequestFullscreen));

    function lockLandscape() {
        try {
            if (screen.orientation && screen.orientation.lock) {
                return screen.orientation.lock('landscape')
                    .then(() => { locked = true; return true; })
                    .catch(() => false);
            }
        } catch (e) {}
        return Promise.resolve(false);
    }

    function enter(el, opts = {}) {
        if (!el) return false;
        if (current() === el) return true;
        try {
            let req = null;
            if (el.requestFullscreen) req = el.requestFullscreen({ navigationUI: 'hide' });
            else if (el.webkitRequestFullscreen) req = el.webkitRequestFullscreen();
            else {
                // iPhone Safari: hanya <video> yang bisa fullscreen (otomatis landscape oleh iOS)
                const v = opts.video || el.querySelector('video');
                if (v && v.webkitEnterFullscreen) { v.webkitEnterFullscreen(); return true; }
                return false;
            }
            Promise.resolve(req)
                .then(() => lockLandscape())
                .catch(() => el.dispatchEvent(new CustomEvent('kz-fs-failed')));
            return true;
        } catch (e) {
            return false;
        }
    }

    function exit() {
        if (!current()) return;
        try { (document.exitFullscreen || document.webkitExitFullscreen).call(document); } catch (e) {}
    }

    function syncButtons() {
        const fs = current();
        document.querySelectorAll('[data-kz-rotate]').forEach((b) => {
            const on = !!fs && b.closest('[data-kz-fs]') === fs;
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.setAttribute('aria-label', on ? 'Keluar dari layar penuh' : 'Layar penuh landscape');
        });
    }

    /* ===== Fullscreen Horizontal Terkunci Penuh =====
     * Saat masuk fullscreen, layar dikunci ke landscape (horizontal).
     * Sesuai preferensi pengguna, mode fullscreen TIDAK BOLEH keluar otomatis saat HP
     * diputar/miring ke vertikal. Mode horizontal wajib terus aktif sampai pengguna
     * secara manual mengetuk tombol keluar dari fullscreen.
     */
    const isPortraitScreen = () => (window.matchMedia && matchMedia('(orientation: portrait)').matches);

    function onChange() {
        if (!current() && locked) {
            try { screen.orientation.unlock(); } catch (e) {}
            locked = false;
        }
        syncButtons();
    }
    document.addEventListener('fullscreenchange', onChange);
    document.addEventListener('webkitfullscreenchange', onChange);

    // Tombol generik (halaman anime): <button data-kz-rotate> di dalam [data-kz-fs]
    document.addEventListener('click', (e) => {
        const btn = e.target.closest && e.target.closest('[data-kz-rotate]');
        if (!btn) return;
        e.preventDefault();
        const target = btn.closest('[data-kz-fs]');
        if (!target) return;
        if (current() === target) exit(); else enter(target);
    });

    const allowWebFs = () => !isTouch() || !isPortraitScreen();
    const armSensor = () => {};

    window.KzFs = { enter, exit, isTouch, current, supportsElement, allowWebFs, armSensor, isPortrait: isPortraitScreen, isActive: (el) => !!el && current() === el };
})();
