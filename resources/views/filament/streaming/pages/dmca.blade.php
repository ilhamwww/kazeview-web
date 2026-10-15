<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-20 relative overflow-x-hidden" x-data="{ lang: 'id' }">
    {{-- Ambient Glow Background --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[600px] overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] md:w-[800px] h-[350px] md:h-[400px] bg-red-600/10 rounded-full blur-[120px] md:blur-[140px]"></div>
    </div>

    {{-- Header Container --}}
    <div class="relative w-full max-w-5xl mx-auto px-4 md:px-8 pt-20 md:pt-28 pb-8 space-y-8">
        {{-- Breadcrumb & Back --}}
        <div class="flex items-center justify-between gap-3">
            <a href="/"
               class="inline-flex items-center gap-2 text-xs md:text-sm font-semibold text-zinc-400 hover:text-white transition group">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Beranda
            </a>

            <div class="flex items-center gap-1.5 p-1 bg-zinc-900/80 border border-zinc-800 rounded-2xl">
                <button type="button" @click="lang = 'id'"
                        :class="lang === 'id' ? 'bg-red-600 text-white shadow-lg shadow-red-900/30' : 'text-zinc-400 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                    <span>🇮🇩</span>
                    <span>Indonesia</span>
                </button>
                <button type="button" @click="lang = 'en'"
                        :class="lang === 'en' ? 'bg-red-600 text-white shadow-lg shadow-red-900/30' : 'text-zinc-400 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                    <span>🇺🇸</span>
                    <span>English</span>
                </button>
            </div>
        </div>

        {{-- Hero Header --}}
        <div class="text-center space-y-4 max-w-3xl mx-auto pt-4">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-600/10 border border-red-500/25 text-red-500 text-[11px] font-black uppercase tracking-[0.2em]">
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                </svg>
                Digital Millennium Copyright Act
            </div>

            <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white leading-tight">
                DMCA & Copyright Policy
            </h1>

            <p class="text-sm md:text-base text-zinc-400 leading-relaxed" x-show="lang === 'id'">
                Pernyataan kepatuhan hak cipta, penafian layanan streaming, dan panduan resmi permohonan penghapusan konten (<em class="text-zinc-200">takedown request</em>).
            </p>
            <p class="text-sm md:text-base text-zinc-400 leading-relaxed" x-show="lang === 'en'" style="display: none;">
                Copyright compliance statement, service disclaimer, and official takedown notice guidelines under the Digital Millennium Copyright Act.
            </p>
        </div>

        {{-- 3 Key Highlights Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800/80 space-y-2">
                <div class="w-10 h-10 rounded-xl bg-red-950/60 border border-red-500/20 flex items-center justify-center text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-white" x-text="lang === 'id' ? 'Tidak Menyimpan Video' : 'Non-Hosting Disclaimer'"></h3>
                <p class="text-xs text-zinc-400 leading-relaxed" x-text="lang === 'id' ? 'KZSTRM tidak mengunggah, meng-host, atau menyimpan file media video pada server kami sendiri. Seluruh konten berasal dari penyedia pihak ketiga publik.' : 'KZSTRM does not upload, host, or store any media files on its own servers. All content is indexed or embedded from non-affiliated third-party hosts.'"></p>
            </div>

            <div class="p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800/80 space-y-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-950/60 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-white" x-text="lang === 'id' ? 'Respon Cepat 24-48 Jam' : 'Fast 24-48h Response'"></h3>
                <p class="text-xs text-zinc-400 leading-relaxed" x-text="lang === 'id' ? 'Kami menanggapi setiap laporan pelanggaran hak cipta yang sah dengan serius dan segera memutus tautan konten terkait.' : 'We take all valid copyright infringement notices seriously and promptly remove or disable access to the infringing links.'"></p>
            </div>

            <div class="p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800/80 space-y-2">
                <div class="w-10 h-10 rounded-xl bg-blue-950/60 border border-blue-500/20 flex items-center justify-center text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-white" x-text="lang === 'id' ? 'Kontak Resmi DMCA' : 'Official DMCA Agent'"></h3>
                <p class="text-xs text-zinc-400 leading-relaxed" x-text="lang === 'id' ? 'Hubungi langsung perwakilan hak cipta kami melalui email yang tertera untuk memproses pengajuan Anda.' : 'Reach out directly to our designated copyright contact via email to file an infringement report.'"></p>
            </div>
        </div>

        {{-- Main Document Card --}}
        <div class="bg-zinc-950/80 border border-zinc-800 rounded-3xl p-6 md:p-10 shadow-2xl space-y-8">
            {{-- INDONESIA CONTENT --}}
            <div x-show="lang === 'id'" class="space-y-6 text-sm text-zinc-300 leading-relaxed">
                <div class="flex items-center gap-3 pb-4 border-b border-zinc-800/80">
                    <span class="text-2xl">🇮🇩</span>
                    <div>
                        <h2 class="text-lg md:text-xl font-bold text-white">Kebijakan Hak Cipta & DMCA (Bahasa Indonesia)</h2>
                        <p class="text-xs text-zinc-500">Pemberitahuan resmi kepatuhan hukum hak cipta digital</p>
                    </div>
                </div>

                <p>
                    <strong class="text-white">KZSTRM</strong> adalah sebuah platform online penyedia jasa dan layanan agregator media digital sebagaimana didefinisikan dalam <strong class="text-zinc-100">Digital Millennium Copyright Act (17 U.S.C. § 512)</strong>. Kami sangat menghormati dan mendukung penuh perlindungan hak kekayaan intelektual serta hak cipta pemilik materi secara serius.
                </p>

                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/25 text-amber-200 text-xs leading-relaxed space-y-1">
                    <p class="font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        Penting untuk diketahui:
                    </p>
                    <p>
                        Seluruh file video, streaming audio-visual, poster, dan gambar yang ditampilkan di website ini di-host oleh pihak ketiga yang tidak terafiliasi dengan KZSTRM. Server kami tidak mengunggah, menyimpan, atau mendistribusikan file media berhak cipta secara langsung.
                    </p>
                </div>

                <p>
                    Jika Anda adalah pemilik hak cipta yang sah atas konten apapun yang muncul pada website <strong>KZSTRM</strong> dan Anda tidak mengizinkan penayangan atau penautan konten tersebut, Anda sangat diperkenankan untuk memberitahu kami agar kami dapat segera mengidentifikasi dan menonaktifkan tautan materi terkait.
                </p>

                <div class="space-y-3 pt-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-600"></span>
                        Persyaratan Permohonan Takedown (DMCA Notice)
                    </h3>
                    <p class="text-xs text-zinc-400">
                        Kami hanya dapat memproses permohonan jika Anda menyertakan informasi yang lengkap dan akurat di bawah ini:
                    </p>

                    <ul class="space-y-2.5 pl-2 list-none">
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">1</span>
                            <span><strong>Spesifikasi karya cipta:</strong> Berikan identifikasi dan deskripsi lengkap mengenai karya cipta yang Anda klaim telah dilanggar.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">2</span>
                            <span><strong>URL tautan spesifik:</strong> Cantumkan daftar tautan URL halaman spesifik di KZSTRM (misalnya: <code class="text-red-400 bg-black/50 px-1 py-0.5 rounded">https://streaming.kazeview.com/movie/...</code>) yang memuat konten yang bersangkutan.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">3</span>
                            <span><strong>Identitas & Informasi Kontak:</strong> Cantumkan nama lengkap Anda, perusahaan/organisasi, alamat fisik, nomor telepon yang dapat dihubungi, dan alamat email resmi Anda.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">4</span>
                            <span><strong>Pernyataan Itikad Baik (Good Faith):</strong> Cantumkan pernyataan bahwa Anda memiliki keyakinan dengan itikad baik bahwa penggunaan materi tersebut tidak diizinkan oleh pemilik hak cipta, agennya, maupun undang-undang.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">5</span>
                            <span><strong>Pernyataan di Bawah Sumpah (Under Penalty of Perjury):</strong> Pernyataan bahwa informasi dalam pemberitahuan adalah akurat dan, di bawah ancaman hukuman pemalsuan, Anda adalah pemilik hak cipta atau diberi wewenang sah untuk bertindak atas nama pemilik hak cipta.</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ENGLISH CONTENT --}}
            <div x-show="lang === 'en'" class="space-y-6 text-sm text-zinc-300 leading-relaxed" style="display: none;">
                <div class="flex items-center gap-3 pb-4 border-b border-zinc-800/80">
                    <span class="text-2xl">🇺🇸</span>
                    <div>
                        <h2 class="text-lg md:text-xl font-bold text-white">DMCA & Copyright Compliance Policy (English)</h2>
                        <p class="text-xs text-zinc-500">Official digital copyright notification & takedown guidelines</p>
                    </div>
                </div>

                <p>
                    <strong class="text-white">KZSTRM</strong> is an online service provider as defined under the <strong class="text-zinc-100">Digital Millennium Copyright Act (17 U.S.C. § 512)</strong>. We strongly respect copyright laws and will protect the rights of every legitimate copyright owner with high integrity and urgency.
                </p>

                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/25 text-amber-200 text-xs leading-relaxed space-y-1">
                    <p class="font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        Important Notice:
                    </p>
                    <p>
                        KZSTRM does not host, upload, or store any media streams or video files on its servers. All video links, embedded streams, and artwork are indexed or served directly from non-affiliated third-party platforms on the open web.
                    </p>
                </div>

                <p>
                    If you are the copyright holder of any content appearing on <strong>KZSTRM</strong> and you wish to disallow the indexing or referencing of that material, you are strongly encouraged to contact us with an official notice so that we can verify and take prompt action to disable or remove the reference links.
                </p>

                <div class="space-y-3 pt-2">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-600"></span>
                        Takedown Notice Requirements
                    </h3>
                    <p class="text-xs text-zinc-400">
                        To enable us to act upon your notice, please ensure your email includes the following verifiable details:
                    </p>

                    <ul class="space-y-2.5 pl-2 list-none">
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">1</span>
                            <span><strong>Copyrighted work identification:</strong> A specific description and title of the copyrighted work claimed to have been infringed.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">2</span>
                            <span><strong>Infringing URL locations:</strong> The exact web URLs on KZSTRM (e.g., <code class="text-red-400 bg-black/50 px-1 py-0.5 rounded">https://streaming.kazeview.com/movie/...</code>) containing the content claimed to be infringing.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">3</span>
                            <span><strong>Contact details:</strong> Your full legal name, company/organization name, physical address, direct phone number, and official email address.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">4</span>
                            <span><strong>Good Faith Statement:</strong> A statement that you have a good-faith belief that use of the disputed material is not authorized by the copyright owner, its agent, or the law.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 border border-zinc-700 text-zinc-300 text-xs flex items-center justify-center shrink-0 mt-0.5 font-bold">5</span>
                            <span><strong>Under Penalty of Perjury:</strong> A statement made under penalty of perjury that the information in the notification is accurate and that you are authorized to act on behalf of the copyright owner.</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Contact Box & Action Button --}}
            <div class="pt-6 border-t border-zinc-800/80 flex flex-col sm:flex-row items-center justify-between gap-5 bg-zinc-900/40 -mx-6 md:-mx-10 -mb-6 md:-mb-10 p-6 md:p-8 rounded-b-3xl">
                <div class="space-y-1 text-center sm:text-left">
                    <p class="text-xs uppercase font-bold tracking-wider text-red-500">Email Korespondensi DMCA</p>
                    <p class="text-base md:text-lg font-black text-white font-mono">dmca@kazeview.com</p>
                    <p class="text-xs text-zinc-400">Harap kirimkan dengan format lengkap agar dapat diproses secepatnya.</p>
                </div>

                <a href="mailto:dmca@kazeview.com?subject=DMCA%20Takedown%20Notice%20-%20KZSTRM&body=Dear%20KZSTRM%20DMCA%20Team%2C%0A%0AI%20am%20the%20copyright%20owner%20(or%20authorized%20representative)%20of%20the%20following%20work%3A%0A-%20Title%20of%20Work%3A%20%0A-%20Infringing%20URL(s)%20on%20KZSTRM%3A%20%0A%0AContact%20Information%3A%0A-%20Name%3A%20%0A-%20Company%2FOrganization%3A%20%0A-%20Address%3A%20%0A-%20Phone%3A%20%0A-%20Email%3A%20%0A%0AStatement%20of%20Authority%3A%0AI%20have%20a%20good%20faith%20belief%20that%20the%20use%20of%20the%20described%20material%20is%20not%20authorized%20by%20the%20copyright%20owner%2C%20its%20agent%2C%20or%20the%20law.%20The%20information%20provided%20in%20this%20notice%20is%20accurate%20under%20penalty%20of%20perjury."
                   class="px-6 py-3.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm shadow-xl shadow-red-600/30 transition active:scale-95 flex items-center gap-2 shrink-0">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                    <span>Kirim Email Takedown</span>
                </a>
            </div>
        </div>

        {{-- Footer Note --}}
        <div class="text-center text-xs text-zinc-500 pt-4">
            <p>KZSTRM Media Aggregator & Streaming Portal · Copyright © {{ date('Y') }} All Rights Reserved.</p>
        </div>
    </div>
</div>
