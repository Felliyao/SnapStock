<x-guest-layout>
    <div class="flex min-h-0 flex-1 flex-col p-2 sm:p-4">
        <!-- Container Utama (Split Screen) -->
        <div class="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col overflow-hidden rounded-lg bg-white shadow-md lg:flex-row">

            <!-- Sisi Kiri: Form Login -->
            <div class="flex min-h-0 w-full flex-col justify-between px-6 py-5 sm:px-10 lg:w-1/2 lg:px-14 lg:py-8">
                <div class="my-auto py-2">
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-900">Welcome back</h2>  

                    <!-- Session Status -->
                    <x-auth-session-status class="mb-3" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="space-y-3">
                        @csrf

                        <!-- Email Address -->
                        <div>
                            <x-input-label for="email" :value="__('Email')" class="text-slate-700" />
                            <x-text-input id="email" class="block mt-1 w-full px-3.5 py-2 text-sm border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 rounded-lg" type="email" name="email" :value="old('email')" placeholder="Enter your email" required autofocus autocomplete="username" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div>
                            <x-input-label for="password" :value="__('Password')" class="text-slate-700" />
                            <x-text-input id="password" class="block mt-1 w-full px-3.5 py-2 text-sm border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 rounded-lg"
                                            type="password"
                                            name="password"
                                            placeholder="••••••••"
                                            required autocomplete="current-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between text-sm">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" name="remember">
                                <span class="ms-2 text-slate-600">{{ __('Remember me') }}</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a class="text-sm text-emerald-600 hover:text-emerald-700 font-medium hover:underline focus:outline-none" href="{{ route('password.request') }}">
                                    {{ __('Forgot password?') }}
                                </a>
                            @endif
                        </div>

                        <!-- Button Log in -->
                        <div class="pt-2">
                            <button type="submit" class="w-full py-2 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition duration-200 shadow-sm flex items-center justify-center">
                                {{ __('Log in') }}
                            </button>
                        </div>
                    </form>

                    <!-- Footer Link Register -->
                    <p class="text-center text-sm text-slate-500 mt-4">
                        Don't have an account? 
                        <a href="{{ route('register') }}" class="text-emerald-600 font-semibold hover:underline">Sign up</a>
                    </p>
                </div>

                <div class="shrink-0 text-xs text-slate-400">
                    © 2077 All rights reserved.
                </div>
            </div>

            <!-- Sisi Kanan: Welcome -->
            <div class="hidden lg:flex w-1/2 flex-col items-center justify-center bg-emerald-700 px-10 text-white relative overflow-hidden">
                <div class="absolute -top-16 -right-16 w-56 h-56 bg-emerald-600/40 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -left-10 w-64 h-64 bg-emerald-800/50 rounded-full blur-2xl"></div>

                <div class="relative flex flex-col items-center text-center">
                    <span class="flex items-center justify-center w-14 h-14 rounded-lg bg-white/15 backdrop-blur mb-6">
                        <x-heroicon-o-sparkles class="h-7 w-7" aria-hidden="true" />
                    </span>
                    <h1 class="text-3xl font-bold tracking-tight">SnapStock</h1>
                    <p class="mt-3 text-emerald-100/90 text-sm leading-relaxed max-w-xs">
                        Kelola stok produk, bahan baku, dan transaksi kasir dalam satu tempat.
                    </p>

                    <ul class="mt-8 space-y-3 text-sm text-emerald-50/90">
                        <li class="flex items-center gap-2.5">
                            <x-heroicon-o-shopping-cart class="h-4 w-4 text-emerald-200" aria-hidden="true" />
                            Kasir &amp; transaksi penjualan
                        </li>
                        <li class="flex items-center gap-2.5">
                            <x-heroicon-o-archive-box class="h-4 w-4 text-emerald-200" aria-hidden="true" />
                            Katalog produk &amp; stok real-time
                        </li>
                        <li class="flex items-center gap-2.5">
                            <x-heroicon-o-camera class="h-4 w-4 text-emerald-200" aria-hidden="true" />
                            Input katalog dibantu AI
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-guest-layout>