<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ], [
            'current_password.current_password' => 'كلمة المرور الحالية التي أدخلتها غير صحيحة.',
            'password.confirmed' => 'تأكيد كلمة المرور الجديدة غير متطابق.',
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }

    /**
     * تحديد اسم كومبوننت اللايوت حسب دور المستخدم الحالي.
     * الجرسون يشوف هيدر (الطلبات + الإعدادات)، وأي دور تاني يشوف هيدر لوحة التحكم الكامل.
     */
    public function getLayoutComponentProperty(): string
    {
        if (Auth::user()->role == 'admin') {
            return 'settings.layout';
        } elseif (Auth::user()->role == 'superadmin') {
            return 'settings.layout';
        } elseif (Auth::user()->role == 'waiter') {
            return 'waiter-layout';
        } elseif (Auth::user()->role == 'chief') {
            return 'chief-layout';
        } else {
            return 'app-layout';
        }
    }
}; ?>

<section class="w-full min-h-screen bg-[#0f1115] text-gray-100" dir="rtl">

    <!-- حيلة ذكية للتغلب على خلفية الـ body البيضاء -->
    <style>
        body {
            background-color: #0f1115 !important;
        }
    </style>

    <!-- 🔀 نفس الصفحة ونفس الراوت لكل الأدوار، والهيدر/السايدبار بس بيتغير تلقائياً حسب الدور -->
    <x-dynamic-component :component="$this->layoutComponent" heading="تغيير كلمة المرور"
        subheading="احرص على استخدام كلمة مرور قوية وحصرية لحماية حسابك من الاختراق">

        <form wire:submit="updatePassword" class="my-6 w-full space-y-6">

            <!-- كلمة المرور الحالية -->
            <div>
                <label for="current_password" class="block text-sm font-semibold text-gray-300 mb-2">
                    كلمة المرور الحالية
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-500">
                        🔒
                    </span>
                    <input id="current_password" wire:model="current_password" type="password" required
                        autocomplete="current-password" placeholder="••••••••"
                        class="w-full pr-11 pl-3 py-3 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/40 focus:border-[#d4af37] transition" />
                </div>
                @error('current_password')
                    <span class="text-xs text-red-400 mt-1.5 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <!-- كلمة المرور الجديدة -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-300 mb-2">
                    كلمة المرور الجديدة
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-500">
                        🔑
                    </span>
                    <input id="password" wire:model="password" type="password" required autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full pr-11 pl-3 py-3 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/40 focus:border-[#d4af37] transition" />
                </div>
                @error('password')
                    <span class="text-xs text-red-400 mt-1.5 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <!-- تأكيد كلمة المرور الجديدة -->
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-300 mb-2">
                    تأكيد كلمة المرور الجديدة
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-500">
                        🔄
                    </span>
                    <input id="password_confirmation" wire:model="password_confirmation" type="password" required
                        autocomplete="new-password" placeholder="••••••••"
                        class="w-full pr-11 pl-3 py-3 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/40 focus:border-[#d4af37] transition" />
                </div>
                @error('password_confirmation')
                    <span class="text-xs text-red-400 mt-1.5 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <!-- أزرار الإجراء وحالة الحفظ -->
            <div class="flex items-center gap-4 pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-bold rounded-xl hover:brightness-110 active:scale-[0.98] transition shadow-lg shadow-[#d4af37]/10 flex items-center justify-center gap-2">
                    <span>تحديث كلمة المرور</span>
                    <span class="text-sm">⚡</span>
                </button>

                <!-- رسالة نجاح الحفظ -->
                <x-action-message class="text-sm text-emerald-400 font-semibold flex items-center gap-1.5"
                    on="password-updated">
                    <span>✨ تم تغيير كلمة المرور بنجاح!</span>
                </x-action-message>
            </div>
        </form>

    </x-dynamic-component>
</section>