@props([
    'title' => 'Memilih server terbaik',
    'subtitle' => 'Menghubungkan ke jalur stream tercepat & resolusi HD',
    'showCountdown' => false,
])

<div class="relative flex flex-col items-center justify-center text-center select-none pointer-events-none max-w-sm w-full mx-auto p-4">
    <!-- Server Cluster Graphic -->
    <div class="relative w-44 h-32 md:w-52 md:h-36 flex items-center justify-center">
        <svg viewBox="0 0 240 160" class="w-full h-full overflow-visible" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- Glow Filters -->
                <filter id="kz-neon-glow" x="-30%" y="-30%" width="160%" height="160%">
                    <feGaussianBlur stdDeviation="6" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
                <filter id="kz-shadow-soft" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="4" />
                </filter>
                <!-- Subtle Top-to-Bottom Server Gradients -->
                <linearGradient id="kz-server-grad-side" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#1A1E29" />
                    <stop offset="100%" stop-color="#10131A" />
                </linearGradient>
                <linearGradient id="kz-server-grad-center" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#222736" />
                    <stop offset="100%" stop-color="#131620" />
                </linearGradient>
                <linearGradient id="kz-center-border" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#FF2D55" />
                    <stop offset="100%" stop-color="#E50914" />
                </linearGradient>
            </defs>

            <!-- Elliptical base shadow with radial floor fade -->
            <ellipse cx="120" cy="146" rx="96" ry="10" fill="#000000" opacity="0.75" filter="url(#kz-shadow-soft)" />
            <ellipse cx="120" cy="146" rx="42" ry="5" fill="#E50914" opacity="0.18" filter="url(#kz-shadow-soft)" />

            <!-- Left Server (Inactive) -->
            <g class="transition duration-500">
                <rect x="36" y="52" width="46" height="84" rx="12" fill="url(#kz-server-grad-side)" stroke="#282D3D" stroke-width="2" />
                <!-- Drive lines -->
                <line x1="46" y1="68" x2="63" y2="68" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                <line x1="46" y1="78" x2="63" y2="78" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                <line x1="46" y1="88" x2="63" y2="88" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                <!-- LEDs -->
                <circle cx="71" cy="67" r="2.5" fill="#E50914" opacity="0.65" />
                <circle cx="71" cy="76" r="2.5" fill="#202430" />
            </g>

            <!-- Right Server (Inactive) -->
            <g class="transition duration-500">
                <rect x="158" y="52" width="46" height="84" rx="12" fill="url(#kz-server-grad-side)" stroke="#282D3D" stroke-width="2" />
                <!-- Drive lines -->
                <line x1="168" y1="68" x2="185" y2="68" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                <line x1="168" y1="78" x2="185" y2="78" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                <line x1="168" y1="88" x2="185" y2="88" stroke="#3A4154" stroke-width="2.6" stroke-linecap="round" />
                <!-- LEDs -->
                <circle cx="193" cy="67" r="2.5" fill="#E50914" opacity="0.65" />
                <circle cx="193" cy="76" r="2.5" fill="#202430" />
            </g>

            <!-- Middle Server (Active / Optimal Highlight) -->
            <g class="transition duration-500">
                <!-- Outer breathing neon glow ring -->
                <rect x="95" y="44" width="50" height="92" rx="15" fill="none" stroke="#FF2D55" stroke-width="2.5" opacity="0.4"
                      class="animate-[kz-pulse-glow_2s_ease-in-out_infinite]" />
                
                <!-- Main Middle Server Chassis -->
                <rect x="96" y="45" width="48" height="90" rx="14" fill="url(#kz-server-grad-center)" stroke="url(#kz-center-border)" stroke-width="2.6"
                      style="filter: drop-shadow(0 0 12px rgba(229, 9, 20, 0.65));" />
                
                <!-- Inner drive lines -->
                <line x1="107" y1="64" x2="125" y2="64" stroke="#566078" stroke-width="2.8" stroke-linecap="round" />
                <line x1="107" y1="75" x2="125" y2="75" stroke="#566078" stroke-width="2.8" stroke-linecap="round" />
                <line x1="107" y1="86" x2="125" y2="86" stroke="#566078" stroke-width="2.8" stroke-linecap="round" />
                
                <!-- Flashing Status LEDs -->
                <circle cx="132" cy="63" r="3" fill="#FF2D55" class="animate-[kz-led-flash_1.2s_ease-in-out_infinite]" />
                <circle cx="132" cy="73" r="3" fill="#242834" />

                <!-- Broadcast Waves radiating from top of middle server -->
                <!-- Origin emitter dot -->
                <circle cx="120" cy="27" r="3.5" fill="#E50914" style="filter: drop-shadow(0 0 6px #ff2d55);" />
                
                <!-- Wave 1 (inner) -->
                <path class="animate-[kz-wave-arc_1.8s_ease-in-out_infinite_0.1s]" d="M109 19 C114 14 126 14 131 19" stroke="#8A93A6" stroke-width="2.4" stroke-linecap="round" fill="none" />
                <!-- Wave 2 (middle) -->
                <path class="animate-[kz-wave-arc_1.8s_ease-in-out_infinite_0.35s]" d="M102 11 C111 3 129 3 138 11" stroke="#8A93A6" stroke-width="2.4" stroke-linecap="round" fill="none" />
                <!-- Wave 3 (outer) -->
                <path class="animate-[kz-wave-arc_1.8s_ease-in-out_infinite_0.6s]" d="M95 3 C108 -7 132 -7 145 3" stroke="#8A93A6" stroke-width="2.4" stroke-linecap="round" fill="none" />
            </g>
        </svg>
    </div>

    <!-- Status Text & Subtitle -->
    <div class="mt-3.5 space-y-1">
        <h4 class="text-sm md:text-base font-bold tracking-tight text-white flex items-center justify-center gap-1 drop-shadow">
            <span>{{ $title }}</span>
            <span class="inline-flex tracking-widest text-[#E50914] font-black">
                <span class="animate-[kz-dot-step_1.4s_infinite_0.0s]">.</span>
                <span class="animate-[kz-dot-step_1.4s_infinite_0.2s]">.</span>
                <span class="animate-[kz-dot-step_1.4s_infinite_0.4s]">.</span>
            </span>
        </h4>
        <p class="text-[11px] md:text-xs text-zinc-400 font-medium leading-relaxed max-w-xs">
            {{ $subtitle }}
            @if($showCountdown)
                <template x-if="loadingSeconds > 0">
                    <span class="block text-red-400 font-mono font-bold mt-0.5" x-text="'(' + loadingSeconds + ' detik)'"></span>
                </template>
            @endif
        </p>
    </div>
</div>
