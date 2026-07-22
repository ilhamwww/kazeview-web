<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil - KAZEVIEW</title>
    @vite('resources/css/app.css')
    <style>
        body {
            background-color: #000000 !important;
        }
    </style>
</head>
<body class="min-h-screen text-white flex items-center justify-center px-6 py-12 md:px-16 max-w-[1600px] mx-auto">
    <div class="w-full max-w-[520px] bg-zinc-900 border border-zinc-800 rounded-[20px] p-8 md:p-10 shadow-2xl text-center space-y-8">
        <!-- Logo IDLIX -->
        <div class="flex flex-col items-center gap-3.5">
            <div class="w-16 h-16 rounded-2xl bg-red-600/10 flex items-center justify-center mb-2">
                <svg class="w-8 h-8 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="2" width="20" height="20" rx="4" />
                    <path d="M7 2v20M17 2v20M2 7h5M2 17h5M17 7h5M17 17h5M7 12h10" />
                </svg>
            </div>
            <span class="text-3xl font-black tracking-tighter text-red-600">KAZEVIEW</span>
        </div>

        <!-- Message -->
        <div class="space-y-3">
            <h2 class="text-2xl font-bold text-white tracking-tight">Pendaftaran Berhasil!</h2>
            <p class="text-sm text-zinc-400 leading-relaxed font-light">
                Akun Anda berhasil didaftarkan. Silakan tunggu persetujuan/konfirmasi dari admin sebelum Anda dapat login.
            </p>
        </div>

        <!-- Action Button -->
        <div>
            <a 
                href="{{ filament()->getLoginUrl() }}"
                style="background-color: #dc2626;"
                class="inline-block w-full py-4 hover:bg-red-700 active:scale-[0.98] text-white text-sm font-bold rounded-lg transition duration-150 tracking-wide shadow-lg text-center"
            >
                Kembali ke Halaman Login
            </a>
        </div>
    </div>
</body>
</html>