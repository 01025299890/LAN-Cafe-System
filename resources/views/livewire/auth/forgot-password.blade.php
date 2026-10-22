<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('إذا كان الحساب مسجلاً لدينا، فسنرسل لك رابط إعادة التعيين فوراً.'));
    }
}; ?>

<div class="min-h-screen bg-[#0f1115] flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-12" dir="rtl">
    <div class="w-full max-w-md space-y-8">

        <!-- الهوية البصرية وشعار الكافيه -->
        <div class="text-center">
            <span
                class="text-3xl font-black bg-gradient-to-r from-[#d4af37] to-[#b8912f] bg-clip-text text-transparent tracking-widest">
                <span class="text-[#d4af37]">☕</span>  ELECTRO CAFE
            </span>
            <h2 class="mt-4 text-2xl font-bold text-gray-100 tracking-wide">
                نسيت كلمة المرور؟ 🔒
            </h2>
            <p class="mt-1.5 text-xs text-gray-400">
                أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيينها
            </p>
        </div>

        <!-- كرت استعادة الحساب المطور -->
        <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl shadow-2xl p-8 relative overflow-hidden">
            <!-- تأثير الإضاءة الخلفية الناعمة -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-[#d4af37]/5 rounded-full blur-3xl pointer-events-none">
            </div>
            <div
                class="absolute -bottom-12 -left-12 w-32 h-32 bg-[#b8912f]/5 rounded-full blur-3xl pointer-events-none">
            </div>

            <!-- رسالة حالة الجلسة (نجاح الإرسال) -->
            @if (session('status'))
                <div
                    class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-center text-xs font-semibold">
                    {{ session('status') }}
                </div>
            @endif

            <form wire:submit="sendPasswordResetLink" class="space-y-6">

                <!-- حقل البريد الإلكتروني -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">
                        البريد الإلكتروني المسجل
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-500">
                            📧
                        </span>
                        <input id="email" wire:model="email" type="email" required autofocus
                            placeholder="email@example.com"
                            class="w-full pr-11 pl-3 py-3 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/40 focus:border-[#d4af37] transition text-left"
                            dir="ltr" />
                    </div>
                    @error('email')
                        <span class="text-xs text-red-400 mt-1.5 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <!-- زر الإرسال المطور بالجرادينت الفخم -->
                <button type="submit"
                    class="w-full py-3 bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-bold rounded-xl hover:brightness-110 active:scale-[0.98] transition shadow-lg shadow-[#d4af37]/10 flex items-center justify-center gap-2">
                    <span>إرسال رابط إعادة التعيين</span>
                    <span class="text-sm">📧</span>
                </button>
            </form>
        </div>

        <!-- الفوتر للعودة للخلف -->
        <div class="text-center text-xs text-gray-500">
            أو،
            <a href="{{ route('login') }}" class="text-[#d4af37] hover:underline font-bold transition">
                العودة لصفحة تسجيل الدخول
            </a>
        </div>
    </div>
</div>