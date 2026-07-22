<div class="min-h-screen bg-black text-white flex items-center justify-center lg:justify-between px-6 py-12 md:px-16 lg:px-24 xl:px-32 max-w-[1600px] mx-auto gap-12">
    @vite('resources/css/app.css')
    <style>
        /* Force body background to be completely black */
        body {
            background-color: #000000 !important;
            background-image: none !important;
        }
        /* Hide default Filament simple page background/card styles if they leak */
        .fi-simple-layout {
            background: #000000 !important;
        }
        .fi-simple-card {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
    </style>

    <!-- Left Column: Promo / Branding -->
    <div class="hidden lg:flex flex-col text-left space-y-8 max-w-xl w-1/2">
        <!-- Logo IDLIX -->
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-red-600/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="2" width="20" height="20" rx="4" />
                    <path d="M7 2v20M17 2v20M2 7h5M2 17h5M17 7h5M17 17h5M7 12h10" />
                </svg>
            </div>
            <span class="text-4xl font-black tracking-tighter text-red-600">KAZEVIEW</span>
        </div>

        <!-- Headline -->
        <div class="space-y-4">
            <h1 class="text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-[1.1]">
                Join the<br>streaming revolution.
            </h1>
        </div>

        <!-- Description -->
        <p class="text-zinc-400 text-base leading-relaxed max-w-md font-light">
            Create an account to track your watchlist, save favorites, and enjoy thousands of movies and series.
        </p>
    </div>

    <!-- Right Column: Register Card -->
    <div class="w-full lg:w-[560px] flex justify-center lg:justify-end">
        <div class="w-full max-w-[520px] bg-zinc-900 border border-zinc-800 rounded-[20px] p-8 md:p-10 shadow-2xl">
            <!-- Header Card -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white tracking-tight">Create an account</h2>
                <p class="text-sm text-zinc-400 mt-2">Sign up to get started.</p>
            </div>

            <!-- Form -->
            <form wire:submit.prevent="register" class="space-y-6">
                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-[11px] font-bold text-zinc-400 tracking-wider uppercase mb-2">
                        NAME
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        wire:model="data.name" 
                        required
                        autofocus
                        class="w-full bg-zinc-950 border border-zinc-700 focus:border-red-600 focus:ring-1 focus:ring-red-600 rounded-lg px-4 py-3.5 text-sm text-white placeholder-zinc-600 focus:outline-none transition duration-150"
                        placeholder="John Doe"
                    >
                    @error('data.name')
                        <span class="text-xs text-red-500 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-[11px] font-bold text-zinc-400 tracking-wider uppercase mb-2">
                        EMAIL ADDRESS
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        wire:model="data.email" 
                        required
                        class="w-full bg-zinc-950 border border-zinc-700 focus:border-red-600 focus:ring-1 focus:ring-red-600 rounded-lg px-4 py-3.5 text-sm text-white placeholder-zinc-600 focus:outline-none transition duration-150"
                        placeholder="you@example.com"
                    >
                    @error('data.email')
                        <span class="text-xs text-red-500 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div x-data="{ show: false }">
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-[11px] font-bold text-zinc-400 tracking-wider uppercase">
                            PASSWORD
                        </label>
                    </div>
                    <div class="relative">
                        <input 
                            :type="show ? 'text' : 'password'" 
                            id="password" 
                            wire:model="data.password" 
                            required
                            class="w-full bg-zinc-950 border border-zinc-700 focus:border-red-600 focus:ring-1 focus:ring-red-600 rounded-lg pl-4 pr-10 py-3.5 text-sm text-white placeholder-zinc-600 focus:outline-none transition duration-150"
                            placeholder=""
                        >
                        <!-- Password Visibility Toggle Icon -->
                        <button 
                            type="button" 
                            @click="show = !show" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-zinc-500 hover:text-zinc-300 transition-colors"
                            aria-label="Toggle password visibility"
                        >
                            <!-- Eye icon -->
                            <svg x-show="!show" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Eye off icon -->
                            <svg x-show="show" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('data.password')
                        <span class="text-xs text-red-500 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div x-data="{ show: false }">
                    <div class="flex items-center justify-between mb-2">
                        <label for="passwordConfirmation" class="block text-[11px] font-bold text-zinc-400 tracking-wider uppercase">
                            CONFIRM PASSWORD
                        </label>
                    </div>
                    <div class="relative">
                        <input 
                            :type="show ? 'text' : 'password'" 
                            id="passwordConfirmation" 
                            wire:model="data.passwordConfirmation" 
                            required
                            class="w-full bg-zinc-950 border border-zinc-700 focus:border-red-600 focus:ring-1 focus:ring-red-600 rounded-lg pl-4 pr-10 py-3.5 text-sm text-white placeholder-zinc-600 focus:outline-none transition duration-150"
                            placeholder=""
                        >
                        <!-- Password Visibility Toggle Icon -->
                        <button 
                            type="button" 
                            @click="show = !show" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-zinc-500 hover:text-zinc-300 transition-colors"
                            aria-label="Toggle password visibility"
                        >
                            <!-- Eye icon -->
                            <svg x-show="!show" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Eye off icon -->
                            <svg x-show="show" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('data.passwordConfirmation')
                        <span class="text-xs text-red-500 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    style="background-color: #dc2626;"
                    class="w-full py-4 mt-2 hover:bg-red-700 active:scale-[0.98] text-white text-sm font-bold rounded-lg transition duration-150 tracking-wide shadow-lg flex items-center justify-center"
                >
                    <span wire:loading.remove wire:target="register">Create Account</span>
                    <span wire:loading wire:target="register" class="inline-flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creating account...
                    </span>
                </button>
            </form>
            
            <!-- Login Link -->
            <div class="mt-8 pt-6 border-t border-zinc-800 text-center">
                <p class="text-sm text-zinc-400">
                    Already have an account? <a href="{{ filament()->getLoginUrl() }}" class="text-red-500 hover:text-red-400 font-medium transition duration-150">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>