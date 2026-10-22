<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Livewire\Attributes\Url; // استيراد الأداة لربط البحث بالرابط

new class extends Component {
    use WithPagination;

    // خواص الحقول
    public $userId = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role = '';
    public $showFormModal = false;

    // ربط خاصية البحث بالرابط لسهولة التنقل والحفاظ على النتائج عند التحديث
    #[Url(history: true)]
    public $search = '';

    // تصفير الترقيم فوراً بعد تعديل نص البحث لتبدأ النتائج من الصفحة الأولى
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        // حماية برمجية صارمة لمنع غير المدراء من الوصول للصفحة
        $currentUserRole = auth()->user()?->role;
        if (!in_array($currentUserRole, ['superadmin', 'admin'])) {
            abort(403, 'غير مصرح لك بدخول هذه الصفحة.');
        }
    }

    public function with()
    {
        $currentUser = auth()->user();
        $query = User::query();

        // تطبيق فلتر البحث الفوري وتطهير النص من الفراغات الزائدة
        $searchTerm = trim($this->search);
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }

        // جلب المستخدمين بناءً على الصلاحيات المتاحة
        if ($currentUser->role === 'superadmin') {
            $query->where('id', '!=', $currentUser->id);
        } else {
            $query->whereIn('role', ['chief', 'waiter', 'user']);
        }

        return [
            'users' => $query->latest()->paginate(10),
            'allowedRoles' => $this->getAllowedRoles(),
        ];
    }

    public function getAllowedRoles()
    {
        $currentUserRole = auth()->user()?->role;

        if ($currentUserRole === 'superadmin') {
            return [
                'admin' => 'مدير نظام (Admin)',
                'chief' => 'شيف المطبخ (Chief)',
                'waiter' => 'ويتر / صالة (Waiter)',
                'user' => 'عميل عادي (User)',
            ];
        }

        if ($currentUserRole === 'admin') {
            return [
                'chief' => 'شيف المطبخ (Chief)',
                'waiter' => 'ويتر / صالة (Waiter)',
                'user' => 'عميل عادي (User)',
            ];
        }

        return [];
    }

    public function openCreateForm()
    {
        $this->resetValidation();
        $this->resetFormFields();

        $allowed = array_keys($this->getAllowedRoles());
        $this->role = $allowed[0] ?? 'user';

        $this->showFormModal = true;
    }

    public function openEditForm($id)
    {
        $this->resetValidation();
        $user = User::findOrFail($id);
        $allowedRoles = array_keys($this->getAllowedRoles());

        if (!in_array($user->role, $allowedRoles)) {
            session()->flash('error', 'غير مسموح لك بتعديل بيانات هذا المستخدم لحماية الصلاحيات!');
            return;
        }

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = $user->role;
        $this->showFormModal = true;
    }

    public function saveUser()
    {
        $allowedRoles = array_keys($this->getAllowedRoles());

        $this->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId)
            ],
            'password' => $this->userId ? 'nullable|string|min:6|confirmed' : 'required|string|min:6|confirmed',
            'role' => ['required', Rule::in($allowedRoles)],
        ], [
            'name.required' => 'اسم الموظف مطلوب.',
            'name.min' => 'الاسم يجب ألا يقل عن 3 حروف.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'يرجى كتابة بريد إلكتروني صحيح.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم لحساب آخر بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة للموظف الجديد.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين، يرجى التأكد.',
            'role.required' => 'يجب تحديد رتبة/دور الموظف.',
            'role.in' => 'الرتبة المحددة غير مصرح لك بتعيينها.',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);

            if (!in_array($user->role, $allowedRoles)) {
                session()->flash('error', 'إجراء غير مصرح به!');
                $this->showFormModal = false;
                return;
            }

            $user->update($data);
            session()->flash('message', 'تم تعديل بيانات الموظف بنجاح! 👍');
        } else {
            User::create($data);
            session()->flash('message', 'تم إضافة الموظف الجديد بنجاح! 🎉');
        }

        $this->showFormModal = false;
        $this->resetFormFields();
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $allowedRoles = array_keys($this->getAllowedRoles());

        if ($user->id === auth()->id()) {
            session()->flash('error', 'لا يمكنك حذف حسابك الشخصي النشط حالياً!');
            return;
        }

        if (!in_array($user->role, $allowedRoles)) {
            session()->flash('error', 'غير مصرح لك بحذف هذا المستخدم.');
            return;
        }

        $user->delete();
        session()->flash('message', 'تم إزالة الحساب بنجاح! 🗑️');
    }

    private function resetFormFields()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = '';
    }
};

\Livewire\Volt\layout('components.layouts.app');
?>

<!-- تم نقل عنصر الـ x-slot ليكون داخل الـ div الرئيسي مباشرة لضمان جذر HTML واحد متكامل للمكون -->
<div class="p-6 bg-[#0f1115] min-h-screen" dir="rtl">
    <x-slot name="title">إدارة الموظفين والمسؤولين</x-slot>

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- الهيدر والنافر الفخم -->
        <nav x-data="{ mobileMenuOpen: false }" class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <!-- اللوجو والقائمة الرئيسية -->
                    <div class="flex items-center gap-10">
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <span
                                class="text-xl font-black bg-gradient-to-r from-[#d4af37] to-[#b8912f] bg-clip-text text-transparent tracking-wide">
                                <span class="text-[#d4af37]">☕</span> ELECTRO CAFE
                            </span>
                        </div>
        
                        <!-- روابط الشاشات الكبيرة (Desktop Nav) -->
                        <div class="hidden md:flex items-center gap-8">
                            <a href="{{ route('admin.employees-manager') }}"
                                class="transition text-sm {{ request()->routeIs('admin.employees-manager') ? 'text-[#d4af37] border-b-2 border-[#d4af37] pb-2 font-bold' : 'text-gray-400 hover:text-[#d4af37] font-medium' }}">
                                إدارة الموظفين
                            </a>
                            <a href="{{ route('admin.products-manager') }}"
                                class="transition text-sm {{ request()->routeIs('admin.products-manager') ? 'text-[#d4af37] border-b-2 border-[#d4af37] pb-2 font-bold' : 'text-gray-400 hover:text-[#d4af37] font-medium' }}">
                                إدارة المنتجات
                            </a>
                            <a href="{{ route('admin.dashboard') }}"
                                class="transition text-sm {{ request()->routeIs('admin.dashboard') ? 'text-[#d4af37] border-b-2 border-[#d4af37] pb-2 font-bold tracking-wide' : 'text-gray-400 hover:text-[#d4af37] font-medium' }}">
                                لوحة التحكم
                            </a>
                            <a href="{{ route('settings.profile') }}"
                                class="transition text-sm {{ request()->routeIs('settings.profile') ? 'text-[#d4af37] border-b-2 border-[#d4af37] pb-2 font-bold' : 'text-gray-400 hover:text-[#d4af37] font-medium' }}">
                                الإعدادات
                            </a>
                        </div>
                    </div>
        
                    <!-- معلومات المستخدم وأيقونة الموبايل -->
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
                                👨‍💻</div>
                        </div>
        
                        <!-- زر القائمة للموبايل -->
                        <div class="flex md:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                                class="p-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-[#22252d] border border-transparent hover:border-[#2a2e38] transition">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" class="hidden"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- قائمة الموبايل المنسدلة (Mobile Dropdown) -->
            <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="md:hidden border-t border-[#2a2e38] bg-[#1a1d24] rounded-b-2xl overflow-hidden">
        
                <div class="px-3 pt-3 pb-4 space-y-1.5">
                    <a href="{{ route('admin.employees-manager') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium transition {{ request()->routeIs('admin.employees-manager') ? 'bg-[#22252d] text-[#d4af37] font-bold border-r-4 border-[#d4af37]' : 'text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]' }}">
                        إدارة الموظفين
                    </a>
        
                    <a href="{{ route('admin.products-manager') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium transition {{ request()->routeIs('admin.products-manager') ? 'bg-[#22252d] text-[#d4af37] font-bold border-r-4 border-[#d4af37]' : 'text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]' }}">
                        إدارة المنتجات
                    </a>
        
                    <a href="{{ route('admin.dashboard') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-[#22252d] text-[#d4af37] font-bold border-r-4 border-[#d4af37]' : 'text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]' }}">
                        لوحة التحكم
                    </a>
        
                    <a href="{{ route('settings.profile') }}"
                        class="block px-4 py-3 rounded-xl text-base font-medium transition {{ request()->routeIs('settings.profile') ? 'bg-[#22252d] text-[#d4af37] font-bold border-r-4 border-[#d4af37]' : 'text-gray-300 hover:bg-[#22252d] hover:text-[#d4af37]' }}">
                        الإعدادات
                    </a>
        
                    <!-- تفاصيل حساب المستخدم داخل قائمة الموبايل -->
                    <div class="pt-3 mt-2 border-t border-[#2a2e38]/60 px-2 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-200">{{ auth()->user()->name ?? 'مستخدم النظام' }}</p>
                            <p class="text-xs text-gray-500">
                                {{ auth()->user()->role === 'superadmin' ? 'المالك / سوبر أدمن' : 'مدير النظام' }}
                            </p>
                        </div>
                        <div
                            class="w-9 h-9 rounded-lg bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-sm">
                            👨‍💻</div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- رسائل النجاح والخطأ -->
        @if (session()->has('message'))
            <div
                class="p-4 mb-2 text-sm text-emerald-300 bg-emerald-900/30 border border-emerald-700/50 rounded-lg font-bold">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 mb-2 text-sm text-red-300 bg-red-900/30 border border-red-700/50 rounded-lg font-bold">
                {{ session('error') }}
            </div>
        @endif

        <!-- الجدول والمحتوى الأساسي -->
        <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-6">

            <!-- هيدر يحتوي على التعداد والبحث وزر الإضافة -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <h3 class="text-[#d4af37] text-xl font-bold">👥 طاقم العمل والمسؤولين ({{ $users->total() }})</h3>

                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <!-- حقل البحث الفوري والمحسّن بالتحديث المباشر -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-500">🔍</span>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="ابحث بالاسم أو البريد..."
                            class="w-full pr-9 pl-4 py-2.5 bg-[#22252d] border border-[#2a2e38] rounded-xl text-gray-100 text-sm focus:ring-[#d4af37] focus:border-[#d4af37] focus:outline-none placeholder-gray-500 transition">
                    </div>

                    <button wire:click="openCreateForm"
                        class="bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-bold px-5 py-2.5 rounded-xl hover:brightness-110 transition whitespace-nowrap">
                        ➕ إضافة حساب جديد
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="text-[#9ca3af] text-sm border-b border-[#2a2e38]">
                            <th class="p-3 font-semibold">الموظف</th>
                            <th class="p-3 font-semibold">البريد الإلكتروني</th>
                            <th class="p-3 font-semibold">الصلاحية / الرتبة</th>
                            <th class="p-3 font-semibold">تاريخ الانضمام</th>
                            <th class="p-3 font-semibold">العمليات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2a2e38]">
                        @forelse ($users as $user)
                            <tr class="hover:bg-[#22252d] transition">
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full bg-[#2a2e38] flex items-center justify-center text-sm border border-[#3b3f4a]">
                                            👤
                                        </div>
                                        <span class="font-semibold text-gray-100">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="p-3 text-gray-300">{{ $user->email }}</td>
                                <td class="p-3">
                                    @if ($user->role === 'superadmin')
                                        <span
                                            class="bg-red-900/40 text-red-400 text-xs px-2.5 py-1 rounded-full font-bold border border-red-800/50">سوبير
                                            أدمن</span>
                                    @elseif ($user->role === 'admin')
                                        <span
                                            class="bg-blue-900/40 text-blue-300 text-xs px-2.5 py-1 rounded-full font-bold border border-blue-800/50">مدير
                                            نظام</span>
                                    @elseif ($user->role === 'chief')
                                        <span
                                            class="bg-amber-900/40 text-amber-300 text-xs px-2.5 py-1 rounded-full font-bold border border-amber-800/50">شيف
                                            المطبخ</span>
                                    @elseif ($user->role === 'waiter')
                                        <span
                                            class="bg-teal-900/40 text-teal-300 text-xs px-2.5 py-1 rounded-full font-bold border border-teal-800/50">ويتر
                                            صالة</span>
                                    @else
                                        <span
                                            class="bg-gray-800 text-gray-400 text-xs px-2.5 py-1 rounded-full font-semibold">عميل
                                            عادي</span>
                                    @endif
                                </td>
                                <td class="p-3 text-gray-400 text-sm">{{ $user->created_at->format('Y-m-d') }}</td>
                                <td class="p-3">
                                    <div class="flex gap-2">
                                        <button wire:click="openEditForm({{ $user->id }})"
                                            class="border border-[#d4af37] text-[#d4af37] px-3 py-1.5 rounded-lg text-sm hover:bg-[#d4af37] hover:text-black transition">
                                            ✏️ تعديل
                                        </button>
                                        <button wire:click="deleteUser({{ $user->id }})"
                                            onclick="confirm('هل أنت متأكد من إزالة حساب هذا الموظف؟') || event.stopImmediatePropagation()"
                                            class="bg-red-600/90 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-red-700 transition">
                                            🗑️ حذف
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center text-gray-500">لا يوجد موظفون مضافون يطابقون
                                    صلاحياتك حالياً أو كلمات البحث.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- أزرار الترقيم -->
            @if ($users->hasPages())
                <div class="mt-6 pt-6 border-t border-[#2a2e38] px-2">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- نافذة المودال -->
    @if ($showFormModal)
        <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
            <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl w-full max-w-lg relative shadow-2xl">
                <button wire:click="$set('showFormModal', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-white text-2xl leading-none">&times;</button>

                <div class="p-8">
                    <h2 class="text-[#d4af37] text-lg font-bold mb-6 border-b border-[#2a2e38] pb-3">
                        {{ $userId ? '✏️ تعديل بيانات الموظف' : '➕ إضافة حساب موظف جديد' }}
                    </h2>

                    <form wire:submit.prevent="saveUser" class="space-y-4">

                        <div>
                            <label class="block text-sm text-gray-300 mb-1">الرتبة / الصلاحيات الممنوحة *</label>
                            <select wire:model="role"
                                class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 focus:ring-[#d4af37] focus:border-[#d4af37]">
                                @foreach ($allowedRoles as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('role') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm text-gray-300 mb-1">اسم الموظف بالكامل *</label>
                            <input type="text" wire:model="name" placeholder="مثال: أحمد رامي"
                                class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 focus:ring-[#d4af37] focus:border-[#d4af37]">
                            @error('name') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm text-gray-300 mb-1">البريد الإلكتروني (لتسجيل الدخول) *</label>
                            <input type="email" wire:model="email" placeholder="example@electrocafe.com"
                                class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 text-left"
                                dir="ltr">
                            @error('email') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-300 mb-1">
                                    كلمة المرور
                                    @if($userId) <span class="text-[10px] text-gray-500 font-normal">(اختياري)</span> @else
                                    * @endif
                                </label>
                                <input type="password" wire:model="password" placeholder="••••••••"
                                    class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 text-left"
                                    dir="ltr">
                                @error('password') <span class="text-sm text-red-400 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm text-gray-300 mb-1">
                                    تأكيد كلمة المرور
                                    @if($userId) <span class="text-[10px] text-gray-500 font-normal">(اختياري)</span> @else
                                    * @endif
                                </label>
                                <input type="password" wire:model="password_confirmation" placeholder="••••••••"
                                    class="w-full p-2.5 bg-[#22252d] border border-[#2a2e38] rounded-lg text-gray-100 text-left"
                                    dir="ltr">
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-5 border-t border-[#2a2e38]">
                            <button type="button" wire:click="$set('showFormModal', false)"
                                class="border border-[#d4af37] text-[#d4af37] px-4 py-2 rounded-xl hover:bg-[#d4af37] hover:text-black transition">
                                إلغاء
                            </button>
                            <button type="submit"
                                class="bg-gradient-to-l from-[#d4af37] to-[#b8912f] text-black font-bold px-5 py-2 rounded-xl hover:brightness-110 transition">
                                {{ $userId ? 'حفظ التعديلات' : 'إنشاء الحساب' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>