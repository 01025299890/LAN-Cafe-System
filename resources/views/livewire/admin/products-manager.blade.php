<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Livewire\WithPagination; // استدعاء الترقيم الديناميكي
use Livewire\Attributes\Url; // للحفاظ على رابط البحث في المتصفح
use Illuminate\Support\Facades\Storage;

use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;
    use WithPagination; // تفعيل ميزة الترقيم لتسريع تحميل قاعدة البيانات

    // خواص الحقول للمودال
    public $productId = null;
    public $name = '';
    public $price = '';
    public $description = '';
    public $category_id = '';
    public $new_category_name = '';
    public $image_path = null;
    public $old_image = '';
    public $is_available = true;
    public $showFormModal = false;

    // تتبع نص البحث في الرابط
    #[Url(history: true)]
    public $search = '';

    // تصفير ترقيم الصفحات بمجرد بدء البحث لمنع حدوث أخطاء "صفحة فارغة"
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with()
    {
        $query = Product::with('category');

        // تطبيق البحث الفوري الذكي على الاسم، الوصف، أو القسم
        $searchTerm = trim($this->search);
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('category', function ($catQuery) use ($searchTerm) {
                        $catQuery->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        return [
            'products' => $query->latest()->paginate(10), // استخدام paginate بدلاً من get()
            'categories' => Category::all(),
        ];
    }

    public function openCreateForm()
    {
        $this->resetValidation();
        $this->productId = null;
        $this->category_id = Category::first()?->id ?? '';
        $this->new_category_name = '';
        $this->name = '';
        $this->description = '';
        $this->price = '';
        $this->image_path = null;
        $this->old_image = '';
        $this->is_available = true;
        $this->showFormModal = true;
    }

    public function openEditForm($id)
    {
        $this->resetValidation();
        $product = Product::findOrFail($id);
        $this->productId = $product->id;
        $this->category_id = $product->category_id;
        $this->new_category_name = '';
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->image_path = null;
        $this->old_image = $product->image_path;
        $this->is_available = $product->is_available;
        $this->showFormModal = true;
    }

    public function saveProduct()
    {
        $isNewCategory = $this->category_id === 'new';

        $this->validate([
            'name' => 'required|string|min:3|max:255',
            'price' => 'required|numeric|min:0',
            'is_available' => 'required|boolean',
            'description' => 'nullable|string',
            'category_id' => 'required',
            'new_category_name' => $isNewCategory ? 'required|string|min:2|max:255' : 'nullable',
            'image_path' => 'nullable|image|max:2048',
        ], [
            'name.required' => 'اسم الوجبة مطلوب.',
            'name.min' => 'اسم الوجبة يجب ألا يقل عن 3 حروف.',
            'price.required' => 'سعر الوجبة مطلوب.',
            'category_id.required' => 'يجب اختيار قسم للوجبة.',
            'new_category_name.required' => 'اكتب اسم القسم الجديد.',
            'image_path.image' => 'يجب أن يكون الملف المرفق صورة صحيحة.',
            'image_path.max' => 'حجم الصورة يجب ألا يتعدى 2 ميجابايت.',
        ]);

        if (!$isNewCategory) {
            $this->validate(['category_id' => 'exists:categories,id']);
        }

        // معالجة وحفظ القسم الجديد بشكل آمن
        if ($isNewCategory) {
            $catName = trim($this->new_category_name);
            $catSlug = Str::slug($catName, '-', 'ar');

            // احتياطي في حال لم يتم توليد slug باللغة العربية بشكل صحيح من المكتبة الافتراضية
            if (empty($catSlug)) {
                $catSlug = str_replace(' ', '-', $catName);
            }

            $category = Category::firstOrCreate(
                ['name' => $catName],
                ['slug' => $catSlug]
            );
            $categoryId = $category->id;
        } else {
            $categoryId = $this->category_id;
        }

        // معالجة رفع وحذف الصورة
        if ($this->image_path) {
            $finalImagePath = $this->image_path->store('products', 'public');

            if ($this->productId && $this->old_image && !Str::startsWith($this->old_image, 'http')) {
                Storage::disk('public')->delete($this->old_image);
            }
        } else {
            $finalImagePath = $this->productId ? $this->old_image : 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=500';
        }

        // معالجة slug للمنتج
        $productSlug = Str::slug($this->name, '-', 'ar');
        if (empty($productSlug)) {
            $productSlug = str_replace(' ', '-', trim($this->name));
        }

        $data = [
            'name' => $this->name,
            'slug' => $productSlug,
            'is_available' => $this->is_available,
            'price' => $this->price,
            'description' => $this->description,
            'image_path' => $finalImagePath,
            'category_id' => $categoryId,
        ];

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($data);
            session()->flash('message', 'تم تعديل الوجبة بنجاح! 👍');
        } else {
            Product::create($data);
            session()->flash('message', 'تم إضافة الوجبة بنجاح! 🎉');
        }

        $this->showFormModal = false;
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image_path && !Str::startsWith($product->image_path, 'http')) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();
        session()->flash('message', 'تم حذف الوجبة بنجاح! 🗑️');
    }
};

\Livewire\Volt\layout('components.layouts.app');
?>

<!-- تم نقل الـ x-slot ليكون تحت جذر الـ div مباشرة لإصلاح مشاكل تعارض الـ DOM Diffing في Livewire 3 -->
<div class="p-6 bg-[#0f1115] min-h-screen" dir="rtl">
    <x-slot name="title">إدارة المنتجات</x-slot>

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- الهيدر الموحد والمحسن بالكامل -->
        <nav x-data="{ mobileMenuOpen: false }" class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">

                    <div class="flex items-center gap-10">
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <span
                                class="text-xl font-black bg-gradient-to-r from-[#d4af37] to-[#b8912f] bg-clip-text text-transparent tracking-wide">
                               <span class="text-[#d4af37]"> ☕</span> ELECTRO CAFE
                            </span>
                        </div>

                        <!-- روابط الـ Desktop المحدثة -->
                        <div class="hidden md:flex items-center gap-8">
                            <a href="{{ route('admin.employees-manager') }}"
                                class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">إدارة
                                الموظفين</a>
                            <a href="{{ route('admin.products-manager') }}"
                                class="text-[#d4af37] border-b-2 border-[#d4af37] pb-2 text-sm font-bold tracking-wide">إدارة
                                المنتجات</a>
                            <a href="{{ route('admin.dashboard') }}"
                                class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">لوحة
                                التحكم</a>
                            <a href="{{ route('settings.profile') }}"
                                class="text-gray-400 hover:text-[#d4af37] transition text-sm font-medium">الإعدادات</a>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- بيانات المشرف ديناميكية للـ Desktop -->
                        <div class="hidden md:flex items-center gap-3 border-r border-[#2a2e38] pr-5">
                            <div class="text-left pl-1">
                                <p class="text-sm font-semibold text-gray-200">
                                    {{ auth()->user()->name ?? 'مستخدم النظام' }}
                                </p>
                                <p class="text-[11px] text-gray-500 font-medium text-right">
                                    {{ auth()->user()->role === 'superadmin' ? 'المالك / سوبر أدمن' : 'مدير النظام' }}
                                </p>
                            </div>
                            <div
                                class="w-10 h-10 rounded-xl bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-lg shadow-inner">
                                👨‍💻
                            </div>
                        </div>

                        <!-- زر القائمة للموبايل -->
                        <div class="flex md:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                                class="inline-flex items-center justify-center p-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-[#22252d] border border-transparent hover:border-[#2a2e38] focus:outline-none transition">
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

            <!-- قائمة الموبايل المنسدلة التفاعلية -->
            <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="md:hidden border-t border-[#2a2e38] bg-[#1a1d24] rounded-b-2xl overflow-hidden"
                style="display: none;">
                <div class="px-3 pt-2 pb-4 space-y-1">
                    <a href="{{ route('admin.employees-manager') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37] transition">
                        إدارة الموظفين
                    </a>

                    <a href="{{ route('admin.products-manager') }}"
                        class="block px-4 py-3 rounded-xl text-base font-bold text-[#d4af37] bg-[#22252d] border-r-4 border-[#d4af37]">
                        إدارة المنتجات
                    </a>

                    <a href="{{ route('admin.dashboard') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37] transition">
                        لوحة التحكم
                    </a>

                    <a href="{{ route('settings.profile') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37] transition">
                        الإعدادات
                    </a>

                    <!-- بيانات المشرف للموبايل -->
                    <div class="pt-4 mt-2 border-t border-[#2a2e38] flex items-center gap-3 px-4">
                        <div class="w-10 h-10 rounded-xl bg-[#22252d] flex items-center justify-center text-lg">
                            👨‍💻
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-200">{{ auth()->user()->name ?? 'مستخدم النظام' }}
                            </p>
                            <p class="text-xs text-gray-500">مدير النظام</p>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- رسائل الحفظ والإشعارات -->
        @if (session()->has('message'))
            <div
                class="p-4 mb-2 text-sm text-emerald-300 bg-emerald-900/30 border border-emerald-700/50 rounded-lg font-bold">
                {{ session('message') }}
            </div>
        @endif

        <!-- جدول المنتجات وعناصره -->
        <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-6">

            <!-- الهيدر المدمج بأدوات البحث الفوري والمطابق لصفحة الموظفين -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <h3 class="text-[#d4af37] text-xl font-bold">📦 المنتجات المضافة ({{ $products->total() }})</h3>

                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <!-- حقل البحث الفوري الفخم -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-500">🔍</span>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="ابحث بالاسم أو الوصف أو القسم..."
                            class="w-full pr-9 pl-4 py-2.5 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 text-sm focus:ring-[#d4af37] focus:border-[#d4af37] focus:outline-none placeholder-gray-500 transition">
                    </div>

                    <button wire:click="openCreateForm"
                        class="bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-bold px-5 py-2.5 rounded-xl hover:brightness-110 transition whitespace-nowrap">
                        ➕ إضافة وجبة جديدة
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="text-[#9ca3af] text-sm border-b border-[#2a2e38]">
                            <th class="p-3 font-semibold">صورة المنتج</th>
                            <th class="p-3 font-semibold">اسم الوجبة</th>
                            <th class="p-3 font-semibold">القسم</th>
                            <th class="p-3 font-semibold">السعر</th>
                            <th class="p-3 font-semibold">الحالة</th>
                            <th class="p-3 font-semibold">العمليات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2a2e38]">
                        @forelse ($products as $product)
                            <tr class="hover:bg-[#22252d] transition">
                                <td class="p-3">
                                    <img src="{{ Str::startsWith($product->image_path, 'http') ? $product->image_path : asset('storage/' . $product->image_path) }}"
                                        alt="{{ $product->name }}"
                                        class="w-14 h-14 object-cover rounded-lg border border-[#2a2e38]">
                                </td>
                                <td class="p-3">
                                    <span class="font-semibold text-gray-100">{{ $product->name }}</span>
                                    <small
                                        class="block text-gray-500 max-w-[250px] truncate">{{ $product->description }}</small>
                                </td>
                                <td class="p-3">
                                    <span class="bg-[#2a2e38] text-gray-300 text-xs px-2.5 py-1 rounded-full font-medium">
                                        {{ $product->category?->name ?? 'بدون قسم' }}
                                    </span>
                                </td>
                                <td class="p-3 text-[#d4af37] font-bold">{{ number_format($product->price, 2) }} EGP</td>
                                <td class="p-3">
                                    @if ($product->is_available)
                                        <span
                                            class="bg-emerald-900/40 text-emerald-300 text-xs px-2.5 py-1 rounded-full font-semibold">نشط
                                            / متوفر</span>
                                    @else
                                        <span
                                            class="bg-gray-700/40 text-gray-400 text-xs px-2.5 py-1 rounded-full font-semibold">غير
                                            متوفر</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <div class="flex gap-2">
                                        <button wire:click="openEditForm({{ $product->id }})"
                                            class="border border-[#d4af37] text-[#d4af37] px-3 py-1.5 rounded-lg text-sm hover:bg-[#d4af37] hover:text-black transition">
                                            ✏️ تعديل
                                        </button>
                                        <button wire:click="deleteProduct({{ $product->id }})"
                                            onclick="confirm('هل أنت متأكد من حذف هذه الوجبة؟') || event.stopImmediatePropagation()"
                                            class="bg-red-600/90 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-red-700 transition">
                                            🗑️ حذف
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center text-gray-500">لا يوجد أي وجبات تطابق كلمات البحث أو
                                    مضافة حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- شريط الترقيم للصفحات (Pagination Controls) -->
            @if ($products->hasPages())
                <div class="mt-6 pt-6 border-t border-[#2a2e38] px-2">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- نافذة إضافة وتعديل الوجبات (Modal) -->
    @if ($showFormModal)
        <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4 animate-fadeIn">
            <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl w-full max-w-lg relative">
                <button wire:click="$set('showFormModal', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-white text-2xl leading-none">&times;</button>

                <div class="p-8">
                    <h2 class="text-[#d4af37] text-lg font-bold mb-6 border-b border-[#2a2e38] pb-3">
                        {{ $productId ? '✏️ تعديل بيانات الوجبة' : '➕ إضافة وجبة جديدة' }}
                    </h2>

                    <form wire:submit.prevent="saveProduct" class="space-y-4">
                        <!-- اختيار القسم -->
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">قسم الوجبة *</label>
                            <select wire:model.live="category_id"
                                class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 focus:ring-[#d4af37] focus:border-[#d4af37]">
                                <option value="">اختار القسم</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                                <option value="new">➕ إضافة قسم جديد...</option>
                            </select>
                            @error('category_id') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span>
                            @enderror

                            @if ($category_id === 'new')
                                <input type="text" wire:model="new_category_name" placeholder="اكتب اسم القسم الجديد..."
                                    class="w-full mt-2 p-2.5 bg-[#22252d] border border-[#d4af37]/60 rounded-lg text-gray-100 focus:ring-[#d4af37] focus:border-[#d4af37]">
                                @error('new_category_name') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <!-- اسم الوجبة -->
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">اسم الوجبة *</label>
                            <input type="text" wire:model="name" placeholder="مثال: إسبريسو مزدوج - Espresso Double"
                                class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 focus:ring-[#d4af37] focus:border-[#d4af37]">
                            @error('name') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- السعر والصورة -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-300 mb-1">السعر (EGP) *</label>
                                <input type="number" step="0.01" wire:model="price" placeholder="0.00"
                                    class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 focus:ring-[#d4af37] focus:border-[#d4af37]">
                                @error('price') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm text-gray-300 mb-1">صورة الوجبة</label>
                                <div class="relative">
                                    <input type="file" wire:model="image_path" accept="image/*"
                                        class="w-full p-1.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-400 focus:ring-[#d4af37] focus:border-[#d4af37] file:bg-[#2a2e38] file:border-0 file:text-[#d4af37] file:px-3 file:py-1 file:rounded-md file:cursor-pointer file:ml-2 hover:file:bg-[#2a2e38]/80 text-xs">

                                    <!-- مؤشر التحميل لرفع الصور الكبيرة بشكل مرئي للمستخدم -->
                                    <div wire:loading wire:target="image_path"
                                        class="absolute left-3 top-1/2 -translate-y-1/2 flex items-center gap-1 text-xs text-[#d4af37]">
                                        <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>يرفع...</span>
                                    </div>
                                </div>
                                @error('image_path') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- معاينات الصور -->
                        @if ($image_path)
                            <div class="mt-2 text-center animate-fadeIn">
                                <span class="text-xs text-gray-400 block mb-1">معاينة الصورة المختارة:</span>
                                <img src="{{ $image_path->temporaryUrl() }}"
                                    class="w-24 h-24 object-cover rounded-xl mx-auto border border-[#d4af37] shadow-lg shadow-[#d4af37]/10">
                            </div>
                        @elseif ($productId && $old_image)
                            <div class="mt-2 text-center">
                                <span class="text-xs text-gray-400 block mb-1">الصورة الحالية للوجبة:</span>
                                <img src="{{ Str::startsWith($old_image, 'http') ? $old_image : asset('storage/' . $old_image) }}"
                                    class="w-24 h-24 object-cover rounded-xl mx-auto border border-[#2a2e38]">
                            </div>
                        @endif

                        <!-- وصف الوجبة -->
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">وصف وتفاصيل الوجبة</label>
                            <textarea wire:model="description" rows="3" placeholder="أدخل تفاصيل ومكونات الوجبة..."
                                class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 focus:ring-[#d4af37] focus:border-[#d4af37]"></textarea>
                            @error('description') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- حالة التوفر للطلب -->
                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="avail-check" wire:model="is_available"
                                class="w-[18px] h-[18px] accent-[#d4af37] rounded border-[#2a2e38] focus:ring-0 cursor-pointer">
                            <label for="avail-check" class="text-gray-300 cursor-pointer select-none">الوجبة نشطة ومتوفرة
                                للطلب حالياً</label>
                        </div>

                        <!-- أزرار التحكم والعمليات -->
                        <div class="flex justify-end gap-3 pt-5 border-t border-[#2a2e38]">
                            <button type="button" wire:click="$set('showFormModal', false)"
                                class="border border-[#d4af37] text-[#d4af37] px-4 py-2 rounded-xl hover:bg-[#d4af37] hover:text-black transition">
                                إلغاء
                            </button>

                            <!-- تعطيل الزر لحماية السيرفر من الحفظ قبل اكتمال رفع الصور الكبيرة -->
                            <button type="submit" wire:loading.attr="disabled" wire:target="image_path"
                                class="bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-bold px-5 py-2 rounded-xl hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2">
                                <span wire:loading wire:target="image_path"
                                    class="animate-ping w-2 h-2 rounded-full bg-black"></span>
                                <span>{{ $productId ? 'حفظ التعديلات' : 'إضافة الوجبة' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>