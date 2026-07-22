<div class="min-h-screen pt-28 pb-16 px-4 md:px-8 max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-white">Profil Saya</h1>
        <p class="text-zinc-400 text-sm mt-1">Kelola informasi akun dan kata sandi Anda.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Sidebar Menu (if needed, but for now just a summary card) -->
        <div class="col-span-1">
            <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 backdrop-blur-md flex flex-col items-center text-center">
                <div class="w-24 h-24 rounded-full bg-zinc-800 border-2 border-red-500/50 flex items-center justify-center text-white font-extrabold uppercase text-3xl shadow-lg mb-4">
                    {{ substr(auth()->user()->name, 0, 2) }}
                </div>
                <h2 class="text-xl font-bold text-white">{{ auth()->user()->name }}</h2>
                <p class="text-zinc-400 text-sm mb-6">{{ auth()->user()->email }}</p>
                
                <div class="w-full h-px bg-zinc-800 my-2"></div>
                
                <div class="w-full text-left py-3">
                    <p class="text-xs text-zinc-500 font-semibold uppercase tracking-wider mb-1">Role Akun</p>
                    <div class="flex items-center gap-2">
                        @if(auth()->user()->hasRole('super_admin'))
                            <span class="px-2.5 py-1 bg-red-600/10 border border-red-500/20 text-red-500 rounded-lg text-xs font-bold">Administrator</span>
                        @else
                            <span class="px-2.5 py-1 bg-zinc-800 border border-zinc-700 text-zinc-300 rounded-lg text-xs font-bold">Member</span>
                        @endif
                    </div>
                </div>

                <div class="w-full h-px bg-zinc-800 my-2"></div>

                <div class="w-full text-left py-3">
                    <p class="text-xs text-zinc-500 font-semibold uppercase tracking-wider mb-1">Langganan</p>
                    @if(auth()->user()->subscription_expires_at)
                        @if(auth()->user()->isSubscriptionExpired())
                            <span class="px-2.5 py-1 bg-red-600/10 border border-red-500/20 text-red-500 rounded-lg text-xs font-bold">Expired</span>
                            <p class="text-xs text-zinc-500 mt-2">Berakhir: <span class="text-red-400 font-semibold">{{ auth()->user()->subscription_expires_at->translatedFormat('d F Y, H:i') }}</span></p>
                        @else
                            <span class="px-2.5 py-1 bg-emerald-600/10 border border-emerald-500/20 text-emerald-500 rounded-lg text-xs font-bold">Active</span>
                            <p class="text-xs text-zinc-500 mt-2">Berakhir: <span class="text-zinc-300 font-semibold">{{ auth()->user()->subscription_expires_at->translatedFormat('d F Y, H:i') }}</span></p>
                        @endif
                    @else
                        <span class="px-2.5 py-1 bg-zinc-800 border border-zinc-700 text-zinc-300 rounded-lg text-xs font-bold">Unlimited</span>
                        <p class="text-xs text-zinc-500 mt-2">Tidak ada batas waktu.</p>
                    @endif
                </div>
            </div>

            <!-- Login History Card -->
            <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 backdrop-blur-md mt-6">
                <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 19.257V18.25A2.25 2.25 0 0012.75 16h-1.5A2.25 2.25 0 009 18.25zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Riwayat Login Perangkat
                </h3>
                
                <div class="space-y-3">
                    @forelse(auth()->user()->loginHistories()->take(5)->get() as $history)
                        <div class="p-3 bg-zinc-950/60 border border-zinc-800 rounded-2xl space-y-2">
                            <div class="flex items-center gap-2.5">
                                <!-- Device Icon -->
                                <div class="w-8 h-8 rounded-lg bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 flex-shrink-0">
                                    @if(strtolower($history->device) === 'mobile')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-6 18.75h12" /></svg>
                                    @elseif(strtolower($history->device) === 'tablet')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-6 18.75h12" /></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 19.257V18.25A2.25 2.25 0 0012.75 16h-1.5A2.25 2.25 0 009 18.25zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    @endif
                                </div>
                                
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-white truncate">{{ $history->browser }} ({{ $history->platform }})</h4>
                                    <p class="text-[10px] text-zinc-500 mt-0.5">IP: {{ $history->ip_address }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between border-t border-zinc-800/50 pt-2 text-[10px] text-zinc-500">
                                <span>{{ $history->login_at->diffForHumans() }}</span>
                                <span>{{ $history->login_at->translatedFormat('d M Y, H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-zinc-500 text-xs">
                            Belum ada riwayat login.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Forms -->
        <div class="col-span-1 md:col-span-2 space-y-8">
            
            <!-- Update Profile Form -->
            <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 md:p-8 backdrop-blur-md">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Informasi Akun
                </h3>
                
                <form wire:submit="updateProfile" class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-zinc-300 mb-1.5">Nama Lengkap</label>
                        <input type="text" id="name" wire:model="name" class="block w-full px-4 py-2.5 bg-zinc-950/60 border border-zinc-800 rounded-xl text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-red-600/50 focus:ring-1 focus:ring-red-600/50 transition-colors text-sm">
                        @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-semibold text-zinc-300 mb-1.5">Alamat Email</label>
                        <input type="email" id="email" wire:model="email" class="block w-full px-4 py-2.5 bg-zinc-950/60 border border-zinc-800 rounded-xl text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-red-600/50 focus:ring-1 focus:ring-red-600/50 transition-colors text-sm">
                        @error('email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold rounded-xl transition duration-150 inline-flex items-center gap-2 text-sm shadow-lg shadow-red-600/20">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Update Password Form -->
            <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 md:p-8 backdrop-blur-md">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Ubah Kata Sandi
                </h3>
                
                <form wire:submit="updatePassword" class="space-y-5"></form>
                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-zinc-300 mb-1.5">Kata Sandi Saat Ini</label>
                        <input type="password" id="current_password" wire:model="current_password" class="block w-full px-4 py-2.5 bg-zinc-950/60 border border-zinc-800 rounded-xl text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-red-600/50 focus:ring-1 focus:ring-red-600/50 transition-colors text-sm">
                        @error('current_password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-semibold text-zinc-300 mb-1.5">Kata Sandi Baru</label>
                        <input type="password" id="new_password" wire:model="new_password" class="block w-full px-4 py-2.5 bg-zinc-950/60 border border-zinc-800 rounded-xl text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-red-600/50 focus:ring-1 focus:ring-red-600/50 transition-colors text-sm">
                        @error('new_password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="block text-sm font-semibold text-zinc-300 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" id="new_password_confirmation" wire:model="new_password_confirmation" class="block w-full px-4 py-2.5 bg-zinc-950/60 border border-zinc-800 rounded-xl text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-red-600/50 focus:ring-1 focus:ring-red-600/50 transition-colors text-sm">
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 active:scale-95 border border-zinc-700 text-white font-bold rounded-xl transition duration-150 inline-flex items-center gap-2 text-sm">
                            Perbarui Sandi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Favorite List Section -->
            <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 md:p-8 backdrop-blur-md">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 fill-current" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    Daftar Favorit Saya
                </h3>

                @if($favorites->isEmpty())
                    <div class="text-center py-12 text-zinc-500 text-sm border border-dashed border-zinc-800 rounded-2xl">
                        Belum ada konten yang ditambahkan ke favorit.
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($favorites as $fav)
                            @php
                                $detailUrl = '#';
                                try {
                                    if ($fav->item_type === 'movie') {
                                        $detailUrl = \App\Filament\Streaming\Pages\WatchMovie::getUrl(['slug' => $fav->item_id], panel: 'streaming');
                                    } elseif ($fav->item_type === 'tv_series') {
                                        $detailUrl = \App\Filament\Streaming\Pages\ShowSeries::getUrl(['slug' => $fav->item_id], panel: 'streaming');
                                    } elseif ($fav->item_type === 'anime') {
                                        $detailUrl = \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $fav->item_id], panel: 'streaming');
                                    } elseif ($fav->item_type === 'bstation') {
                                        $detailUrl = \App\Filament\Streaming\Pages\BstationDetail::getUrl(['id' => $fav->item_id], panel: 'streaming');
                                    } elseif ($fav->item_type === 'dracin') {
                                        $dracinParts = explode(':', $fav->item_id);
                                        $networkSlug = $dracinParts[0] ?? '';
                                        $dramaId = $dracinParts[1] ?? '';
                                        $detailUrl = \App\Filament\Streaming\Pages\DracinDetail::getUrl(['network_slug' => $networkSlug, 'id' => $dramaId], panel: 'streaming');
                                    }
                                } catch (\Exception $e) {
                                    $detailUrl = $fav->url ?? '#';
                                }
                            @endphp
                            <div class="group relative flex flex-col bg-zinc-950/60 border border-zinc-800/80 rounded-2xl overflow-hidden hover:border-zinc-700 transition duration-300">
                                <!-- Thumbnail -->
                                <a href="{{ $detailUrl }}" class="block aspect-[3/4] w-full overflow-hidden bg-black relative">
                                    @if($fav->thumbnail)
                                        @if($fav->item_type === 'dracin')
                                            <img src="{{ url('/api/proxy-image?url=' . urlencode($fav->thumbnail)) }}" alt="{{ $fav->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" referrerpolicy="no-referrer">
                                        @else
                                            <img src="{{ $fav->thumbnail }}" alt="{{ $fav->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" referrerpolicy="no-referrer">
                                        @endif
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-zinc-700 text-xs">No Cover</div>
                                    @endif
                                    
                                    <!-- Badge Type -->
                                    <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-extrabold text-gray-200 bg-black/60 backdrop-blur-md rounded border border-white/5 uppercase tracking-wider">
                                        {{ $fav->item_type }}
                                    </span>
                                </a>

                                <!-- Description / Title -->
                                <div class="p-3 flex-1 flex flex-col justify-between gap-2">
                                    <a href="{{ $detailUrl }}" class="block text-xs font-bold text-gray-200 hover:text-red-500 transition duration-200 line-clamp-2 leading-snug">
                                        {{ $fav->title }}
                                    </a>

                                    <button wire:click="removeFromFavorites({{ $fav->id }})" class="w-full mt-2 py-1.5 bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white rounded-lg text-[10px] font-bold border border-red-500/20 transition flex items-center justify-center gap-1">
                                        <svg class="w-3 h-3 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Watch History Section -->
            <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 md:p-8 backdrop-blur-md">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 fill-none stroke-current" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Menonton Saya
                </h3>

                @if($watchHistories->isEmpty())
                    <div class="text-center py-12 text-zinc-500 text-sm border border-dashed border-zinc-800 rounded-2xl">
                        Belum ada riwayat menonton.
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($watchHistories as $history)
                            @php
                                $detailUrl = '#';
                                try {
                                    if ($history->item_type === 'movie') {
                                        $detailUrl = \App\Filament\Streaming\Pages\WatchMovie::getUrl(['slug' => $history->item_id], panel: 'streaming');
                                    } elseif ($history->item_type === 'tv_series') {
                                        $detailUrl = \App\Filament\Streaming\Pages\ShowSeries::getUrl(['slug' => $history->item_id], panel: 'streaming');
                                    } elseif ($history->item_type === 'anime') {
                                        $detailUrl = \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $history->item_id], panel: 'streaming');
                                    } elseif ($history->item_type === 'bstation') {
                                        $detailUrl = \App\Filament\Streaming\Pages\BstationDetail::getUrl(['id' => $history->item_id], panel: 'streaming');
                                    } elseif ($history->item_type === 'dracin') {
                                        $dracinParts = explode(':', $history->item_id);
                                        $networkSlug = $dracinParts[0] ?? '';
                                        $dramaId = $dracinParts[1] ?? '';
                                        $detailUrl = \App\Filament\Streaming\Pages\DracinDetail::getUrl(['network_slug' => $networkSlug, 'id' => $dramaId], panel: 'streaming');
                                    }
                                } catch (\Exception $e) {
                                    $detailUrl = $history->url ?? '#';
                                }
                            @endphp
                            <div class="group relative flex flex-col bg-zinc-950/60 border border-zinc-800/80 rounded-2xl overflow-hidden hover:border-zinc-700 transition duration-300">
                                <!-- Thumbnail -->
                                <a href="{{ $detailUrl }}" class="block aspect-[3/4] w-full overflow-hidden bg-black relative">
                                    @if($history->thumbnail)
                                        @if($history->item_type === 'dracin')
                                            <img src="{{ url('/api/proxy-image?url=' . urlencode($history->thumbnail)) }}" alt="{{ $history->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" referrerpolicy="no-referrer">
                                        @else
                                            <img src="{{ $history->thumbnail }}" alt="{{ $history->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" referrerpolicy="no-referrer">
                                        @endif
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-zinc-700 text-xs">No Cover</div>
                                    @endif
                                    
                                    <!-- Badge Type -->
                                    <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-extrabold text-gray-200 bg-black/60 backdrop-blur-md rounded border border-white/5 uppercase tracking-wider">
                                        {{ $history->item_type }}
                                    </span>
                                </a>

                                <!-- Description / Title -->
                                <div class="p-3 flex-1 flex flex-col justify-between gap-2">
                                    <div class="min-w-0">
                                        <a href="{{ $detailUrl }}" class="block text-xs font-bold text-gray-200 hover:text-red-500 transition duration-200 line-clamp-2 leading-snug mb-1">
                                            {{ $history->title }}
                                        </a>
                                        <p class="text-[10px] text-red-400 font-bold">Terakhir: Episode {{ $history->last_episode }}</p>
                                    </div>

                                    <div class="space-y-1.5 mt-2">
                                        <a href="{{ $detailUrl }}" class="w-full py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-[10px] font-bold transition flex items-center justify-center gap-1 shadow-md shadow-red-600/10">
                                            Putar Kembali
                                        </a>
                                        <button wire:click="removeFromHistory({{ $history->id }})" class="w-full py-1 bg-zinc-800/60 hover:bg-zinc-800 text-zinc-400 hover:text-white border border-zinc-800 hover:border-zinc-700 rounded-lg text-[10px] font-bold transition flex items-center justify-center gap-1">
                                            Hapus Riwayat
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
