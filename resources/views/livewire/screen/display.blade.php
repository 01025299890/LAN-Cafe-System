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

{{-- ✨ تم ترك الـ wire:poll هنا لتحديث الطلبات فقط، وحذفنا الـ x-data من هنا لمنع تصفير السلايدر --}}
<div wire:poll.5s="loadData"
    class="w-full min-h-screen bg-[#090b0f] text-gray-100 p-4 md:p-8 flex flex-col justify-between" dir="rtl">

    <!-- 1. الهيدر العلوي -->
    <div class="flex flex-row items-center justify-between border-b border-[#1f2430] pb-4 mb-6 w-full">
        <div class="flex items-center gap-3">
            <span class="text-2xl sm:text-4xl lg:text-5xl animate-bounce">☕</span>
            <div class="text-right">
                <h1
                    class="text-lg sm:text-3xl lg:text-5xl font-black bg-gradient-to-r from-[#d4af37] to-[#f3e5ab] bg-clip-text text-transparent tracking-wider leading-none mb-1">
                    ELECTRO CAFE
                </h1>
            </div>
        </div>

        <div class="hidden lg:block text-left">
            <h2 class="text-base sm:text-xl lg:text-3xl font-bold text-gray-400 leading-none">حالة الطلبات الحالية</h2>
        </div>
    </div>

    <!-- 2. لوحة العرض الرئيسية -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 flex-1 w-full items-stretch">

        <!-- 🌟 القسم الأيمن: معرض المنتجات المميزة (تم حمايته وعزله هنا بنجاح) -->
        <div wire:ignore x-data="{ 
                currentIndex: 0, 
                totalSlides: {{ count($showcaseProducts) }},
                init() {
                    setInterval(() => {
                        if (this.totalSlides > 0) {
                            this.currentIndex = (this.currentIndex + 1) % this.totalSlides;
                        }
                    }, 5000);
                }
             }"
            class="bg-[#11141c] border border-[#1f2430] rounded-2xl p-6 flex flex-col justify-between shadow-2xl relative overflow-hidden group w-full">

            <div class="absolute -top-24 -left-24 w-48 h-48 bg-[#d4af37]/5 rounded-full blur-3xl pointer-events-none">
            </div>

            <!-- الحاوية الشبكية المستقرة -->
            <div
                class="w-full flex-1 grid grid-cols-1 grid-rows-1 items-center justify-center min-h-[450px] lg:min-h-[600px] overflow-hidden">
                @if(count($showcaseProducts) > 0)
                    @foreach($showcaseProducts as $index => $product)
                        <div x-show="currentIndex === {{ $index }}" wire:key="product-slide-{{ $product->id }}"
                            x-transition:enter="transition ease-out duration-700 transform"
                            x-transition:enter-start="opacity-0 translate-y-12 blur-md scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 blur-none scale-100"
                            x-transition:leave="transition ease-in duration-500 transform absolute inset-0 m-auto flex flex-col items-center justify-center"
                            x-transition:leave-start="opacity-100 translate-y-0 blur-none scale-100"
                            x-transition:leave-end="opacity-0 -translate-y-12 blur-md scale-95"
                            class="col-start-1 row-start-1 w-full flex flex-col items-center justify-center text-center p-2">

                            <!-- حجم الصورة العملاق والمثالي -->
                            <div class="relative mb-6 flex-shrink-0">
                                <img src="{{ asset('storage/' . $product->image_path) }}"
                                    class="w-56 h-56 sm:w-72 sm:h-72 lg:w-[440px] lg:h-[440px] xl:w-[520px] xl:h-[520px] object-cover rounded-2xl shadow-[0_25px_60px_rgba(0,0,0,0.7)] border border-[#242a36] transform group-hover:scale-102 transition duration-500"
                                    alt="{{ $product->name }}">
                            </div>

                            <!-- النصوص والمعلومات -->
                            <h2 class="text-xl sm:text-3xl lg:text-4xl xl:text-5xl font-black text-white mb-2 tracking-wide">
                                {{ $product->name }}
                            </h2>
                            <p
                                class="text-xs sm:text-base lg:text-lg xl:text-xl text-gray-400 max-w-xl line-clamp-2 leading-relaxed mb-4 px-2">
                                {{ $product->description }}
                            </p>

                            <span
                                class="px-6 py-2 sm:px-8 sm:py-4 bg-[#d4af37]/10 text-[#d4af37] border border-[#d4af37]/20 rounded-2xl text-lg sm:text-2xl lg:text-3xl xl:text-4xl font-black tracking-tight flex-shrink-0 shadow-lg">
                                {{ number_format($product->price, 2) }} <span
                                    class="text-xs sm:text-sm lg:text-xl font-normal">EGP</span>
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="col-start-1 row-start-1 text-center text-gray-600">
                        <span class="text-5xl block mb-2">🍽️</span>
                        <p class="text-sm">لا توجد منتجات مضافة حالياً</p>
                    </div>
                @endif
            </div>

            @if(count($showcaseProducts) > 1)
                <div class="flex justify-center gap-2 mt-6 z-20 flex-shrink-0">
                    @foreach($showcaseProducts as $index => $prod)
                        <button @click="currentIndex = {{ $index }}" class="h-2 rounded-full transition-all duration-300"
                            :class="currentIndex === {{ $index }} ? 'bg-[#d4af37] w-8' : 'bg-gray-700 w-2 hover:bg-gray-500'"></button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- القسم الأيسر: جدول الطلبات الحية (بيتحدث لايڤ وسلس جداً) -->
        <div class="flex flex-col gap-6 w-full justify-between">

            <div class="block lg:hidden text-right mb-1 px-1">
                <h2 class="text-lg font-bold text-gray-200 leading-tight">حالة الطلبات الحالية</h2>
            </div>

            <!-- طلبات قيد التحضير -->
            <div
                class="bg-[#11141c] border border-[#1f2430] rounded-2xl p-6 flex flex-col shadow-xl overflow-hidden flex-1 min-h-[320px]">
                <div class="flex justify-between items-center mb-4 border-b border-[#1f2430] pb-3">
                    <h3 class="flex items-center gap-2 text-[#29b6f6] font-black text-base sm:text-xl lg:text-3xl">
                        <span class="inline-block w-3 h-3 bg-[#29b6f6] rounded-full animate-ping"></span>
                        ⏳ قيد التحضير
                    </h3>
                    <span
                        class="text-[10px] sm:text-xs lg:text-base font-bold px-3 py-1 bg-[#29b6f6]/10 text-[#29b6f6] rounded-md border border-[#29b6f6]/20">
                        {{ count($preparingOrders) }} طلبات
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                    @if(count($preparingOrders) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($preparingOrders as $order)
                                <div wire:key="prep-{{ $order->id }}"
                                    class="flex justify-between items-center bg-[#161a24] hover:bg-[#1c212e] p-4 rounded-xl border border-[#1f2430] transition shadow-md group">
                                    <div class="flex flex-col text-right">
                                        <span
                                            class="font-extrabold text-base sm:text-xl lg:text-2xl text-[#29b6f6] group-hover:translate-x-1 transition duration-300">
                                            طلب #{{ $order->id }}
                                        </span>
                                        <span class="text-[9px] sm:text-[11px] lg:text-sm text-gray-500">
                                            منذ
                                            {{ $order->created_at ? $order->created_at->diffForHumans(null, true) : 'قليل' }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-xs sm:text-sm lg:text-lg font-black bg-[#29b6f6]/10 text-white border border-[#1f2430] px-3 py-2 rounded-lg">
                                        طاولة {{ $order->table_number }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-full flex flex-col justify-center items-center text-gray-600 py-6">
                            <span class="text-3xl block mb-1">✨</span>
                            <p class="text-xs sm:text-sm lg:text-lg">كل طلبات الزبائن تم إنجازها!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- طلبات جاهزة للاستلام -->
            <div
                class="bg-[#11141c] border border-[#1f2430] rounded-2xl p-6 flex flex-col shadow-xl overflow-hidden flex-1 min-h-[320px]">
                <div class="flex justify-between items-center mb-4 border-b border-[#1f2430] pb-3">
                    <h3 class="flex items-center gap-2 text-[#66bb6a] font-black text-base sm:text-xl lg:text-3xl">
                        <span class="inline-block w-3 h-3 bg-[#66bb6a] rounded-full animate-pulse"></span>
                        ✅ جاهز للاستلام
                    </h3>
                    <span
                        class="text-[10px] sm:text-xs lg:text-base font-bold px-3 py-1 bg-[#66bb6a]/10 text-[#66bb6a] rounded-md border border-[#66bb6a]/20">
                        {{ count($readyOrders) }} جاهز
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                    @if(count($readyOrders) > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($readyOrders as $order)
                                <div wire:key="ready-{{ $order->id }}"
                                    class="flex justify-between items-center bg-[#161a24] hover:bg-[#1a231c] p-4 rounded-xl border border-[#1f2430] border-l-4 border-l-[#66bb6a] transition shadow-md group">
                                    <div class="flex flex-col text-right">
                                        <span
                                            class="font-extrabold text-lg sm:text-2xl lg:text-3xl text-[#66bb6a] group-hover:translate-x-1 transition duration-300">
                                            طلب #{{ $order->id }}
                                        </span>
                                        <span
                                            class="text-[9px] sm:text-[11px] lg:text-sm text-emerald-400 font-medium animate-pulse">
                                            جاهز للاستلام
                                        </span>
                                    </div>
                                    <span
                                        class="text-xs sm:text-sm lg:text-lg font-black bg-[#66bb6a]/20 text-white px-4 py-2 rounded-lg shadow-md">
                                        طاولة {{ $order->table_number }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-full flex flex-col justify-center items-center text-gray-600 py-6">
                            <span class="text-3xl block mb-1">🛎️</span>
                            <p class="text-xs sm:text-sm lg:text-lg">في انتظار خروج الوجبات والمشروبات...</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>