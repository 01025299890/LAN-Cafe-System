@props(['heading', 'subheading'])

<div class="p-6 bg-[#0f1115] min-h-screen text-gray-100" dir="rtl">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- ☕ الهيدر الموحد (Navigation Bar) -->
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
                            <a href="{{ route('admin.employees-manager') }}"
                                class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">إدارة
                                الموظفين</a>
                            <a href="{{ route('admin.products-manager') }}"
                                class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">إدارة
                                المنتجات</a>
                            <a href="{{ route('admin.dashboard') }}"
                                class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">لوحة
                                التحكم</a>
                            <!-- تم إضافة رابط الإعدادات كنشط هنا -->
                            <a href="{{ route('settings.profile') }}"
                                class="text-[#d4af37] border-b-2 border-[#d4af37] pb-2 text-sm font-bold tracking-wide">الإعدادات</a>
                        </div>
                    </div>
                    <!-- الملف الشخصي السريع بالهيدر -->
                    <div class="flex items-center gap-4">
                        <div class="hidden md:flex items-center gap-3 border-r border-[#2a2e38] pr-5">
                            <div class="text-left pl-1">
                                <p class="text-sm font-semibold text-gray-200">{{ auth()->user()->name ?? 'مستخدم النظام' }}</p>
                                <p class="text-[11px] text-gray-500 font-medium text-right">
                                    {{ auth()->user()->role === 'superadmin' ? 'المالك / سوبر أدمن' : 'مدير النظام' }}
                                </p>
                            </div>
                            <div
                                class="w-10 h-10 rounded-xl bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-lg">
                                👨‍💻
                            </div>
                        </div>
                        <!-- زر القائمة للموبايل -->
                        <div class="flex md:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                                class="p-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-[#22252d] border border-transparent hover:border-[#2a2e38] transition">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }"
                                        class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- هيدر الموبايل -->
            <div x-show="mobileMenuOpen"
                class="md:hidden border-t border-[#2a2e38] bg-[#1a1d24] rounded-b-2xl overflow-hidden">
                <div class="px-3 pt-2 pb-4 space-y-1">
                    <a href="{{ route('admin.employees-manager') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]">إدارة
                        الموظفين</a>
                    <a href="{{ route('admin.products-manager') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]">إدارة
                     المنتجات</a>
                    <a href="{{ route('admin.dashboard') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]">لوحة
                        التحكم</a>
                    <a href="{{ route('settings.profile') }}"
                        class="block px-4 py-3 rounded-xl text-base font-bold text-[#d4af37] bg-[#22252d] border-r-4 border-[#d4af37]">الإعدادات</a>
                </div>
            </div>
        </nav>

        <!-- ⚙ محتوى صفحة الإعدادات -->
        <div class="flex flex-col lg:flex-row gap-8 min-h-[500px]">

            <!-- القائمة الجانبية للإعدادات -->
            <aside class="w-full lg:w-1/4">
                <nav class="space-y-1.5">
                    @php
$links = [
    ['route' => 'settings.profile', 'label' => '👤 الملف الشخصي'],
    ['route' => 'settings.password', 'label' => '🔑 كلمة المرور'],
];
                    @endphp

                    @foreach ($links as $link)
                        @php
    $isActive = request()->routeIs($link['route']);
                        @endphp
                        <a href="{{ route($link['route']) }}" wire:navigate
                            class="flex items-center px-4 py-3 text-sm font-bold rounded-xl transition-all duration-200 {{ $isActive ? 'bg-[#d4af37]/10 text-[#d4af37] border-r-4 border-[#d4af37]' : 'text-gray-400 hover:bg-[#1a1d24] hover:text-gray-200' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <!-- كرت المحتوى الرئيسي للإعدادات -->
            <div
                class="flex-1 bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-6 lg:p-8 relative overflow-hidden shadow-2xl">
                <!-- تأثير التوهج الخلفي الناعم -->
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-[#d4af37]/5 rounded-full blur-3xl pointer-events-none">
                </div>

                <!-- الهيدر الداخلي للقسم الحالي -->
                <div class="mb-6 border-b border-[#2a2e38] pb-6">
                    <h2 class="text-lg font-bold text-gray-100">{{ $heading }}</h2>
                    <p class="mt-1 text-xs text-gray-400">{{ $subheading }}</p>
                </div>

                <!-- مكان فورم الملف الشخصي أو كلمة المرور -->
                <div>
                    {{ $slot }}
                </div>
            </div>

        </div>

    </div>
</div>