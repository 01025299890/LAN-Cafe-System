<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $selectedTable = null;
    public $selectedCategory = 'all';
    public $search = '';
    public $searchTable = ''; // حقل البحث المخصص لرقم الطاولة

    // سلة المشتريات والملاحظات
    public $cart = [];
    public $orderNotes = '';
    public $activeOrders = [];

    // الانتقال بين الطاولات مع حفظ تلقائي ومستقل للسلات
    public function selectTable($tableNum)
    {
        // 1. حفظ السلة الحالية للطاولة القديمة قبل الانتقال (إذا كانت محددة)
        if ($this->selectedTable) {
            session()->put("cart_table_{$this->selectedTable}", $this->cart);
            session()->put("notes_table_{$this->selectedTable}", $this->orderNotes);
        }

        // 2. تعيين الطاولة الجديدة
        $this->selectedTable = $tableNum;

        // 3. استرجاع بيانات السلة والملاحظات الخاصة بالطاولة الجديدة من الجلسة
        $this->cart = session()->get("cart_table_{$tableNum}", []);
        $this->orderNotes = session()->get("notes_table_{$tableNum}", '');

        // 4. جلب الطلبات النشطة حالياً لهذه الطاولة من المطبخ
        $this->loadActiveOrders();
    }

    public function loadActiveOrders()
    {
        if ($this->selectedTable) {
            $this->activeOrders = Order::with('items.product')
                ->where('table_number', $this->selectedTable)
                ->whereIn('status', ['pending', 'preparing', 'ready'])
                ->latest()
                ->get();
        } else {
            $this->activeOrders = [];
        }
    }

    public function selectCategory($slug)
    {
        $this->selectedCategory = $slug;
    }

    // إضافة منتج للسلة
    public function addToCart($productId)
    {
        if (!$this->selectedTable) {
            session()->flash('error', '⚠️ الرجاء اختيار رقم الطاولة أولاً قبل إضافة المنتجات!');
            return;
        }

        $product = Product::findOrFail($productId);

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        } else {
            $this->cart[$productId] = [
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'notes' => ''
            ];
        }

        $this->saveCartToSession();
    }

    public function incrementQuantity($productId)
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
            $this->saveCartToSession();
        }
    }

    public function decrementQuantity($productId)
    {
        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['quantity'] > 1) {
                $this->cart[$productId]['quantity']--;
            } else {
                unset($this->cart[$productId]);
            }
            $this->saveCartToSession();
        }
    }

    // حذف الوجبة بالكامل من السلة
    public function removeFromCart($productId)
    {
        if (isset($this->cart[$productId])) {
            unset($this->cart[$productId]);
            $this->saveCartToSession();
        }
    }

    // حفظ تلقائي فوري عند تحديث ملاحظات أي وجبة في السلة
    public function updatedCart()
    {
        $this->saveCartToSession();
    }

    private function saveCartToSession()
    {
        if ($this->selectedTable) {
            session()->put("cart_table_{$this->selectedTable}", $this->cart);
        }
    }

    // إرسال الطلب للمطبخ
    public function submitOrder()
    {
        if (!$this->selectedTable) {
            session()->flash('error', 'الرجاء اختيار رقم الطاولة.');
            return;
        }

        if (empty($this->cart)) {
            session()->flash('error', 'سلة الطلبات فارغة!');
            return;
        }

        DB::transaction(function () {
            // حساب الإجمالي الكلي للسلة
            $total = 0;
            foreach ($this->cart as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            // إنشاء الطلب الرئيسي
            $order = Order::create([
                'table_number' => $this->selectedTable,
                'status' => 'pending',
                'notes' => $this->orderNotes,
                'total_amount' => $total,
                'user_id' => auth()->id() ?? 1, // جلب معرف الموظف الحالي (الجرسون)
            ]);

            // تفريغ محتويات السلة بجدول العناصر المرفقة
            foreach ($this->cart as $productId => $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'notes' => $item['notes'] ?: null,
                ]);
            }
        });

        // تنظيف الجلسة والسلة الحالية للطاولة بعد الإرسال بنجاح
        session()->forget("cart_table_{$this->selectedTable}");
        session()->forget("notes_table_{$this->selectedTable}");
        $this->cart = [];
        $this->orderNotes = '';

        session()->flash('success', '🎉 تم إرسال الطلب بنجاح إلى المطبخ وهو قيد الانتظار الآن!');
        $this->loadActiveOrders();
    }

    public function with()
    {
        // 1. تحديد عدد الطاولات ديناميكياً (افتراضي 36 طاولة ويمكنك تعديله لأي رقم)
        $totalTablesCount = config('cafe.total_tables', 36);
        $tablesList = range(1, $totalTablesCount);

        // إذا كان لديك موديل للطاولات مستقبلاً يمكنك تفعيل السطر التالي بدلاً من range:
        // $tablesList = \App\Models\Table::pluck('number')->toArray();

        // تصفية الطاولات بناءً على بحث الجرسون
        if (!empty($this->searchTable)) {
            $tablesList = array_filter($tablesList, function ($t) {
                return str_contains((string) $t, $this->searchTable);
            });
        }

        // 2. جلب المنتجات المتوفرة فقط وتطبيق فلاتر البحث والأقسام
        $productsQuery = Product::where('is_available', true);

        if (!empty($this->search)) {
            $productsQuery->where('name', 'like', '%' . trim($this->search) . '%');
        }

        if ($this->selectedCategory !== 'all') {
            $productsQuery->whereHas('category', function ($q) {
                $q->where('slug', $this->selectedCategory);
            });
        }

        return [
            'tables' => $tablesList,
            'products' => $productsQuery->get(),
            'categories' => Category::all()
        ];
    }
};

\Livewire\Volt\layout('components.layouts.app');
?>

<div x-data="{ mobileMenuOpen: false, cartOpen: false }" class="p-4 md:p-6 bg-[#0f1115] min-h-screen relative"
    dir="rtl">
    <x-slot name="title">لوحة الطلبات</x-slot>

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- هيدر الصفحة والربط الذكي مع الموظفين والمنتجات -->
        <nav class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl shadow-lg mb-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <div class="flex items-center gap-6 lg:gap-10">
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <span
                                class="text-lg sm:text-xl font-black bg-gradient-to-r from-[#d4af37] to-[#b8912f] bg-clip-text text-transparent tracking-wide">
                                <span class="text-[#d4af37]">☕</span> ELECTRO CAFE
                            </span>
                        </div>

                        <!-- روابط الملاحة لسطح المكتب -->
                        <div class="hidden md:flex items-center gap-8">
                            <a href="{{ route('waiter.orders') }}"
                                class="text-[#d4af37] border-b-2 border-[#d4af37] pb-2 text-sm font-bold tracking-wide">الطلبات</a>
                            <a href="{{ route('settings.profile') }}"
                                class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">الإعدادات</a>
                        </div>
                    </div>

                    <!-- قائمة الموظف + زر همبرجر للموبايل -->
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-3 border-r border-[#2a2e38] pr-5">
                            <div class="text-left pl-1">
                                <p class="text-sm font-semibold text-gray-200">
                                    {{ auth()->user()->name ?? 'مستخدم النظام' }}
                                </p>
                                <p class="text-[11px] text-gray-500 font-medium text-right">
                                    {{ auth()->user()->role === 'waiter' ? 'طاقم صالة الضيافة' : 'مستخدم النظام' }}
                                </p>
                            </div>
                            <div
                                class="w-10 h-10 rounded-xl bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-lg shadow-inner">
                                🤵
                            </div>
                        </div>

                        <!-- زر القائمة للجوال -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="md:hidden p-2 rounded-xl bg-[#22252d] border border-[#2a2e38] text-gray-400 hover:text-[#d4af37] transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" style="display: none;" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- القائمة المنسدلة للجوال -->
            <div x-show="mobileMenuOpen" x-transition
                class="md:hidden border-t border-[#2a2e38] bg-[#1a1d24] px-4 py-3 space-y-2 rounded-b-2xl"
                style="display: none;">
                <a href="{{ route('waiter.orders') }}" class="block text-[#d4af37] font-bold text-sm py-2">الطلبات</a>
                <a href="{{ route('settings.profile') }}"
                    class="block text-gray-400 hover:text-[#d4af37] text-sm py-2">الإعدادات</a>
                <div class="border-t border-[#2a2e38] pt-2 mt-2 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#22252d] flex items-center justify-center text-sm">🤵</div>
                    <div>
                        <p class="text-xs font-semibold text-gray-200">{{ auth()->user()->name ?? 'مستخدم النظام' }}</p>
                        <p class="text-[9px] text-gray-500">طاقم صالة الضيافة</p>
                    </div>
                </div>
            </div>
        </nav>

        <!-- عنوان الصفحة الفرعي -->
        <div class="text-center my-6">
            <h1
                class="text-2xl sm:text-3xl font-extrabold bg-gradient-to-r from-[#d4af37] to-[#f3e5ab] bg-clip-text text-transparent">
                🤵 لوحة الطلبات
            </h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-2">سجل طلبات الطاولات وأرسلها للمطبخ فوراً لمتابعة حالة
                التحضير</p>
        </div>

        <!-- رسائل الحفظ والتنبيهات -->
        @if (session()->has('success'))
            <div
                class="p-4 mb-2 text-sm text-emerald-300 bg-emerald-900/30 border border-emerald-700/50 rounded-xl font-bold transition">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div
                class="p-4 mb-2 text-sm text-red-300 bg-red-900/30 border border-red-700/50 rounded-xl font-bold transition">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- الجزء الأيمن والأوسط: الطاولات ومنيو الوجبات -->
            <div class="lg:col-span-2 space-y-6">

                <!-- 📍 اختيار رقم الطاولة -->
                <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-4 sm:p-6 space-y-4">

                    <!-- الهيدر مع مربع البحث السريع لفلترة الطاولات عند زيادة عددها -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#2a2e38]/60 pb-3">
                        <h3
                            class="text-[#d4af37] text-sm sm:text-md font-bold border-r-4 border-[#d4af37] pr-3 flex items-center gap-2">
                            📍 اختر رقم الطاولة للبدء
                            <span
                                class="text-[10px] font-medium text-gray-500 bg-[#22252d] border border-[#2a2e38] px-2 py-0.5 rounded-full">
                                {{ count($tables) }} طاولة
                            </span>
                        </h3>
                        <div class="relative w-full sm:w-48">
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-500">🔍</span>
                            <input type="number" wire:model.live="searchTable" placeholder="بحث برقم الطاولة..."
                                class="w-full pr-8 pl-3 py-1.5 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 text-xs focus:ring-[#d4af37] focus:border-[#d4af37] focus:outline-none placeholder-gray-500 transition">
                        </div>
                    </div>

                    <!-- شبكة الطاولات بتصميم بطاقات أوضح مع مؤشر حالة -->
                    <div
                        class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2.5 sm:gap-3 max-h-52 overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-[#2a2e38]">
                        @forelse($tables as $tableNum)
                                            @php
                                                $isActive = $selectedTable === $tableNum;
                                                $hasCart = session()->has("cart_table_{$tableNum}") && !empty(session()->get("cart_table_{$tableNum}"));
                                            @endphp
                                            <button wire:click="selectTable({{ $tableNum }})"
                                                class="group relative flex flex-col items-center justify-center gap-1 py-3 sm:py-3.5 px-2 rounded-xl border transition-all duration-200
                                                    {{ $isActive
                            ? 'bg-gradient-to-b from-[#d4af37] to-[#b8912f] text-black border-[#d4af37] shadow-lg shadow-[#d4af37]/30 scale-105 -translate-y-0.5'
                            : 'bg-[#22252d] border-[#2a2e38] text-gray-300 hover:border-[#d4af37]/70 hover:bg-[#262a33] hover:-translate-y-0.5' }}">

                                                <span
                                                    class="text-base sm:text-lg leading-none {{ $isActive ? '' : 'opacity-70 group-hover:opacity-100' }}">
                                                    🪑
                                                </span>
                                                <span class="text-[11px] sm:text-sm font-black leading-none">
                                                    {{ $tableNum }}
                                                </span>

                                                <!-- نقطة صغيرة تدل إن فيه سلة محفوظة للطاولة دي -->
                                                @if($hasCart && !$isActive)
                                                    <span
                                                        class="absolute top-1.5 left-1.5 w-1.5 h-1.5 rounded-full bg-[#d4af37] animate-pulse"></span>
                                                @endif
                                            </button>
                        @empty
                            <div
                                class="col-span-full py-8 text-center text-gray-500 text-xs flex flex-col items-center gap-2">
                                <span class="text-2xl">🔍</span>
                                لا توجد طاولة مطابقة للبحث.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 🍔 كتالوج الوجبات والبحث الفوري -->
                @if($selectedTable)
                    <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-4 sm:p-6 space-y-6">

                        <!-- أشرطة التصفية والبحث الفوري للوجبات -->
                        <div class="flex flex-col gap-4">
                            <!-- حقل البحث -->
                            <div class="relative w-full">
                                <span class="absolute inset-y-0 right-3 flex items-center text-gray-500">🔍</span>
                                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ابحث عن منتج..."
                                    class="w-full pr-9 pl-4 py-2.5 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 text-sm focus:ring-[#d4af37] focus:border-[#d4af37] focus:outline-none placeholder-gray-500 transition">
                            </div>

                            <!-- تصنيفات الأقسام بالتمرير الأفقي للجوال -->
                            <div
                                class="flex gap-2 overflow-x-auto pb-2 scrollbar-none [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                <button wire:click="selectCategory('all')"
                                    class="px-4 py-2 text-xs font-bold rounded-xl border transition shrink-0 {{ $selectedCategory === 'all' ? 'bg-[#d4af37] text-black border-[#d4af37]' : 'bg-[#22252d] border-[#2a2e38] text-gray-400 hover:text-white' }}">
                                    الكل
                                </button>
                                @foreach($categories as $category)
                                    <button wire:click="selectCategory('{{ $category->slug }}')"
                                        class="px-4 py-2 text-xs font-bold rounded-xl border transition shrink-0 {{ $selectedCategory === $category->slug ? 'bg-[#d4af37] text-black border-[#d4af37]' : 'bg-[#22252d] border-[#2a2e38] text-gray-400 hover:text-white' }}">
                                        {{ explode(' - ', $category->name)[1] ?? $category->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- شبكة عرض المنتجات -->
                        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                            @forelse($products as $product)
                                <div
                                    class="bg-[#22252d] border border-[#2a2e38] rounded-xl overflow-hidden hover:border-[#d4af37]/60 transition duration-300 flex flex-col justify-between">
                                    <div class="h-24 sm:h-32 w-full overflow-hidden relative">
                                        <img src="{{ Str::startsWith($product->image_path, 'http') ? $product->image_path : asset('storage/' . $product->image_path) }}"
                                            class="w-full h-full object-cover transition duration-500 hover:scale-105"
                                            alt="{{ $product->name }}">
                                    </div>
                                    <div class="p-3 sm:p-4 flex flex-col justify-between flex-grow">
                                        <div>
                                            <h4
                                                class="text-white font-bold text-xs sm:text-sm mb-1 line-clamp-2 h-8 sm:h-10 leading-snug">
                                                {{ $product->name }}
                                            </h4>
                                            <span
                                                class="text-[#d4af37] font-black text-xs sm:text-sm block mb-2 sm:mb-3">{{ number_format($product->price, 2) }}
                                                EGP</span>
                                        </div>
                                        <button wire:click="addToCart({{ $product->id }})"
                                            class="w-full bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-extrabold py-2 rounded-lg text-[10px] sm:text-xs hover:brightness-110 transition flex items-center justify-center gap-1">
                                            ➕ إضافة للطلب
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full py-10 text-center text-gray-500 text-sm">لا توجد منتجات متطابقة مع
                                    البحث الحالي.</div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <!-- رسالة التوجيه باختيار الطاولة أولاً -->
                    <div
                        class="bg-[#1a1d24] border border-[#2a2e38] border-dashed rounded-2xl p-12 sm:p-16 text-center space-y-4">
                        <span class="text-4xl sm:text-5xl block animate-bounce">👉</span>
                        <h3 class="text-gray-300 font-bold text-sm sm:text-lg">يرجى اختيار رقم الطاولة للبدء في تدوين وإضافة
                            الوجبات</h3>
                    </div>
                @endif
            </div>

            <!-- الخلفية الشفافة المظلمة للموبايل عند فتح سلة المشتريات والطلبات النشطة -->
            <div x-show="cartOpen" @click="cartOpen = false"
                class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 lg:hidden" style="display: none;"></div>

            <!-- الجزء الأيسر: سلة الطلبات والمتابعة المباشرة مع المطبخ -->
            <div :class="cartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
                class="fixed inset-y-0 right-0 z-50 w-[85%] max-w-[360px] bg-[#1a1d24] border-l border-[#2a2e38] p-5 lg:p-0 lg:static lg:w-auto lg:bg-transparent lg:border-none transform transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col gap-6 overflow-y-auto h-full lg:h-auto">

                <!-- زر إغلاق السلة للجوال -->
                <div class="flex items-center justify-between lg:hidden border-b border-[#2a2e38] pb-3">
                    <span class="text-white font-bold text-sm">🛒 سلة طاولة {{ $selectedTable ?? '' }}</span>
                    <button @click="cartOpen = false"
                        class="text-gray-400 hover:text-white text-xs bg-[#22252d] px-3 py-1 rounded-lg border border-[#2a2e38]">✕
                        إغلاق</button>
                </div>

                <!-- 🛒 سلة الطلبات الحالية -->
                <div class="bg-[#1a1d24] lg:border lg:border-[#2a2e38] lg:rounded-2xl lg:p-6 space-y-4">
                    <div class="hidden lg:flex items-center justify-between border-b border-[#2a2e38] pb-3">
                        <h3 class="text-[#d4af37] text-md font-bold flex items-center gap-2">
                            <span>🛒 سلة الطلبات</span>
                        </h3>
                        @if($selectedTable)
                            <span
                                class="text-xs bg-[#d4af37]/10 border border-[#d4af37]/30 text-[#d4af37] px-3 py-1 rounded-full font-bold">
                                طاولة {{ $selectedTable }}
                            </span>
                        @endif
                    </div>

                    <!-- قائمة عناصر السلة -->
                    <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1">
                        @forelse($cart as $productId => $item)
                            <div class="bg-[#22252d] border border-[#2a2e38] rounded-xl p-3 space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-xs sm:text-sm text-gray-200 font-semibold">{{ $item['name'] }}</span>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-xs text-[#d4af37] font-bold whitespace-nowrap">
                                            {{ number_format($item['price'] * $item['quantity'], 2) }} EGP
                                        </span>
                                        <!-- زر حذف الوجبة بالكامل -->
                                        <button wire:click="removeFromCart({{ $productId }})"
                                            class="text-gray-500 hover:text-red-500 transition text-sm p-1"
                                            title="حذف الوجبة بالكامل">
                                            🗑️
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 justify-between">
                                    <input type="text" placeholder="إضافة ملاحظة للوجبة..."
                                        wire:model.live.debounce.500ms="cart.{{ $productId }}.notes"
                                        class="bg-[#1a1d24] border border-[#2a2e38] rounded-lg text-[11px] px-2.5 py-1.5 text-gray-300 placeholder-gray-500 focus:outline-none focus:border-[#d4af37] flex-grow">

                                    <div
                                        class="flex items-center gap-2.5 bg-[#1a1d24] border border-[#2a2e38] px-2 py-1 rounded-lg">
                                        <button wire:click="decrementQuantity({{ $productId }})"
                                            class="text-gray-400 hover:text-red-400 font-bold text-xs sm:text-sm transition">-</button>
                                        <span
                                            class="text-xs text-white font-black min-w-[15px] text-center">{{ $item['quantity'] }}</span>
                                        <button wire:click="incrementQuantity({{ $productId }})"
                                            class="text-[#d4af37] hover:text-white font-bold text-xs sm:text-sm transition">+</button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10 text-gray-500 text-xs sm:text-sm space-y-2">
                                <span class="text-3xl block">🛒</span>
                                <p>السلة فارغة، أضف وجبات للبدء.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- الملاحظات العامة والإجمالي -->
                    @if(!empty($cart))
                        <div class="border-t border-[#2a2e38] pt-4 space-y-4">
                            <div>
                                <label class="block text-[11px] text-gray-400 mb-1">✍️ ملاحظات عامة للتحضير:</label>
                                <textarea wire:model="orderNotes" placeholder="أي ملاحظات عامة للطلب بالكامل..."
                                    class="w-full bg-[#22252d] border border-[#2a2e38] rounded-xl text-xs p-3 text-gray-300 placeholder-gray-500 focus:outline-none focus:border-[#d4af37] h-16 resize-none"></textarea>
                            </div>

                            <div
                                class="flex items-center justify-between bg-[#22252d] p-3 rounded-xl border border-[#2a2e38]">
                                <span class="text-xs sm:text-sm text-gray-300">الإجمالي الكلي:</span>
                                <span class="text-[#d4af37] font-black text-md sm:text-lg">
                                    @php
                                        $total = 0;
                                        foreach ($cart as $item) {
                                            $total += $item['price'] * $item['quantity'];
                                        }
                                    @endphp
                                    {{ number_format($total, 2) }} EGP
                                </span>
                            </div>

                            <button wire:click="submitOrder"
                                class="w-full bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-extrabold py-3 rounded-xl hover:brightness-110 transition shadow-lg shadow-[#d4af37]/10 flex items-center justify-center gap-2 text-sm">
                                🔔 إرسال الطلب للمطبخ
                            </button>
                        </div>
                    @endif
                </div>

                <!-- 🕒 تتبع طلبات الطاولة الحالية والموجودة بالمطبخ -->
                @if($selectedTable && !empty($activeOrders))
                    <div class="bg-[#1a1d24] lg:border lg:border-[#2a2e38] lg:rounded-2xl lg:p-6 space-y-4">
                        <h4
                            class="text-[#d4af37] text-xs sm:text-sm font-bold flex items-center gap-1.5 border-b border-[#2a2e38] pb-3 lg:border-none lg:pb-0">
                            <span>🕒 حالة طلبات الطاولة النشطة</span>
                        </h4>

                        <div class="space-y-3">
                            @foreach($activeOrders as $order)
                                @php
                                    $statusColors = [
                                        'pending' => 'border-yellow-500 bg-[#22252d]',
                                        'preparing' => 'border-blue-500 bg-[#22252d]',
                                        'ready' => 'border-emerald-500 bg-[#22252d]'
                                    ];
                                    $badgeStyles = [
                                        'pending' => 'bg-yellow-900/40 text-yellow-300 border border-yellow-700/50',
                                        'preparing' => 'bg-blue-900/40 text-blue-300 border border-blue-700/50',
                                        'ready' => 'bg-emerald-900/40 text-emerald-300 border border-emerald-700/50'
                                    ];
                                    $currentColor = $statusColors[$order->status] ?? 'border-gray-500 bg-[#22252d]';
                                    $currentBadge = $badgeStyles[$order->status] ?? 'bg-gray-800 text-gray-400';
                                @endphp

                                <div class="p-3 sm:p-4 rounded-xl border-r-4 {{ $currentColor }} shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs text-white font-bold">طلب #{{ $order->id }}</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $currentBadge }}">
                                            @if($order->status == 'pending') معلق @elseif($order->status == 'preparing') قيد
                                            التحضير @else جاهز ⚡ @endif
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-400 space-y-1.5">
                                        @foreach($order->items as $item)
                                            <div class="flex items-center justify-between">
                                                <span>• {{ $item->product->name }} ({{ $item->quantity }})</span>
                                                @if($item->notes)
                                                    <span
                                                        class="text-[#d4af37] text-[9px] font-medium bg-[#1a1d24] px-1.5 py-0.5 rounded border border-[#2a2e38] max-w-[100px] truncate">{{ $item->notes }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- 🛒 زر عائم لعرض السلة على الموبايل فقط -->
    @if($selectedTable && !empty($cart))
        @php
            $totalItems = collect($cart)->sum('quantity');
        @endphp
        <div class="fixed bottom-6 right-6 z-40 lg:hidden">
            <button @click="cartOpen = true"
                class="relative bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-extrabold p-4 rounded-full shadow-2xl shadow-[#d4af37]/40 hover:scale-105 transition active:scale-95 flex items-center justify-center">
                <span
                    class="absolute -top-2 -left-2 bg-red-600 text-white text-[10px] font-black w-6 h-6 rounded-full flex items-center justify-center border-2 border-[#1a1d24]">
                    {{ $totalItems }}
                </span>
                <span class="text-xl">🛒</span>
            </button>
        </div>
    @endif
</div>