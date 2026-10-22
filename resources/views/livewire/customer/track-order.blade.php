<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Order;

new class extends Component {
    public $showcaseProducts;
    public $preparingOrders;
    public $readyOrders;

    public function mount()
    {
        $this->loadData();
    }

    // جلب البيانات الحية من قاعدة البيانات
    public function loadData()
    {
        $this->showcaseProducts = Product::all();
        $this->preparingOrders = Order::where('status', 'pending')->orWhere('status', 'preparing')->get();
        $this->readyOrders = Order::where('status', 'ready')->orderBy('updated_at', 'desc')->get();
    }
}; ?>

<div wire:poll.5s="loadData" x-data="{ 
        currentIndex: 0, 
        totalSlides: {{ count($showcaseProducts) }},
        init() {
            // تدوير تلقائي وسلس للمنتجات كل 5 ثوانٍ بداخل المتصفح مباشرة دون إرهاق السيرفر
            setInterval(() => {
                if (this.totalSlides > 0) {
                    this.currentIndex = (this.currentIndex + 1) % this.totalSlides;
                }
            }, 5000);
        }
     }" class="w-full min-h-screen bg-[#090b0f] text-gray-100 p-4 sm:p-6 flex flex-col justify-between" dir="rtl">

    <!-- 1. الهيدر العلوي الموحد (نفس نظام المنيو وبدون تسجيل دخول) -->
    <nav x-data="{ mobileMenuOpen: false }"
        class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl shadow-lg mb-6 w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">

                <!-- اليمين: اللوجو والروابط الأساسية -->
                <div class="flex items-center gap-10">
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <span
                            class="text-xl font-black bg-gradient-to-r from-[#d4af37] to-[#b8912f] bg-clip-text text-transparent tracking-wide">
                            <span class="text-[#d4af37]">☕</span> ELECTRO CAFE
                        </span>
                    </div>

                    <!-- روابط الـ Desktop (رابط المتابعة هو النشط هنا) -->
                    <div class="hidden md:flex items-center gap-8">
                        <a href="{{ route('customer.menu') }}" class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">المنيو
                        </a>

                        <a href="{{ route('customer.track-order') }}"
                            class="text-[#d4af37] border-b-2 border-[#d4af37] pb-2 text-sm font-bold tracking-wide flex items-center gap-1.5">
                            <span>🔍</span> متابعة الأوردر
                        </a>

                        <!-- <a href="/about" class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">عن
                            الكافيه</a> -->
                    </div>
                </div>

                <!-- اليسار: زر الموبايل فقط وعنوان اللوحة للشاشات الكبيرة -->
                <div class="flex items-center gap-4">
                    <div class="hidden lg:block text-left pl-2">
                        <span
                            class="text-xs font-bold px-3 py-1.5 bg-[#d4af37]/15 text-[#d4af37] border border-[#d4af37]/20 rounded-xl animate-pulse">
                            تابع الأوردر
                        </span>
                    </div>

                    <!-- زر القائمة للموبايل -->
                    <div class="flex md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                            class="inline-flex items-center justify-center p-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-[#22252d] border border-[#2a2e38] focus:outline-none transition">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }"
                                    class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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

        <!-- قائمة الموبايل المنسدلة -->
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="md:hidden border-t border-[#2a2e38] bg-[#1a1d24] rounded-b-2xl overflow-hidden"
            style="display: none;">
            <div class="px-3 pt-2 pb-4 space-y-1">
                <a href="{{ route('customer.menu') }}"
                    class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]">المنيو
                </a>
                <a href="{{ route('customer.track-order') }}"
                    class="block px-4 py-3 rounded-xl text-base font-bold text-[#d4af37] bg-[#22252d] border-r-4 border-[#d4af37]">🔍
                    متابعة الأوردر</a>
                <!-- <a href="/about"
                    class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]">عن
                    الكافيه</a> -->
            </div>
        </div>
    </nav>

    <!-- 2. لوحة العرض الرئيسية -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1 h-auto lg:h-[calc(100vh-160px)]">

        <!-- القسم الأيمن: معرض المنتجات المميزة -->
        <div
            class="grid-cols-1 lg:col-span-5 bg-[#11141c] border border-[#1f2430] rounded-2xl p-4 sm:p-6 flex flex-col justify-between shadow-2xl relative overflow-hidden group h-[420px] sm:h-[480px] lg:h-full">
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-[#d4af37]/5 rounded-full blur-3xl pointer-events-none">
            </div>

            <div class="relative flex-1 flex items-center justify-center">
                @if(count($showcaseProducts) > 0)
                    @foreach($showcaseProducts as $index => $product)
                        <div x-show="currentIndex === {{ $index }}"
                            x-transition:enter="transition ease-out duration-500 absolute"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-300 absolute"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                            class="text-center w-full flex flex-col items-center justify-center">

                            <div class="relative mb-4 sm:mb-6">
                                <img src="{{ asset('storage/' . $product->image_path) }}"
                                    class="w-48 h-48 sm:w-60 sm:h-60 lg:w-72 lg:h-72 object-cover rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.6)] border border-[#242a36] transform group-hover:scale-105 transition duration-500"
                                    alt="{{ $product->name }}">
                            </div>

                            <h2 class="text-xl sm:text-2xl font-black text-white mb-1 sm:mb-2 tracking-wide">
                                {{ $product->name }}</h2>
                            <p class="text-xs sm:text-sm text-gray-400 max-w-sm line-clamp-2 leading-relaxed mb-3 sm:mb-4 px-2">
                                {{ $product->description }}</p>

                            <span
                                class="px-4 py-1.5 sm:px-5 sm:py-2 bg-[#d4af37]/10 text-[#d4af37] border border-[#d4af37]/20 rounded-xl text-lg sm:text-xl font-extrabold tracking-tight">
                                {{ number_format($product->price, 2) }} <span class="text-xs font-normal">EGP</span>
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-gray-600">
                        <span class="text-5xl block mb-2">🍽️</span>
                        <p class="text-sm">لا توجد منتجات مضافة حالياً</p>
                    </div>
                @endif
            </div>

            @if(count($showcaseProducts) > 1)
                <div class="flex justify-center gap-2 mt-4 z-20">
                    @foreach($showcaseProducts as $index => $prod)
                        <button @click="currentIndex = {{ $index }}" class="h-1.5 rounded-full transition-all duration-300"
                            :class="currentIndex === {{ $index }} ? 'bg-[#d4af37] w-6' : 'bg-gray-700 w-2 hover:bg-gray-500'"></button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- القسم الأيسر: جدول الطلبات الحية -->
        <div class="lg:col-span-7 flex flex-col gap-6 h-auto lg:h-full">

            <!-- عنوان حالة الطلبات للموبايل -->
            <div class="block lg:hidden text-right mb-1 px-1">
                <h2 class="text-lg font-bold text-gray-200 leading-tight">حالة الطلبات الحالية</h2>
            </div>

            <!-- الجزء الأول: طلبات قيد التحضير -->
            <div
                class="bg-[#11141c] border border-[#1f2430] rounded-2xl p-4 sm:p-5 flex flex-col shadow-xl overflow-hidden h-[320px] lg:h-1/2">
                <div class="flex justify-between items-center mb-4 border-b border-[#1f2430] pb-2">
                    <h3 class="flex items-center gap-2 text-[#29b6f6] font-black text-base sm:text-lg">
                        <span class="inline-block w-2.5 h-2.5 bg-[#29b6f6] rounded-full animate-ping"></span>
                        ⏳ قيد التحضير
                    </h3>
                    <span
                        class="text-[10px] sm:text-xs font-bold px-2.5 py-1 bg-[#29b6f6]/10 text-[#29b6f6] rounded-md border border-[#29b6f6]/20">
                        {{ count($preparingOrders) }} طلبات بالانتظار
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                    @if(count($preparingOrders) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($preparingOrders as $order)
                                <div wire:key="prep-{{ $order->id }}"
                                    class="flex justify-between items-center bg-[#161a24] hover:bg-[#1c212e] p-3 sm:p-4 rounded-xl border border-[#1f2430] transition shadow-md group">
                                    <div class="flex flex-col text-right">
                                        <span
                                            class="font-extrabold text-base sm:text-lg text-[#29b6f6] group-hover:translate-x-1 transition duration-300">طلب
                                            #{{ $order->id }}</span>
                                        <span class="text-[9px] sm:text-[10px] text-gray-500">منذ
                                            {{ $order->created_at ? $order->created_at->diffForHumans(null, true) : 'قليل' }}</span>
                                    </div>
                                    <span
                                        class="text-xs font-black bg-[#29b6f6]/10 text-white border border-[#1f2430] px-2.5 py-1.5 rounded-lg">
                                        طاولة {{ $order->table_number }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-full flex flex-col justify-center items-center text-gray-600 py-6">
                            <span class="text-2xl block mb-1">✨</span>
                            <p class="text-xs sm:text-sm">كل طلبات الزبائن تم إنجازها!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- الجزء الثاني: طلبات جاهزة للاستلام -->
            <div
                class="bg-[#11141c] border border-[#1f2430] rounded-2xl p-4 sm:p-5 flex flex-col shadow-xl overflow-hidden h-[320px] lg:h-1/2">
                <div class="flex justify-between items-center mb-4 border-b border-[#1f2430] pb-2">
                    <h3 class="flex items-center gap-2 text-[#66bb6a] font-black text-base sm:text-lg">
                        <span class="inline-block w-2.5 h-2.5 bg-[#66bb6a] rounded-full animate-pulse"></span>
                        ✅ جاهز للاستلام
                    </h3>
                    <span
                        class="text-[10px] sm:text-xs font-bold px-2.5 py-1 bg-[#66bb6a]/10 text-[#66bb6a] rounded-md border border-[#66bb6a]/20">
                        {{ count($readyOrders) }} جاهز الآن
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                    @if(count($readyOrders) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($readyOrders as $order)
                                <div wire:key="ready-{{ $order->id }}"
                                    class="flex justify-between items-center bg-[#161a24] hover:bg-[#1a231c] p-3 sm:p-4 rounded-xl border border-[#1f2430] border-l-4 border-l-[#66bb6a] transition shadow-md group">
                                    <div class="flex flex-col text-right">
                                        <span
                                            class="font-extrabold text-lg sm:text-xl text-[#66bb6a] group-hover:translate-x-1 transition duration-300">طلب
                                            #{{ $order->id }}</span>
                                        <span class="text-[9px] sm:text-[10px] text-emerald-400 font-medium animate-pulse">جاهز
                                            للاستلام</span>
                                    </div>
                                    <span
                                        class="text-xs sm:text-sm font-black bg-[#66bb6a]/20 text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg shadow-md">
                                        طاولة {{ $order->table_number }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-full flex flex-col justify-center items-center text-gray-600 py-6">
                            <span class="text-2xl block mb-1">🛎️</span>
                            <p class="text-xs sm:text-sm">في انتظار خروج الوجبات الساخنة والمشروبات...</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>