@props(['heading', 'subheading'])

<div class="p-6 bg-[#0f1115] min-h-screen text-gray-100" dir="rtl">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- ☕ هيدر الجرسون (Waiter Navbar) -->
        <nav x-data="{ mobileMenuOpen: false }" class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <div class="flex items-center gap-10">
                        <!-- الشعار -->
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <span
                                class="text-xl font-black bg-gradient-to-r from-[#d4af37] to-[#b8912f] bg-clip-text text-transparent tracking-wide">
                                <span class="text-[#d4af37]">☕</span> ELECTRO CAFE
                            </span>
                        </div>

                        <!-- الروابط الأساسية -->
                        <div class="hidden md:flex items-center gap-8">
                            <a href="{{ route('waiter.orders') }}"
                                class="{{ request()->routeIs('waiter.orders*') ? 'text-[#d4af37] border-b-2 border-[#d4af37] pb-2 text-sm font-bold' : 'text-gray-400 hover:text-[#d4af37] transition text-sm font-medium' }}">
                                الطلبات
                            </a>
                            <a href="{{ route('settings.profile') }}"
                                class="{{ request()->routeIs('settings.*') ? 'text-[#d4af37] border-b-2 border-[#d4af37] pb-2 text-sm font-bold' : 'text-gray-400 hover:text-[#d4af37] transition text-sm font-medium' }}">
                                الإعدادات
                            </a>
                        </div>
                    </div>

                    <!-- الملف الشخصي -->
                    <div class="flex items-center gap-4">
                        <div class="hidden md:flex items-center gap-3 border-r border-[#2a2e38] pr-5">
                            <div class="text-left pl-1">
                                <p class="text-sm font-semibold text-gray-200">{{ auth()->user()->name ?? 'جرسون' }}</p>
                                <p class="text-[11px] text-gray-500 font-medium text-right">جرسون / طاقم العمل</p>
                            </div>
                            <div
                                class="w-10 h-10 rounded-xl bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-lg">
                                👨‍🍳
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- المحتوى الرئيسي -->
        <div class="flex flex-col lg:flex-row gap-8 min-h-[500px]">

            <!-- القائمة الجانبية -->
            <aside class="w-full lg:w-1/4">
                <nav class="space-y-1.5">
                    @php
                        $links = [
                            ['route' => 'waiter.orders', 'label' => '🍔 الطلبات الحالية'],
                            // ['route' => 'waiter.history', 'label' => '📜 سجل الطلبات'],
                            ['route' => 'settings.profile', 'label' => '👤 الملف الشخصي'],
                            ['route' => 'settings.password', 'label' => '🔑 كلمة المرور'],
                        ];
                    @endphp

                    @foreach ($links as $link)
                        @php
                            $isActive = request()->routeIs($link['route'] . '*');
                        @endphp
                        <a href="{{ route($link['route']) }}" wire:navigate
                            class="flex items-center px-4 py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ $isActive ? 'bg-[#d4af37]/10 text-[#d4af37] border-r-4 border-[#d4af37]' : 'text-gray-400 hover:bg-[#1a1d24] hover:text-gray-200' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <!-- الكرت الرئيسي -->
            <div class="flex-1 bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-6 lg:p-8 shadow-2xl">
                <div class="mb-6 border-b border-[#2a2e38] pb-6">
                    <h2 class="text-lg font-bold text-gray-100">{{ $heading }}</h2>
                    <p class="mt-1 text-xs text-gray-400">{{ $subheading }}</p>
                </div>
                <div>
                    {{ $slot }}
                </div>
            </div>

        </div>
    </div>
</div>