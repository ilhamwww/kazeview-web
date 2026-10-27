/**
 * KZSTRM Server Selection Loading Indicator for Artplayer & Streaming UI
 * - Initial load / server switch: Shows 3-server cluster ("Memilih server terbaik...")
 * - Seeking / scrubbing / mid-stream buffering: Shows clean circular rotating spinner while keeping time bar visible
 */
(function() {
    function getServerClusterHtml(title = 'Memilih server terbaik', subtitle = 'Mengoptimalkan stream film & resolusi HD') {
        return `
        <div class="kz-server-loader-box relative flex flex-col items-center justify-center text-center select-none pointer-events-none max-w-sm w-full mx-auto p-4">
            <div class="relative w-44 h-32 md:w-52 md:h-36 flex items-center justify-center">
                <svg viewBox="0 0 240 160" class="w-full h-full overflow-visible" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <filter id="kz-shadow-soft-art" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="4" />
                        </filter>
                        <linearGradient id="kz-server-grad-side-art" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#1A1E29" />
                            <stop offset="100%" stop-color="#10131A" />
                        </linearGradient>
                        <linearGradient id="kz-server-grad-center-art" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#222736" />
                            <stop offset="100%" stop-color="#131620" />
                        </linearGradient>
                        <linearGradient id="kz-center-border-art" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#FF2D55" />
                            <stop offset="100%" stop-color="#E50914" />
                        </linearGradient>
                    </defs>

                    <!-- Base floor shadow -->
                    <ellipse cx="120" cy="146" rx="96" ry="10" fill="#000000" opacity="0.75" filter="url(#kz-shadow-soft-art)" />
                    <ellipse cx="120" cy="146" rx="42" ry="5" fill="#E50914" opacity="0.18" filter="url(#kz-shadow-soft-art)" />

                    <!-- Left Server (Inactive) -->
                    <g>
                        <rect x="36" y="52" width="46" height="84" rx="12" fill="url(#kz-server-grad-side-art)" stroke="#282D3D" stroke-width="2" />
                        <line x1="46" y1="68" x2="63" y2="68" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                        <line x1="46" y1="78" x2="63" y2="78" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                        <line x1="46" y1="88" x2="63" y2="88" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                        <circle cx="71" cy="67" r="2.5" fill="#E50914" opacity="0.65" />
                        <circle cx="71" cy="76" r="2.5" fill="#202430" />
                    </g>

                    <!-- Right Server (Inactive) -->
                    <g>
                        <rect x="158" y="52" width="46" height="84" rx="12" fill="url(#kz-server-grad-side-art)" stroke="#282D3D" stroke-width="2" />
                        <line x1="168" y1="68" x2="185" y2="68" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                        <line x1="168" y1="78" x2="185" y2="78" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                        <line x1="168" y1="88" x2="185" y2="88" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                        <circle cx="193" cy="67" r="2.5" fill="#E50914" opacity="0.65" />
                        <circle cx="193" cy="76" r="2.5" fill="#202430" />
                    </g>

                    <!-- Middle Server (Active / Optimal Highlight) -->
                    <g>
                        <rect x="95" y="44" width="50" height="92" rx="15" fill="none" stroke="#FF2D55" stroke-width="2.5" opacity="0.4"
                              style="animation: kz-pulse-glow 2s ease-in-out infinite;" />
                        <rect x="96" y="45" width="48" height="90" rx="14" fill="url(#kz-server-grad-center-art)" stroke="url(#kz-center-border-art)" stroke-width="2.6"
                              style="filter: drop-shadow(0 0 12px rgba(229, 9, 20, 0.65));" />
                        <line x1="107" y1="64" x2="125" y2="64" stroke="#566078" stroke-width="2.8" stroke-linecap="round" />
                        <line x1="107" y1="75" x2="125" y2="75" stroke="#566078" stroke-width="2.8" stroke-linecap="round" />
                        <line x1="107" y1="86" x2="125" y2="86" stroke="#566078" stroke-width="2.8" stroke-linecap="round" />
                        <circle cx="132" cy="63" r="3" fill="#FF2D55" style="animation: kz-led-flash 1.2s ease-in-out infinite;" />
                        <circle cx="132" cy="73" r="3" fill="#242834" />

                        <!-- Broadcast Signal Waves -->
                        <circle cx="120" cy="27" r="3.5" fill="#E50914" style="filter: drop-shadow(0 0 6px #ff2d55);" />
                        <path style="animation: kz-wave-arc 1.8s ease-in-out infinite 0.1s; transform-origin: 120px 24px;" d="M109 19 C114 14 126 14 131 19" stroke="#8A93A6" stroke-width="2.4" stroke-linecap="round" fill="none" />
                        <path style="animation: kz-wave-arc 1.8s ease-in-out infinite 0.35s; transform-origin: 120px 24px;" d="M102 11 C111 3 129 3 138 11" stroke="#8A93A6" stroke-width="2.4" stroke-linecap="round" fill="none" />
                        <path style="animation: kz-wave-arc 1.8s ease-in-out infinite 0.6s; transform-origin: 120px 24px;" d="M95 3 C108 -7 132 -7 145 3" stroke="#8A93A6" stroke-width="2.4" stroke-linecap="round" fill="none" />
                    </g>
                </svg>
            </div>

            <div class="mt-3.5 space-y-1">
                <h4 class="text-sm md:text-base font-bold tracking-tight text-white flex items-center justify-center gap-1 drop-shadow">
                    <span>${title}</span>
                    <span class="inline-flex tracking-widest text-[#E50914] font-black">
                        <span style="animation: kz-dot-step 1.4s infinite 0.0s;">.</span>
                        <span style="animation: kz-dot-step 1.4s infinite 0.2s;">.</span>
                        <span style="animation: kz-dot-step 1.4s infinite 0.4s;">.</span>
                    </span>
                </h4>
                <p class="text-[11px] md:text-xs text-zinc-400 font-medium leading-relaxed max-w-xs">${subtitle}</p>
            </div>
        </div>
        `;
    }

    function getCircularSpinnerHtml() {
        return `
        <div class="kz-seek-spinner-wrap relative flex items-center justify-center pointer-events-none select-none">
            <div class="w-12 h-12 md:w-14 md:h-14 rounded-full border-[3.5px] border-white/15"></div>
            <div class="absolute inset-0 w-12 h-12 md:w-14 md:h-14 rounded-full border-[3.5px] border-[#E50914] border-t-transparent animate-spin" style="filter: drop-shadow(0 0 8px rgba(229, 9, 20, 0.7));"></div>
        </div>
        `;
    }

    function applyToArtplayer(art, title, subtitle) {
        if (!art || !art.template || !art.template.$loading) return;
        const loadingEl = art.template.$loading;

        // Pasang animasi 3-server untuk initial load
        loadingEl.classList.add('kz-server-initial-loading');
        loadingEl.innerHTML = getServerClusterHtml(title, subtitle);

        // Setelah video mulai berjalan (sudah mulai streaming), ganti ke circular spinner untuk seeking berikutnya
        let hasSwitchedToSeekSpinner = false;
        const switchToSeekSpinner = () => {
            if (hasSwitchedToSeekSpinner) return;
            hasSwitchedToSeekSpinner = true;
            loadingEl.classList.remove('kz-server-initial-loading');
            loadingEl.innerHTML = getCircularSpinnerHtml();
        };

        art.once('video:playing', switchToSeekSpinner);
        art.once('video:timeupdate', () => {
            if (art.currentTime > 0.3) switchToSeekSpinner();
        });

        // Backup timer: maksimal 12 detik setelah ready, jika video jalan, switch otomatis
        setTimeout(() => {
            if (art.video && !art.video.paused && art.video.currentTime > 0) {
                switchToSeekSpinner();
            }
        }, 8000);
    }

    window.kzServerLoader = {
        getHtml: getServerClusterHtml,
        getSeekSpinnerHtml: getCircularSpinnerHtml,
        applyToArtplayer
    };
})();
