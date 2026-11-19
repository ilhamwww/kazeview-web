/**
 * KZ Player v3 — satu sumber untuk kontrol kualitas & subtitle.
 * Dipakai oleh watch-movie, watch-series, dan watch-indonesia.
 */
(function () {
    if (window.KzPlayerV3) return;

    const normalize = (sources) => {
        if (!Array.isArray(sources)) return [];

        const seen = {};
        const list = [];

        sources.forEach((s) => {
            const q = parseInt(s && s.quality, 10);
            const url = s && (s.url || s.src);
            if (!q || !url || seen[q]) return;
            seen[q] = true;
            list.push({ quality: q, url: url, label: q + 'p' });
        });

        return list.sort((a, b) => b.quality - a.quality);
    };

    const currentVideo = (vm) => {
        if (vm && vm.art && vm.art.video) return vm.art.video;
        return document.querySelector('.art-video-player video') || document.querySelector('video');
    };

    window.KzPlayerV3 = {
        normalize: normalize,

        /**
         * Ganti sumber video tanpa kehilangan posisi tonton.
         */
        applyQuality: function (vm, url) {
            const video = currentVideo(vm);
            if (!video || !url) return;

            const at = video.currentTime || 0;
            const wasPlaying = !video.paused;
            const hls = vm && vm.hls;

            const resume = function () {
                try {
                    if (at > 1) video.currentTime = at;
                } catch (e) {}
                if (wasPlaying) video.play().catch(function () {});
            };

            video.addEventListener('loadedmetadata', resume, { once: true });

            if (hls && typeof hls.destroy === 'function' && url.indexOf('.m3u8') !== -1 && window.Hls && window.Hls.isSupported()) {
                hls.destroy();
                const next = new window.Hls({ maxBufferLength: 30 });
                next.loadSource(url);
                next.attachMedia(video);
                if (vm) vm.hls = next;
                return;
            }

            if (vm && vm.art && typeof vm.art.switchUrl === 'function') {
                try {
                    vm.art.switchUrl(url);
                    return;
                } catch (e) {}
            }

            video.src = url;
            video.load();
        },

        /**
         * Simpan pilihan kualitas agar bertahan saat pindah episode / reload.
         */
        rememberQuality: function (quality) {
            try {
                localStorage.setItem('kz_player_quality', String(quality));
            } catch (e) {}
        },

        preferredQuality: function (options) {
            let saved = 0;
            try {
                saved = parseInt(localStorage.getItem('kz_player_quality') || '0', 10);
            } catch (e) {}

            if (saved && options.some((o) => o.quality === saved)) return saved;
            return options.length ? options[0].quality : 0;
        },
    };
})();
