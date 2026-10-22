<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Category;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    // تتبع البحث والقسم المختار في الرابط
    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $activeCategory = 'all';

    // متغير لتخزين الوجبة المختارة لعرض تفاصيلها في نفس الصفحة
    public $selectedProduct = null;

    // إعادة الترقيم للصفحة الأولى عند التصفية
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function selectCategory($id)
    {
        $this->activeCategory = $id;
        $this->resetPage();
    }

    // دالة جلب تفاصيل وجبة معينة لفتح المودال
    public function showProductDetails($id)
    {
        $this->selectedProduct = Product::with('category')->find($id);
    }

    // دالة إغلاق نافذة التفاصيل
    public function closeDetails()
    {
        $this->selectedProduct = null;
    }

    public function with()
    {
        // جلب الوجبات النشطة والمتوفرة فقط للزبائن
        $query = Product::where('is_available', true)->with('category');

        // فلترة حسب القسم
        if ($this->activeCategory !== 'all') {
            $query->where('category_id', $this->activeCategory);
        }

        // فلترة حسب البحث
        $searchTerm = trim($this->search);
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        return [
            'products' => $query->latest()->paginate(12),
            'categories' => Category::has('products')->get(), // جلب الأقسام التي تحتوي على وجبات فقط
        ];
    }
};

\Livewire\Volt\layout('components.layouts.app');
?>

<div class="p-4 md:p-6 bg-[#0f1115] min-h-screen relative" dir="rtl">
    <x-slot name="title">المنيو </x-slot>

    <div class="max-w-7xl mx-auto space-y-6 md:space-y-8">

        <!-- هيدر صفحة المنيو المطور للزبائن -->
        <nav x-data="{ mobileMenuOpen: false }" class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">

                    <!-- اليمين: اللوجو والروابط الأساسية للعميل -->
                    <div class="flex items-center gap-10">
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <span
                                class="text-xl font-black bg-gradient-to-r from-[#d4af37] to-[#b8912f] bg-clip-text text-transparent tracking-wide">
                                <span class="text-[#d4af37]">☕</span> ELECTRO CAFE
                            </span>
                        </div>

                        <!-- روابط الـ Desktop -->
                        <div class="hidden md:flex items-center gap-8">
                            <a href="{{ route('customer.menu') }}"
                                class="text-[#d4af37] border-b-2 border-[#d4af37] pb-2 text-sm font-bold tracking-wide">المنيو
                            </a>

                            <a href="{{ route('customer.track-order') }}"
                                class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium flex items-center gap-1.5">
                                <span>🔍</span> متابعة الأوردر
                            </a>
                        </div>
                    </div>

                    <!-- اليسار: زر القائمة للموبايل فقط -->
                    <div class="flex items-center">
                        <div class="flex md:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                                class="inline-flex items-center justify-center p-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-[#22252d] border border-[#2a2e38] focus:outline-none transition">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }"
                                        class="inline-flex" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
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
                        class="block px-4 py-3 rounded-xl text-base font-bold text-[#d4af37] bg-[#22252d] border-r-4 border-[#d4af37]">المنيو
                    </a>
                    <a href="{{ route('customer.track-order') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]">🔍
                        متابعة الأوردر</a>
                </div>
            </div>
        </nav>

        <!-- شريط البحث الفوري وأزرار التصنيفات المطور للموبايل -->
        <div
            class="flex flex-col-reverse md:flex-row items-stretch md:items-center justify-between gap-4 bg-[#1a1d24] border border-[#2a2e38] p-4 rounded-2xl shadow-md">

            <!-- ألسنة الأقسام المتوفرة ديناميكياً -->
            <div
                class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden snap-x">
                <button wire:click="selectCategory('all')"
                    class="px-5 py-2.5 md:py-2 rounded-xl text-xs font-bold transition whitespace-nowrap shrink-0 snap-start {{ $activeCategory === 'all' ? 'bg-[#d4af37] text-black shadow-md shadow-[#d4af37]/20' : 'bg-[#22252d] text-gray-400 border border-[#2a2e38] hover:text-[#d4af37]' }}">
                    🌟 الكل
                </button>
                @foreach($categories as $category)
                    <button wire:click="selectCategory({{ $category->id }})"
                        class="px-5 py-2.5 md:py-2 rounded-xl text-xs font-bold transition whitespace-nowrap shrink-0 snap-start {{ $activeCategory == $category->id ? 'bg-[#d4af37] text-black shadow-md shadow-[#d4af37]/20' : 'bg-[#22252d] text-gray-400 border border-[#2a2e38] hover:text-[#d4af37]' }}">
                        📂 {{ $category->name }}
                    </button>
                @endforeach
            </div>

            <!-- حقل البحث السلس للزبون -->
            <div class="relative w-full md:w-72">
                <span class="absolute inset-y-0 right-3 flex items-center text-gray-500 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ابحث عن مشروب أو وجبة..."
                    class="w-full pr-10 pl-4 py-3 md:py-2 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 text-xs focus:ring-2 focus:ring-[#d4af37]/40 focus:border-[#d4af37] focus:outline-none placeholder-gray-500 transition shadow-inner">
            </div>
        </div>

        <!-- شبكة عرض الوجبات -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            @forelse($products as $product)
                <div
                    class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl overflow-hidden shadow-lg hover:border-[#d4af37]/40 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">

                    <!-- صورة الوجبة -->
                    <div class="relative overflow-hidden aspect-video bg-[#22252d]">
                        <img src="{{ Str::startsWith($product->image_path, 'http') ? $product->image_path : asset('storage/' . $product->image_path) }}"
                            alt="{{ $product->name }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <span
                            class="absolute top-3 right-3 bg-black/70 backdrop-blur-md text-[#d4af37] text-[11px] font-bold px-2.5 py-1 rounded-lg border border-[#2a2e38]">
                            {{ $product->category?->name ?? 'وجبات' }}
                        </span>
                    </div>

                    <!-- تفاصيل الوجبة -->
                    <div class="p-4 md:p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h4 class="text-gray-100 font-bold text-sm md:text-base tracking-wide">
                                {{ $product->name }}
                            </h4>
                            <p class="text-xs text-gray-400 mt-1.5 line-clamp-2 leading-relaxed min-h-[32px]">
                                {{ $product->description ?? 'لا يوجد وصف متاح للوجبة حالياً.' }}
                            </p>
                        </div>

                        <!-- السعر وزر التفاصيل المحدث ليعمل بـ Livewire في نفس الصفحة -->
                        <div class="flex items-center justify-between pt-2 border-t border-[#2a2e38]">
                            <span class="text-[#d4af37] font-black text-sm md:text-base">
                                {{ number_format($product->price, 2) }}
                                <small class="text-[10px] font-medium text-gray-500">EGP</small>
                            </span>

                            <button type="button" wire:click="showProductDetails({{ $product->id }})"
                                class="bg-[#22252d] border border-[#2a2e38] text-gray-200 hover:bg-[#d4af37] hover:text-black hover:border-[#d4af37] px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                                <span>👁️</span>
                                <span>التفاصيل</span>
                            </button>
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-full bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-12 text-center text-gray-500">
                    <p class="text-base md:text-lg">🥣 عذرًا، لا توجد أي وجبات متوفرة حاليًا في هذا القسم أو تطابق بحثك.</p>
                </div>
            @endforelse
        </div>

        <!-- أزرار ترقيم الصفحات التفاعلية المتناسقة -->
        @if($products->hasPages())
            <div
                class="bg-[#1a1d24] border border-[#2a2e38] p-4 rounded-2xl shadow-md flex items-center justify-between gap-4">

                <!-- زر السابق (Previous) -->
                <button wire:click="previousPage" @if($products->onFirstPage()) disabled @endif
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $products->onFirstPage() ? 'bg-[#1a1d24] text-gray-600 border border-[#2a2e38]/50 cursor-not-allowed opacity-40' : 'bg-[#22252d] text-gray-300 border border-[#2a2e38] hover:bg-[#d4af37] hover:text-black hover:border-[#d4af37] active:scale-95' }}">
                    <span>➡️</span>
                    <span>السابق</span>
                </button>

                <!-- مؤشر أرقام الصفحات -->
                <span class="text-xs text-gray-400 font-medium tracking-wide">
                    الصفحة <span class="text-[#d4af37] font-black mx-0.5">{{ $products->currentPage() }}</span> من <span
                        class="text-gray-300 font-bold mx-0.5">{{ $products->lastPage() }}</span>
                </span>

                <!-- زر التالي (Next) -->
                <button wire:click="nextPage" @if(!$products->hasMorePages()) disabled @endif
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ !$products->hasMorePages() ? 'bg-[#1a1d24] text-gray-600 border border-[#2a2e38]/50 cursor-not-allowed opacity-40' : 'bg-[#22252d] text-gray-300 border border-[#2a2e38] hover:bg-[#d4af37] hover:text-black hover:border-[#d4af37] active:scale-95' }}">
                    <span>التالي</span>
                    <span>⬅️</span>
                </button>

            </div>
        @endif

    </div>

    <!-- 🌟 نافذة تفاصيل الوجبة المنبثقة (Modal) مع أنيميشن احترافي للدخول والخروج بسلاسة فائقة -->
    @if($selectedProduct)
        <div x-data="{ isOpen: false }" x-init="$nextTick(() => isOpen = true)" x-show="isOpen"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 backdrop-blur-none"
            x-transition:enter-end="opacity-100 backdrop-blur-sm" x-transition:leave="transition ease-in duration-250"
            x-transition:leave-start="opacity-100 backdrop-blur-sm" x-transition:leave-end="opacity-0 backdrop-blur-none"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">

            <!-- خلفية شفافة تغلق النافذة عند الضغط خارجها بأنيميشن سلس -->
            <div class="absolute inset-0 cursor-pointer"
                @click="isOpen = false; setTimeout(() => $wire.closeDetails(), 250)"></div>

            <!-- محتوى النافذة المنبثقة المدعوم بأنيميشن الارتداد المرن المميز (Cubic-Bezier) دخولاً وخروجاً -->
            <div x-show="isOpen" x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-400"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-250"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl relative z-10">

                <!-- زر إغلاق علوي سريع -->
                <button type="button" @click="isOpen = false; setTimeout(() => $wire.closeDetails(), 250)"
                    class="absolute top-4 left-4 bg-[#22252d] border border-[#2a2e38] text-gray-400 hover:text-white w-9 h-9 rounded-xl flex items-center justify-center transition z-20 active:scale-95 cursor-pointer">
                    ✕
                </button>

                <!-- صورة الوجبة الكبيرة -->
                <div class="relative aspect-video bg-[#22252d]">
                    <img src="{{ Str::startsWith($selectedProduct->image_path, 'http') ? $selectedProduct->image_path : asset('storage/' . $selectedProduct->image_path) }}"
                        alt="{{ $selectedProduct->name }}" class="w-full h-full object-cover">
                    <span
                        class="absolute bottom-3 right-3 bg-black/70 backdrop-blur-md text-[#d4af37] text-xs font-bold px-3 py-1.5 rounded-lg border border-[#2a2e38]">
                        📂 {{ $selectedProduct->category?->name ?? 'وجبات' }}
                    </span>
                </div>

                <!-- تفاصيل الوجبة بالكامل -->
                <div class="p-6 space-y-5">
                    <div>
                        <h3 class="text-xl font-black text-white tracking-wide">
                            {{ $selectedProduct->name }}
                        </h3>
                        <p
                            class="text-xs sm:text-sm text-gray-400 mt-3 leading-relaxed border-r-2 border-[#d4af37] pr-3 bg-[#22252d]/30 py-2 rounded-l-lg">
                            {{ $selectedProduct->description ?? 'لا يوجد وصف تفصيلي متاح لهذه الوجبة حالياً.' }}
                        </p>
                    </div>

                    <!-- السعر وزر التأكيد الأسفل -->
                    <div class="flex items-center justify-between pt-4 border-t border-[#2a2e38]">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-500 font-bold tracking-wider">السعر الإجمالي</span>
                            <span class="text-[#d4af37] font-black text-lg sm:text-xl tracking-tight">
                                {{ number_format($selectedProduct->price, 2) }}
                                <small class="text-xs font-bold text-gray-400">EGP</small>
                            </span>
                        </div>

                        <button type="button" @click="isOpen = false; setTimeout(() => $wire.closeDetails(), 250)"
                            class="bg-[#d4af37] text-black hover:bg-[#b8912f] px-6 py-2.5 rounded-xl text-xs font-black transition active:scale-95 shadow-md shadow-[#d4af37]/10 cursor-pointer">
                            رجوع للمنيو
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>