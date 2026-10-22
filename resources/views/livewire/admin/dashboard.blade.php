<?php

use Livewire\Volt\Component;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {

    public function with()
    {
        // 1. تثبيت تاريخ اليوم في متغير لتوفير الـ Queries
        $today = today();
        $thisMonth = today()->month;
        // 2. حساب إجمالي المبيعات وعدد الطلبات لليوم
        $today_total_sales = Order::whereDate('created_at', $today)->sum('total_amount') ?? 0;
        $orders_count = Order::whereDate('created_at', $today)->count();
        $month_total_sales = Order::whereMonth('created_at', $thisMonth)->sum('total_amount') ?? 0;
        // 3. حساب عدد القطع المباعة اليوم عبر Join سريع
        $total_items_sold = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $today)
            ->sum('order_items.quantity') ?? 0;

        // 4. جلب المنتج الأكثر مبيعاً اليوم تلقائياً من الداتا بيز
        $top_product_query = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $today)
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->first();

        // جلب الاسم بناءً على النتيجة (أو وضع اسم افتراضي لو مفيش مبيعات)
        $top_product = $top_product_query && $top_product_query->product
            ? $top_product_query->product->name
            : 'لا توجد مبيعات بعد';

        // 5. جلب آخر 4 طلبات حقيقية من الداتا بيز وعمل Mapping لتطابق الـ UI الفخم
        $recent_orders = Order::whereDate('created_at', $today)
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($order) {
                // تحديد لون الحالة تلقائياً بناءً على حالة الأوردر عندك في السيستم
                $status_color = match ($order->status) {
                    'completed', 'تم التوصيل' => 'emerald',
                    'pending', 'جاري التجهيز' => 'amber',
                    'cancelled', 'ملغي' => 'rose',
                    default => 'amber',
                };

                return [
                    'id' => '#' . $order->id,
                    // لو الأوردر لطاولة اظهر رقمها، لو تيك اواي اظهر سفري
                    'customer' => $order->table_number ? 'طاولة ' . $order->table_number : 'سفري (تيك أواي)',
                    'time' => $order->created_at->diffForHumans(), // هيطلع: "منذ دقيقتين"، "منذ ساعة".. تلقائياً بالعربي
                    'total' => $order->total_amount,
                    'status' => $order->status,
                    'status_color' => $status_color,
                ];
            })->toArray();

        // ==========================================
        // 📊 6. داتا الرسم البياني للمبيعات (آخر 7 أيام)
        // ==========================================
        $sales_data = [];
        $sales_labels = [];

        // هنلف على آخر 7 أيام ونجيب مبيعات كل يوم
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i); // بنرجع لورا بالأيام

            // جلب اسم اليوم بالعربي (السبت، الأحد، الإثنين...)
            $sales_labels[] = $date->translatedFormat('l');

            // حساب مبيعات هذا اليوم
            $sales_data[] = Order::whereDate('created_at', $date)->sum('total_amount') ?? 0;
        }

        // ==========================================
        // 🍩 7. داتا الرسم البياني الدائري (مبيعات الأقسام)
        // ==========================================
        // بنعمل Join عشان نجيب الأقسام ونجمع كميات المنتجات المباعة في كل قسم
        $categories_sales = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(order_items.quantity) as total_quantity'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_quantity')
            ->take(4) // هنجيب أعلى 4 أقسام بس عشان شكل الدائرة يبقى حلو
            ->get();

        $categories_data = $categories_sales->pluck('total_quantity')->map(fn($val) => (int) $val)->toArray();
        $categories_labels = $categories_sales->pluck('name')->toArray();

        // ==========================================
        // 🚀 الـ Return الموحد والنهائي لكل البيانات إلى الـ View
        // ==========================================
        return [
            'today_total_sales' => $today_total_sales,
            'month_total_sales' => $month_total_sales,
            'orders_count' => $orders_count,
            'active_staff' => 6, // تقدر تربطها بعدين بـ User::where('role', 'waiter')->count()
            'top_product' => $top_product,
            'total_items_sold' => $total_items_sold,
            'recent_orders' => $recent_orders,
            'sales_chart_data' => $sales_data,
            'sales_chart_labels' => $sales_labels,
            'categories_chart_data' => $categories_data,
            'categories_chart_labels' => $categories_labels,
        ];
    }
};

\Livewire\Volt\layout('components.layouts.app');
?>
<x-slot name="title">لوحة التحكم الرئيسية</x-slot>

<!-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> -->

<div class="p-6 bg-[#0f1115] min-h-screen text-gray-100" dir="rtl">
    <div class="max-w-7xl mx-auto space-y-6">

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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="space-y-1 z-10">
                    <p class="text-xs text-gray-400 font-medium">إجمالي مبيعات الشهر</p>
                    <p class="text-2xl font-black text-[#d4af37] tracking-wide">{{ number_format($month_total_sales) }} EGP
                    </p>
                    <!-- <span class="text-[11px] text-emerald-400 font-medium bg-emerald-500/10 px-2 py-0.5 rounded-md">📈
                        +12% عن الأسبوع الماضي</span> -->
                </div>
                <div
                    class="w-12 h-12 rounded-xl bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-xl shadow-inner group-hover:scale-110 transition duration-300">
                    💰</div>
            </div>

            <div
                class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="space-y-1 z-10">
                    <p class="text-xs text-gray-400 font-medium">إجمالي مبيعات اليوم</p>
                    <p class="text-2xl font-black text-[#d4af37] tracking-wide">{{ number_format($today_total_sales) }} EGP
                    </p>
                    <span class="text-[11px] text-gray-400 font-medium bg-gray-500/10 px-2 py-0.5 rounded-md">🔄
                        مُحَدَّثة</span>
                </div>
                <div
                    class="w-12 h-12 rounded-xl bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-xl shadow-inner group-hover:scale-110 transition duration-300">
                    📊</div>
            </div>

            <div
                class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="space-y-1 z-10">
                    <p class="text-xs text-gray-400 font-medium">طلبات اليوم</p>
                    <p class="text-2xl font-black text-[#d4af37] tracking-wide">{{ $orders_count }} طلب</p>
                    <span class="text-[11px] text-emerald-400 font-medium bg-emerald-500/10 px-2 py-0.5 rounded-md">
                        📦 تم بيع {{ $total_items_sold }} منتج/وجبة
                    </span>
                </div>
                <div
                    class="w-12 h-12 rounded-xl bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-xl shadow-inner group-hover:scale-110 transition duration-300">
                    🧾</div>
            </div>

            <div
                class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                <div class="space-y-1 z-10">
                    <p class="text-xs text-gray-400 font-medium">الأكثر طلباً اليوم</p>
                    <p class="text-xl font-bold text-[#d4af37] truncate max-w-[160px]">{{ $top_product }}</p>
                    <span class="text-[11px] text-[#d4af37] font-medium bg-[#d4af37]/10 px-2 py-0.5 rounded-md">🔥
                        الأعلى مبيعاً</span>
                </div>
                <div
                    class="w-12 h-12 rounded-xl bg-[#22252d] border border-[#2a2e38] flex items-center justify-center text-xl shadow-inner group-hover:scale-110 transition duration-300">
                    🔥</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-6 lg:col-span-2 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-base font-bold text-gray-100">تحليلات الأرباح والمبيعات</h4>
                        <p class="text-xs text-gray-500">معدل الإيرادات اليومية للأسبوع الحالي</p>
                    </div>
                    <span class="text-xs bg-[#22252d] border border-[#2a2e38] text-gray-400 px-3 py-1 rounded-xl">تحديث
                        فوري 🔄</span>
                </div>
                <div id="salesChart" class="w-full"></div>
            </div>

            <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h4 class="text-base font-bold text-gray-100">نسبة مبيعات الأقسام</h4>
                    <p class="text-xs text-gray-500">أكثر الأقسام طلباً في الكافيه</p>
                </div>
                <div id="categoriesChart" class="py-4 flex justify-center"></div>
            </div>
        </div>

        <div class="bg-[#1a1d24] border border-[#2a2e38] rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-base font-bold text-gray-100">آخر الطلبات المستلمة</h4>
                    <p class="text-xs text-gray-500">متابعة حية لحالة الطلبات في الصالة والسفري</p>
                </div>
                <!-- <button class="text-xs text-[#d4af37] hover:underline font-semibold">عرض كل الطلبات ←</button> -->
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="text-gray-400 text-xs border-b border-[#2a2e38]">
                            <th class="p-3 font-semibold">رقم الطلب</th>
                            <th class="p-3 font-semibold">العميل / الطاولة</th>
                            <th class="p-3 font-semibold">التوقيت</th>
                            <th class="p-3 font-semibold">الإجمالي</th>
                            <th class="p-3 font-semibold">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2a2e38] text-sm">
                        @foreach($recent_orders as $order)
                            <tr class="hover:bg-[#22252d]/50 transition">
                                <td class="p-3 font-mono font-bold text-gray-300">{{ $order['id'] }}</td>
                                <td class="p-3 font-semibold text-gray-100">{{ $order['customer'] }}</td>
                                <td class="p-3 text-gray-400 text-xs">{{ $order['time'] }}</td>
                                <td class="p-3 text-[#d4af37] font-bold">{{ number_format($order['total']) }} EGP</td>
                                <td class="p-3">
                                    @if($order['status_color'] === 'amber')
                                        <span
                                            class="bg-amber-500/10 text-amber-400 text-xs px-2.5 py-1 rounded-lg font-medium border border-amber-500/20">⏳
                                            {{ $order['status'] }}</span>
                                    @elseif($order['status_color'] === 'emerald')
                                        <span
                                            class="bg-emerald-500/10 text-emerald-400 text-xs px-2.5 py-1 rounded-lg font-medium border border-emerald-500/20">✔
                                            {{ $order['status'] }}</span>
                                    @else
                                        <span
                                            class="bg-rose-500/10 text-rose-400 text-xs px-2.5 py-1 rounded-lg font-medium border border-rose-500/20">❌
                                            {{ $order['status'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        // 1. إعدادات الرسم البياني للمبيعات 📈
        var salesOptions = {
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'inherit',
                background: 'transparent'
            },
            theme: { mode: 'dark' },
            colors: ['#d4af37'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.02, stops: [0, 90, 100] }
            },
            grid: {
                borderColor: '#2a2e38',
                strokeDashArray: 4,
            },
            series: [{
                name: 'المبيعات اليومية',
                data: @json($sales_chart_data)
            }],
            xaxis: {
                categories: @json($sales_chart_labels),
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { colors: '#9ca3af' } }
            },
            yaxis: {
                labels: {
                    style: { colors: '#9ca3af' },
                    formatter: function (value) { return value + " EGP"; }
                }
            },
            tooltip: { theme: 'dark' }
        };
        var salesChart = new ApexCharts(document.querySelector("#salesChart"), salesOptions);
        salesChart.render();

        // 2. إعدادات الرسم البياني لتوزيع الأصناف 🍩
        var categoriesOptions = {
            chart: {
                type: 'donut',
                height: 290,
                background: 'transparent'
            },
            theme: { mode: 'dark' },
            colors: ['#919191', '#b8912f', '#744f41', '#C6A484'],
            series: @json($categories_chart_data),
            labels: @json($categories_chart_labels),
            legend: {
                position: 'bottom',
                fontFamily: 'inherit',
                labels: { colors: '#9ca3af' },
                style: { fontSize: '13px', fontWeight: 1000 }
            },
            dataLabels: { enabled: false },
            stroke: { show: false },
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%',
                        labels: {
                            show: true,
                            name: { style: { colors: '#9ca3af' } },
                            value: {
                                color: '#d4af37',
                                fontSize: '20px',
                                fontWeight: 'bold',
                                formatter: function (val) { return val + " قطعة"; }
                            },
                            total: {
                                show: true,
                                label: 'الأعلى طلباً',
                                color: '#9ca3af',
                                formatter: function (w) {
                                    return w.globals.labels[0] || 'لا توجد بيانات';
                                }
                            }
                        }
                    }
                }
            },
            tooltip: { theme: 'dark' }
        };
        var categoriesChart = new ApexCharts(document.querySelector("#categoriesChart"), categoriesOptions);
        categoriesChart.render();

    });
</script>