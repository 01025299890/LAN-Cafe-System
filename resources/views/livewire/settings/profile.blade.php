<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        // نقوم بالتحقق من الاسم فقط وحفظه
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->fill($validated);
        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * تحديد اسم الكومبوننت الخاص باللايوت حسب دور المستخدم الحالي.
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
            return 'layouts.app';
        }
    }
}; ?>

<section class="w-full min-h-screen bg-[#0f1115] text-gray-100" dir="rtl">

    <!-- 💡 حيلة ذكية للتغلب على خلفية الـ body البيضاء في layouts.app دون التأثير على باقي صفحات الموقع -->
    <style>
        body {
            background-color: #0f1115 !important;
        }
    </style>

    <!-- 🔀 نفس الصفحة ونفس الراوت لكل الأدوار، والهيدر/السايدبار بس بيتغير تلقائياً حسب الدور -->
    <x-dynamic-component :component="$this->layoutComponent" heading="الملف الشخصي"
        subheading="قم بتحديث اسم الحساب الخاص بك">

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">

            <!-- حقل الاسم الكامل -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                    الاسم الكامل
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-500">
                        👤
                    </span>
                    <input id="name" wire:model="name" type="text" required autofocus autocomplete="name"
                        placeholder="أدخل الاسم الكامل"
                        class="w-full pr-11 pl-3 py-3 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-[#d4af37]/40 focus:border-[#d4af37] transition" />
                </div>
                @error('name')
                    <span class="text-xs text-red-400 mt-1.5 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <!-- حقل البريد الإلكتروني (مغلق ومحمي بالكامل) -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-400 mb-2">
                    البريد الإلكتروني (لا يمكن تغييره 🔒)
                </label>
                <div class="relative opacity-60">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-600">
                        📧
                    </span>
                    <input id="email" wire:model="email" type="email" disabled readonly
                        class="w-full pr-11 pl-3 py-3 bg-[#17191e] border border-[#23272e] rounded-xl text-gray-500 cursor-not-allowed text-left font-medium"
                        dir="ltr" />
                </div>
                <p class="mt-2 text-[11px] text-gray-500">
                    * هذا البريد الإلكتروني مرتبط بصلاحياتك وحسابك في النظام مباشرة، لمراجعته يرجى الرجوع لـ Super
                    Admin.
                </p>
            </div>

            <!-- أزرار الإجراء وحالة الحفظ -->
            <div class="flex items-center gap-4 pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-bold rounded-xl hover:brightness-110 active:scale-[0.98] transition shadow-lg shadow-[#d4af37]/10 flex items-center justify-center gap-2">
                    <span>حفظ الاسم الجديد</span>
                    <span class="text-sm">💾</span>
                </button>

                <!-- رسالة نجاح الحفظ -->
                <x-action-message class="text-sm text-emerald-400 font-semibold flex items-center gap-1.5"
                    on="profile-updated">
                    <span>✨ تم تحديث الاسم بنجاح!</span>
                </x-action-message>
            </div>
        </form>

    </x-dynamic-component>
</section>