<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        // 1. جلب بيانات المستخدم الذي سجل دخوله للتو
        $user = Auth::user();

        // 2. تحديد المسار الافتراضي (مثلاً للمستخدم العادي أو الكاشير)
        $defaultRoute = route('login', absolute: false);

        // 3. التحقق من صلاحيات المستخدم وتحديد المسار الافتراضي
        if ($user && $user->role === 'admin'|| $user->role === 'superadmin') {
            // إذا كان مديرًا، يتم توجيهه إلى لوحة تحكم الأدمن
            $defaultRoute = route('admin.dashboard', absolute: false);
        } 
        elseif ($user && $user->role === 'waiter') {
            // إذا كان نادلًا، يتم توجيهه إلى صفحة الطلبات الخاصة بالنادل
            $defaultRoute = route('waiter.orders', absolute: false);
        }
        elseif( $user && $user->role === 'chief') {
            // مثال: إذا كان شيف (يمكنك تغيير المسمى أو حذف هذا الشرط إذا لم تحتاجه)
            $defaultRoute = route('chief.orders', absolute: false);
        }


        // 4. التوجيه الذكي باستخدام المسار الديناميكي المحدد
        $this->redirectIntended(default: $defaultRoute, navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

<div class="min-h-screen bg-[#0f1115] flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-12 select-none"
    dir="rtl">
    <div class="w-full max-w-md space-y-8">

        <!-- اللوجو وهوية الكافيه -->
        <div class="text-center">
            <div class="inline-flex items-center justify-center gap-2 mb-2">
                <div class="p-2.5 bg-[#d4af37]/10 rounded-2xl border border-[#d4af37]/20 shadow-inner">
                    <svg class="w-8 h-8 text-[#d4af37]" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                        <!-- أيقونة القهوة / الطاقة المميزة -->
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <h1
                class="text-3xl font-black bg-gradient-to-r from-[#d4af37] via-[#f3e5ab] to-[#b8912f] bg-clip-text text-transparent tracking-widest uppercase">
                Electro Cafe
            </h1>
            <h2 class="mt-3 text-xl font-bold text-gray-100 tracking-wide">
                مرحباً بك مجدداً! 👋
            </h2>
            <p class="mt-1 text-xs text-gray-400">
                يرجى تسجيل الدخول للوصول إلى لوحة التحكم
            </p>
        </div>

        <!-- كرت تسجيل الدخول -->
        <div
            class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl shadow-2xl p-8 relative overflow-hidden backdrop-blur-sm">
            <!-- إضاءات خلفية ديكورية -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-[#d4af37]/10 rounded-full blur-3xl pointer-events-none">
            </div>
            <div
                class="absolute -bottom-12 -left-12 w-32 h-32 bg-[#b8912f]/10 rounded-full blur-3xl pointer-events-none">
            </div>

            <!-- رسالة حالة الجلسة -->
            @if (session('status'))
                <x-auth-session-status class="mb-4 text-center text-sm text-emerald-400 font-medium"
                    :status="session('status')" />
            @endif

            <form wire:submit="login" class="space-y-6 relative z-10">

                <!-- حقل البريد الإلكتروني -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">
                        البريد الإلكتروني
                    </label>
                    <div class="relative">
                        <span
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-gray-500">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <input id="email" wire:model="email" type="email" required autofocus autocomplete="email"
                            placeholder="admin@electrocafe.com"
                            class="w-full pr-11 pl-4 py-3 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/40 focus:border-[#d4af37] transition text-left text-sm"
                            dir="ltr" />
                    </div>
                    @error('email')
                        <span class="text-xs text-red-400 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- حقل كلمة المرور (مع ميزة الإظهار/الإخفاء) -->
                <div x-data="{ showPassword: false }">
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-sm font-semibold text-gray-300">
                            كلمة المرور
                        </label>
                        
                    </div>
                    <div class="relative">
                        <!-- أيقونة القفل -->
                        <span
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-gray-500">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25z" />
                            </svg>
                        </span>

                        <input id="password" wire:model="password" :type="showPassword ? 'text' : 'password'" required
                            autocomplete="current-password" placeholder="••••••••"
                            class="w-full pr-11 pl-11 py-3 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/40 focus:border-[#d4af37] transition text-left text-sm"
                            dir="ltr" />

                        <!-- زر إظهار / إخفاء كلمة المرور -->
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-500 hover:text-gray-300 transition focus:outline-none">
                            <svg x-show="!showPassword" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="text-xs text-red-400 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- تذكرني -->
                <div class="flex items-center">
                    <input id="remember" type="checkbox" wire:model="remember"
                        class="w-4 h-4 text-[#d4af37] bg-[#22252d] border-[#2a2e38] rounded focus:ring-[#d4af37] focus:ring-offset-[#1a1d24] focus:ring-2 transition cursor-pointer" />
                    <label for="remember"
                        class="mr-2 text-sm text-gray-400 select-none cursor-pointer hover:text-gray-300">
                        تذكرني على هذا الجهاز
                    </label>
                </div>

                <!-- زر تسجيل الدخول التفاعلي -->
                <button type="submit" wire:loading.attr="disabled"
                    class="w-full py-3.5 bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-bold rounded-xl hover:brightness-110 active:scale-[0.99] transition shadow-lg shadow-[#d4af37]/15 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">

                    <!-- الحالة العادية -->
                    <span wire:loading.remove wire:target="login" class="flex items-center gap-2">
                        <span>تسجيل الدخول للنظام</span>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </span>

                    <!-- حالة التحميل -->
                    <span wire:loading wire:target="login" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>جاري التحقق...</span>
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>