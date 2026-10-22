<?php

use Livewire\Volt\Component;
use App\Models\Order;
use Livewire\Attributes\Layout;
new class extends Component {
    public $preparingOrders;
    public $readyOrders;

    public function mount()
    {
        $this->loadKitchenOrders();
    }

    // جلب طلبات المطبخ للحالتين فقط: قيد التحضير والجاهزة
    public function loadKitchenOrders()
    {
        $this->preparingOrders = Order::where('status', 'preparing')->orWhere('status', 'pending')
            ->with('items.product')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->readyOrders = Order::where('status', 'ready')
            ->with('items.product')
            ->orderBy('updated_at', 'desc') // الأحدث جهوزية يظهر أولاً
            ->get();
    }

    // تحديث حالة الطلب
    public function updateStatus($orderId, $newStatus)
    {
        $order = Order::find($orderId);
        if ($order) {
            $order->update(['status' => $newStatus]);
            
            $message = $newStatus === 'ready' 
                ? "🛎️ الطلب #{$orderId} جاهز للاستلام الآن!" 
                : "✅ تم تسليم الطلب #{$orderId} للعميل وأرشفته.";
                
            session()->flash('kitchen_message', $message);
        }
        $this->loadKitchenOrders();
    }
    
}; ?>

<div wire:poll.3s="loadKitchenOrders" class="w-full min-h-screen bg-[#090b0f] text-gray-100 p-4 sm:p-6" dir="rtl">
    
    <!-- هيدر الشاشة الذكي المطور (يحتوي على العنوان، الإحصائيات، والإعدادات) -->
    <div class="flex flex-col lg:flex-row items-center justify-between border-b border-[#1f2430] pb-4 mb-6 gap-4">
        
        <!-- الجهة اليمنى: الهوية والعنوان -->
        <div class="flex items-center gap-3 w-full lg:w-auto justify-start">
            <span class="text-3xl animate-pulse">👨‍🍳</span>
            <div class="text-right">
                <h1 class="text-xl sm:text-2xl font-black bg-gradient-to-r from-[#29b6f6] to-[#66bb6a] bg-clip-text text-transparent tracking-wider">شاشة إدارة وتجهيز الطلبات</h1>
                <p class="text-xs text-gray-500 font-medium">التحكم الفوري في حالات الطهي والتسليم</p>
            </div>
        </div>
        
        <!-- الجهة اليسرى: تجمع الإحصائيات وزر الإعدادات لتناسق مثالي -->
        <div class="flex items-center justify-between lg:justify-end gap-4 w-full lg:w-auto">
            
            <!-- قسم الـ Stats السريع -->
            <div class="flex gap-3">
                <div class="bg-[#29b6f6]/10 border border-[#29b6f6]/20 px-4 py-2 rounded-xl text-center min-w-[90px]">
                    <span class="block text-[10px] sm:text-xs text-gray-400">بتجهز الآن</span>
                    <span class="text-base sm:text-lg font-black text-[#29b6f6]">{{ count($preparingOrders) }}</span>
                </div>
                <div class="bg-[#66bb6a]/10 border border-[#66bb6a]/20 px-4 py-2 rounded-xl text-center min-w-[90px]">
                    <span class="block text-[10px] sm:text-xs text-gray-400">جهزت خلاص</span>
                    <span class="text-base sm:text-lg font-black text-[#66bb6a]">{{ count($readyOrders) }}</span>
                </div>
            </div>

            <!-- زر الإعدادات (Settings Button) المتناسق مع النظام -->
            <a href="{{ route('settings.profile') }}" class="bg-[#11141c] border border-[#1f2430] hover:border-gray-600 text-gray-300 hover:text-white px-3 py-2.5 sm:py-3 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-lg group focus:outline-none focus:ring-2 focus:ring-[#29b6f6]/50" title="إعدادات المطبخ">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-white group-hover:rotate-45 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-xs font-bold hidden sm:inline">الإعدادات</span>
            </a>
            
        </div>
    </div>

    <!-- رسائل التنبيه السريعة -->
    @if(session()->has('kitchen_message'))
        <div class="bg-[#11141c] border-r-4 border-[#29b6f6] text-sky-400 p-3 rounded-xl mb-6 text-sm font-bold shadow-md animate-bounce">
            {{ session('kitchen_message') }}
        </div>
    @endif

    <!-- لوحة سير العمل الثنائية -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 items-start">
        
        <!-- العمود الأول: طلبات بتجهز (قيد التحضير) -->
        <div class="bg-[#11141c]/60 border border-[#1f2430] rounded-2xl p-4 sm:p-5">
            <h2 class="text-lg font-black text-[#29b6f6] mb-4 flex items-center gap-2 pb-2 border-b border-[#1f2430]">
                <span class="w-2.5 h-2.5 bg-[#29b6f6] rounded-full animate-ping"></span>
                🔥 طلبات قيد التحضير ({{ count($preparingOrders) }})
            </h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[calc(100vh-200px)] overflow-y-auto pr-1">
                @forelse($preparingOrders as $order)
                    <div wire:key="preparing-card-{{ $order->id }}" class="bg-[#11141c] border-2 border-[#29b6f6]/30 hover:border-[#29b6f6] rounded-xl p-4 flex flex-col justify-between shadow-xl transition duration-300 relative group overflow-hidden">
                        
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-base font-black text-white">طلب #{{ $order->id }}</span>
                            <span class="bg-[#29b6f6]/10 text-[#29b6f6] text-xs font-black px-2.5 py-1 rounded-lg border border-[#29b6f6]/20">
                                طاولة {{ $order->table_number }}
                            </span>
                        </div>
                        
                        <div class="text-[11px] text-gray-500 mb-3 flex items-center gap-1">
                            🕒 بدأ تحضيره منذ: <span class="text-gray-300 font-medium">{{ $order->created_at ? $order->created_at->diffForHumans(null, true) : 'الآن' }}</span>
                        </div>

                        <div class="border-t border-[#1f2430] pt-3 space-y-2.5 flex-1">
                            @foreach($order->items as $item)
                                <div class="text-sm">
                                    <div class="flex justify-between items-start">
                                        <span class="font-extrabold text-gray-200 text-base">{{ $item->product->name }}</span>
                                        <span class="bg-gray-800 text-[#d4af37] text-sm font-black px-2 py-0.5 rounded-md min-w-[32px] text-center">
                                            X{{ $item->quantity }}
                                        </span>
                                    </div>
                                    @if($item->notes)
                                        <div class="mt-1 bg-[#29b6f6]/5 border border-dashed border-[#29b6f6]/30 rounded-lg p-2 text-xs text-[#29b6f6] font-semibold">
                                            ⚠️ ملاحظة: {{ $item->notes }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($order->notes)
                            <div class="mt-3 text-xs bg-gray-900 text-gray-400 p-2 rounded-lg border border-[#1f2430]">
                                💬 ملاحظة عامة: {{ $order->notes }}
                            </div>
                        @endif

                        <button type="button" 
                                wire:click="updateStatus({{ $order->id }}, 'ready')"
                                class="w-full mt-4 bg-gradient-to-r from-[#29b6f6] to-[#0288d1] hover:from-[#0288d1] hover:to-[#29b6f6] text-white font-black text-sm py-3 rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                            🛎️ خلاص جهز.. نقله للجاهز
                        </button>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-600">
                        <span class="text-4xl block mb-2">💤</span>
                        <p class="text-sm font-medium">لا توجد طلبات يتم تجهيزها حالياً.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- العمود الثاني: طلبات جهزت (جاهزة للاستلام) -->
        <div class="bg-[#11141c]/60 border border-[#1f2430] rounded-2xl p-4 sm:p-5">
            <h2 class="text-lg font-black text-[#66bb6a] mb-4 flex items-center gap-2 pb-2 border-b border-[#1f2430]">
                <span class="w-2.5 h-2.5 bg-[#66bb6a] rounded-full animate-pulse"></span>
                ✅ طلبات جهزت خلاص ({{ count($readyOrders) }})
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[calc(100vh-200px)] overflow-y-auto pr-1">
                @forelse($readyOrders as $order)
                    <div wire:key="ready-card-{{ $order->id }}" class="bg-[#11141c] border-2 border-[#66bb6a]/30 hover:border-[#66bb6a] rounded-xl p-4 flex flex-col justify-between shadow-xl transition duration-300 relative overflow-hidden">
                        
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-base font-black text-white">طلب #{{ $order->id }}</span>
                            <span class="bg-[#66bb6a]/10 text-[#66bb6a] text-xs font-black px-2.5 py-1 rounded-lg border border-[#66bb6a]/20">
                                طاولة {{ $order->table_number }}
                            </span>
                        </div>
                        
                        <div class="text-[11px] text-gray-500 mb-3 flex items-center gap-1">
                            🔔 جاهز منذ: <span class="text-[#66bb6a] font-semibold">{{ $order->updated_at ? $order->updated_at->diffForHumans(null, true) : 'قليل' }}</span>
                        </div>

                        <div class="border-t border-[#1f2430] pt-3 space-y-2.5 flex-1">
                            @foreach($order->items as $item)
                                <div class="text-sm">
                                    <div class="flex justify-between items-start">
                                        <span class="font-extrabold text-gray-400 line-through text-base">{{ $item->product->name }}</span>
                                        <span class="bg-gray-800 text-gray-400 text-sm font-black px-2 py-0.5 rounded-md min-w-[32px] text-center">
                                            X{{ $item->quantity }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" 
                                wire:click="updateStatus({{ $order->id }}, 'completed')"
                                class="w-full mt-4 bg-gradient-to-r from-[#66bb6a] to-[#43a047] hover:from-[#43a047] hover:to-[#66bb6a] text-white font-black text-sm py-3 rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                            📦 تم تسليم الطلب وأرشفته
                        </button>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-600">
                        <span class="text-4xl block mb-2">🍽️</span>
                        <p class="text-sm font-medium">لا توجد طلبات جاهزة بانتظار التسليم.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>