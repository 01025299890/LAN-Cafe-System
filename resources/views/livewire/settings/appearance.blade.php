<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout; // 👈 استدعاء الـ Layout المخصص

new #[Layout('layouts.app')] class extends Component { // 👈 ربط الـ Layout الفخم مباشرة
    //
}; ?>

<section class="w-full" dir="rtl">
    @include('partials.settings-heading')

    <x-settings.layout heading="تخصيص المظهر" subheading="اختر نمط الألوان المفضل لواجهة لوحة تحكم Electro Cafe">

        <!-- كروت اختيار المظهر الذكية باستخدام Alpine.js لتتوافق مع Flux أو نظام ألوان الـ Tailwind الافتراضي -->
        <div x-data="{
            get appearance() {
                return (typeof $flux !== 'undefined' && $flux.appearance) ? $flux.appearance : (localStorage.theme || 'dark');
            },
            set appearance(value) {
                if (typeof $flux !== 'undefined' && $flux.appearance) {
                    $flux.appearance = value;
                } else {
                    localStorage.theme = value;
                    if (value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }
        }" class="grid grid-cols-1 sm:grid-cols-3 gap-4 my-6 w-full">

            <!-- خيار المظهر المظلم (الافتراضي والموصى به) -->
            <button type="button" @click="appearance = 'dark'"
                :class="appearance === 'dark' ? 'border-[#d4af37] bg-[#d4af37]/10 text-[#d4af37] shadow-lg shadow-[#d4af37]/5' : 'border-[#2a2e38] bg-[#22252d] text-gray-400 hover:border-gray-600 hover:text-gray-200'"
                class="flex flex-col items-center justify-center p-6 border-2 rounded-2xl transition-all duration-300 cursor-pointer group">
                <span class="text-3xl mb-3 group-hover:scale-110 transition duration-300">🌙</span>
                <span class="font-bold text-sm">مظهر مظلم (ليلي)</span>
                <span class="text-[10px] text-gray-500 mt-1.5 font-medium">فخم ومريح للعين ☕</span>
            </button>

            <!-- خيار المظهر المضيء -->
            <button type="button" @click="appearance = 'light'"
                :class="appearance === 'light' ? 'border-[#d4af37] bg-[#d4af37]/10 text-[#d4af37] shadow-lg shadow-[#d4af37]/5' : 'border-[#2a2e38] bg-[#22252d] text-gray-400 hover:border-gray-600 hover:text-gray-200'"
                class="flex flex-col items-center justify-center p-6 border-2 rounded-2xl transition-all duration-300 cursor-pointer group">
                <span class="text-3xl mb-3 group-hover:scale-110 transition duration-300">☀️</span>
                <span class="font-bold text-sm">مظهر مضيء (نهاري)</span>
                <span class="text-[10px] text-gray-500 mt-1.5 font-medium">واضح في الإضاءة المرتفعة</span>
            </button>

            <!-- خيار مظهر النظام التلقائي -->
            <button type="button" @click="appearance = 'system'"
                :class="appearance === 'system' ? 'border-[#d4af37] bg-[#d4af37]/10 text-[#d4af37] shadow-lg shadow-[#d4af37]/5' : 'border-[#2a2e38] bg-[#22252d] text-gray-400 hover:border-gray-600 hover:text-gray-200'"
                class="flex flex-col items-center justify-center p-6 border-2 rounded-2xl transition-all duration-300 cursor-pointer group">
                <span class="text-3xl mb-3 group-hover:scale-110 transition duration-300">💻</span>
                <span class="font-bold text-sm">تلقائي (حسب النظام)</span>
                <span class="text-[10px] text-gray-500 mt-1.5 font-medium">يتبع إعدادات جهازك الحالية</span>
            </button>

        </div>
    </x-settings.layout>
</section>