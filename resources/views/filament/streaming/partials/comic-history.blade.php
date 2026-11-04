<section id="comic-history" x-show="recentReads.length > 0" style="display:none" class="mb-8">
    <div class="flex items-center justify-between gap-4 mb-4">
        <h2 class="text-lg md:text-xl font-black">Terakhir Kali Dibaca</h2>
        <button type="button" @click="clearRecentReads()" class="text-xs font-semibold text-zinc-500 hover:text-red-400">Hapus Riwayat</button>
    </div>
    <div class="flex gap-3 overflow-x-auto overflow-y-hidden overscroll-x-contain scrollbar-none snap-x py-2 px-1">
        <template x-for="item in recentReads" :key="item.comicId">
            <a :href="'/komik/detail/' + item.comicId" class="snap-start shrink-0 w-[260px] sm:w-[300px] flex gap-3 p-3 rounded-2xl bg-zinc-900/70 border border-white/10 hover:border-red-600/50 transition">
                <div class="relative shrink-0 w-16 aspect-[2/3] rounded-lg overflow-hidden bg-zinc-800">
                    <span class="absolute inset-0 flex items-center justify-center text-lg font-black text-zinc-600" x-text="(item.comicTitle || '?').charAt(0)"></span>
                    <template x-if="item.comicCover"><img :src="item.comicCover" alt="" class="relative w-full h-full object-cover" loading="lazy" x-on:error="$el.style.display='none'"></template>
                </div>
                <div class="min-w-0 flex-1 flex flex-col justify-center">
                    <p class="text-[10px] font-bold text-red-400" x-text="item.comicId.startsWith('wurmz__') ? 'Wurmz Scans' : (item.comicId.startsWith('izanami__') ? 'Izanami Scans' : (item.comicId.startsWith('kiryuu__') ? 'Kiryuu ID' : 'Shinigami ID'))"></p>
                    <p class="mt-1 text-sm font-bold text-white truncate" x-text="item.comicTitle"></p>
                    <p class="mt-1 text-xs text-zinc-400 truncate" x-text="item.chapterLabel"></p>
                    <div class="mt-2 flex items-center gap-2">
                        <div class="flex-1 h-1 rounded-full bg-zinc-800 overflow-hidden"><div class="h-full bg-red-600 rounded-full" :style="'width:' + (item.progress || 0) + '%'"></div></div>
                        <span class="text-[10px] font-bold text-red-400" x-text="(item.progress || 0) + '%'"></span>
                    </div>
                    <p class="mt-1 text-[10px] text-zinc-500">Lanjut dari panel <span x-text="item.panelIndex || 1"></span></p>
                </div>
            </a>
        </template>
    </div>
</section>
