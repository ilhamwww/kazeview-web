/**
 * KZ Player Core — satu sumber konfigurasi pemutar untuk semua halaman
 * (film, TV series, Indonesia, anime).
 *
 * Pemakaian:
 *   const art = new Artplayer(KzPlayerCore.config({
 *       container, url, poster, subtitles: [{url,label}], nativeFs: true,
 *       extraSettings: [...], extraControls: [...],
 *   }));
 *   KzPlayerCore.bindAutoFullscreen(art, shell);
 *
 * Tujuannya supaya tema, tombol, setelan, dan perilaku layar penuh tidak
 * berbeda antar halaman saat ada fitur baru.
 */
(function () {
    if (window.KzPlayerCore) return;

    const ICONS = {
        fs: '<svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="14" height="10" rx="2"/><path d="M20 4v5h-5"/><path d="M20 9a8 8 0 0 0-8-6"/></svg>',
        back10: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>',
        fwd10: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>',
        speed: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>',
        pip: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><rect x="11" y="9" width="9" height="6" rx="1" stroke="currentColor" fill="none"/></svg>',
        flip: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        ratio: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="9" x2="22" y2="9"/></svg>',
        captions: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M10 9H8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2"/><path d="M18 9h-2a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2"/></svg>',
    };

    const isTouch = () => !!(window.KzFs && window.KzFs.isTouch());

    /** Kecepatan putar, PiP, flip, dan aspek rasio — sama di semua halaman. */
    function baseSettings() {
        return [
            {
                html: 'Playback speed',
                name: 'playback-speed',
                icon: ICONS.speed,
                tooltip: '1x',
                selector: [
                    { html: '0.5x', speed: 0.5 },
                    { html: '0.75x', speed: 0.75 },
                    { default: true, html: '1x', speed: 1.0 },
                    { html: '1.25x', speed: 1.25 },
                    { html: '1.5x', speed: 1.5 },
                    { html: '2x', speed: 2.0 },
                ],
                onSelect: function (item) {
                    if (window.__kzArt) window.__kzArt.playbackRate = item.speed;
                    return item.html;
                },
            },
            {
                html: 'Picture-in-Picture',
                name: 'pip',
                icon: ICONS.pip,
                tooltip: 'Off',
                selector: [{ html: 'On' }, { default: true, html: 'Off' }],
                onSelect: function (item) {
                    const art = window.__kzArt;
                    if (art) art.pip = !art.pip;
                    return art && art.pip ? 'On' : 'Off';
                },
            },
            {
                html: 'Flip Video',
                name: 'flip',
                icon: ICONS.flip,
                tooltip: 'Normal',
                selector: [
                    { default: true, html: 'Normal', flip: 'normal' },
                    { html: 'Horizontal', flip: 'horizontal' },
                    { html: 'Vertical', flip: 'vertical' },
                ],
                onSelect: function (item) {
                    if (window.__kzArt) window.__kzArt.flip = item.flip;
                    return item.html;
                },
            },
            {
                html: 'Aspect Ratio',
                name: 'aspect-ratio',
                icon: ICONS.ratio,
                tooltip: 'Default',
                selector: [
                    { default: true, html: 'Default', ratio: 'default' },
                    { html: '16:9', ratio: '16:9' },
                    { html: '4:3', ratio: '4:3' },
                ],
                onSelect: function (item) {
                    if (window.__kzArt) window.__kzArt.aspectRatio = item.ratio;
                    return item.html;
                },
            },
        ];
    }

    /** Tombol kiri: mundur/maju 10 detik. Tombol kanan: layar penuh native. */
    function baseControls(ctx) {
        const getArt = () => (ctx && ctx.getArt ? ctx.getArt() : window.__kzArt);
        const shell = ctx && ctx.shell;
        const native = !!(ctx && ctx.nativeFs);

        const list = [
            {
                position: 'left',
                index: 10,
                html: ICONS.back10,
                tooltip: 'Mundur 10 Detik',
                click: function () {
                    const art = getArt();
                    if (art) art.seek = Math.max(0, art.currentTime - 10);
                },
            },
            {
                position: 'left',
                index: 11,
                html: ICONS.fwd10,
                tooltip: 'Maju 10 Detik',
                click: function () {
                    const art = getArt();
                    if (art) art.seek = Math.min(art.duration || 999999, art.currentTime + 10);
                },
            },
        ];

        if (native) {
            list.push({
                name: 'kz-fs',
                position: 'right',
                index: 70,
                html: ICONS.fs,
                tooltip: 'Layar penuh',
                click: function () {
                    const art = getArt();
                    if (!window.KzFs || !shell) return;
                    if (window.KzFs.isActive(shell)) window.KzFs.exit();
                    else window.KzFs.enter(shell, { video: art && art.video });
                },
            });
        }

        return list;
    }


    /**
     * Setelan subtitle standar: Captions, ukuran, posisi, gaya.
     * captionOptions: [{html, url, default}] atau format halaman masing-masing.
     */
    function subtitleSettings(captionOptions, currentLabel) {
        const sel = Array.isArray(captionOptions) ? captionOptions : [];

        return [
            {
                html: 'Captions',
                name: 'captions',
                icon: ICONS.captions,
                tooltip: currentLabel || 'Off',
                selector: sel,
                onSelect: function (item) {
                    const art = window.__kzArt;
                    if (!art) return item.html;
                    if (item.url) {
                        art.subtitle.show = true;
                        art.subtitle.switch(item.url, { name: item.html });
                    } else {
                        art.subtitle.show = false;
                    }
                    return item.html;
                },
            },
            {
                html: 'Subtitle size',
                name: 'subtitle-size',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/><path d="M9 8h6"/><path d="M12 8v6"/></svg>',
                tooltip: '100%',
                selector: [
                    { html: '75%', size: '16px' },
                    { default: true, html: '100%', size: '22px' },
                    { html: '125%', size: '26px' },
                    { html: '150%', size: '30px' },
                ],
                onSelect: function (item) {
                    if (window.__kzArt) window.__kzArt.subtitle.style('fontSize', item.size);
                    return item.html;
                },
            },
            {
                html: 'Subtitle position',
                name: 'subtitle-position',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/><line x1="6" y1="13" x2="18" y2="13" stroke-width="2.5"/></svg>',
                tooltip: 'Default',
                selector: [
                    { default: true, html: 'Default', bottom: '35px' },
                    { html: 'Top', top: '40px' },
                    { html: 'Bottom', bottom: '15px' },
                ],
                onSelect: function (item) {
                    const art = window.__kzArt;
                    if (!art) return item.html;
                    if (item.top) {
                        art.subtitle.style('bottom', 'auto');
                        art.subtitle.style('top', item.top);
                    } else {
                        art.subtitle.style('top', 'auto');
                        art.subtitle.style('bottom', item.bottom || '35px');
                    }
                    return item.html;
                },
            },
            {
                html: 'Subtitle style',
                name: 'subtitle-style',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 11-7-7-8 8 7 7 8-8z"/><path d="m19 11 2 2-2 2-2-2z"/><line x1="2" y1="21" x2="22" y2="21"/></svg>',
                tooltip: (function () {
                    var v = localStorage.getItem('kz-subtitle-style');
                    return v === 'solid' ? 'Solid Black' : (v === 'custom' ? 'Custom' : 'Clean Outline');
                })(),
                selector: [
                    { default: (localStorage.getItem('kz-subtitle-style') || 'clean') === 'clean', html: 'Clean Outline', cls: 'art-subtitle-style-clean' },
                    { default: localStorage.getItem('kz-subtitle-style') === 'custom', html: 'Custom', cls: '' },
                    { default: localStorage.getItem('kz-subtitle-style') === 'solid', html: 'Solid Black', cls: 'art-subtitle-style-solid' },
                ],
                onSelect: function (item) {
                    var art = window.__kzArt;
                    if (!art) return item.html;
                    var el = art.template && art.template.$subtitle;
                    if (el) {
                        el.classList.remove('art-subtitle-style-clean', 'art-subtitle-style-solid');
                        if (item.cls) el.classList.add(item.cls);
                    }
                    var key = item.html === 'Solid Black' ? 'solid' : (item.html === 'Custom' ? 'custom' : 'clean');
                    try { localStorage.setItem('kz-subtitle-style', key); } catch (e) {}
                    return item.html;
                },
            },
        ];
    }

    /**
     * Konfigurasi Artplayer standar.
     */
    function config(opts) {
        opts = opts || {};

        const native = opts.nativeFs !== undefined ? opts.nativeFs : isTouch();
        const url = opts.url || '';
        const isHls = /\.m3u8(\?|$)/i.test(url);

        const cfg = {
            container: opts.container,
            url: url,
            type: isHls ? 'm3u8' : 'mp4',
            poster: opts.poster || '',
            theme: '#E50914',
            volume: opts.volume !== undefined ? opts.volume : 0.8,
            muted: !!opts.muted,
            autoplay: opts.autoplay !== false,
            isLive: false,
            loop: false,
            pip: true,
            setting: true,
            flip: false,
            playbackRate: false,
            aspectRatio: false,
            fullscreen: !native,
            fullscreenWeb: !native,
            subtitleOffset: false,
            miniProgressBar: false,
            playsInline: true,
            airplay: true,
            hotkey: true,
        };

        if (isHls) {
            cfg.customType = {
                m3u8: function (video, source, art) {
                    if (window.Hls && window.Hls.isSupported()) {
                        if (art.hls) art.hls.destroy();

                        const hlsOpts = Object.assign({ enableWorker: true }, (opts.hls && opts.hls.options) || {});
                        const hls = new window.Hls(hlsOpts);
                        art.hls = hls;

                        if (opts.hls && typeof opts.hls.setup === 'function') {
                            try { opts.hls.setup(hls, video, art); } catch (e) {}
                        }

                        hls.loadSource(source);
                        hls.attachMedia(video);
                        art.on('destroy', function () { hls.destroy(); });
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = source;
                    }
                },
            };
        }

        cfg.controls = baseControls({ getArt: opts.getArt, shell: opts.shell, nativeFs: native });
        if (Array.isArray(opts.extraControls)) {
            cfg.controls = cfg.controls.concat(opts.extraControls);
        }

        cfg.settings = baseSettings();
        if (Array.isArray(opts.extraSettings)) {
            // Setelan tambahan (mis. caption) ditaruh paling depan.
            cfg.settings = opts.extraSettings.concat(cfg.settings);
        }

        if (opts.subtitleUrl) {
            cfg.subtitle = {
                url: opts.subtitleUrl,
                type: 'vtt',
                escape: false,
                style: { color: '#ffffff', fontSize: '22px', fontWeight: '600' },
                encoding: 'utf-8',
            };
        }

        return cfg;
    }

    /**
     * Fullscreen: HP masuk native + kunci landscape dari gestur play,
     * desktop masuk web fullscreen sekali.
     */
    function bindAutoFullscreen(art, shell, opts) {
        opts = opts || {};
        if (!art || !shell) return;

        const native = isTouch();
        let done = false;

        const enter = () => {
            if (done) return;

            if (native) {
                if (!window.KzFs || window.KzFs.isActive(shell)) return;
                if (navigator.userActivation && !navigator.userActivation.isActive) return;
                done = true;
                window.KzFs.enter(shell, { video: art.video });
                setTimeout(function () {
                    if (window.KzFs && !window.KzFs.isActive(shell)) done = false;
                }, 900);
                return;
            }

            done = true;
            try {
                if (!art.fullscreenWeb) art.fullscreenWeb = true;
            } catch (e) {}
        };

        art.on('play', enter);
        art.on('video:play', enter);
        if (art.video) art.video.addEventListener('play', enter);

        shell.addEventListener('kz-fs-failed', function () {
            if (native) return;
            try { art.fullscreenWeb = true; } catch (e) {}
        });

        if (opts.onFullscreenWeb) {
            art.on('fullscreenWeb', opts.onFullscreenWeb);
        }
    }

    /** Pasang daftar subtitle ke player (dipakai saat ganti server/episode). */
    function attachSubtitles(art, subtitles) {
        if (!art || !Array.isArray(subtitles) || !subtitles.length) return;

        const first = subtitles[0];
        try {
            art.subtitle.show = true;
            art.subtitle.switch(first.url, { name: first.label || 'Indonesian' });
        } catch (e) {}
    }

    window.KzPlayerCore = {
        isTouch: isTouch,
        icons: ICONS,
        baseSettings: baseSettings,
        subtitleSettings: subtitleSettings,
        baseControls: baseControls,
        config: config,
        bindAutoFullscreen: bindAutoFullscreen,
        attachSubtitles: attachSubtitles,
    };
})();
