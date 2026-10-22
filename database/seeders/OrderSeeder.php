<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // جلب أول يوزر.. لو الجدول فاضي هيكريت يوزر أدمن فوراً
        $user = User::first();

        if (!$user) {
            $user = User::create([
                'name' => 'أحمد رامي',
                'email' => 'admin@cafe.com',
                'password' => bcrypt('password'),
            ]);
            $this->command->info('تم إنشاء مستخدم أدمن افتراضي لربط الطلبات به.');
        }

        $userId = $user->id;

        // جلب المنتجات
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('يا هندسة، لازم تشغل ProductSeeder الأول عشان نلاقي منتجات!');
            return;
        }

        $status = 'completed';

        // اللف على آخر 7 أيام لتغذية الـ Chart
        for ($i = 7; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $ordersCount = rand(5, 12);

            for ($j = 0; $j < $ordersCount; $j++) {

                // 1. إنشاء الأوردر الأساسي
                $order = Order::create([
                    'user_id' => $userId,
                    'table_number' => rand(1, 12),
                    'status' => $status,
                    'total_amount' => 0,
                    'created_at' => $date->copy()->addHours(rand(8, 23))->addMinutes(rand(0, 59)),
                ]);

                $totalAmount = 0;
                $itemsCount = rand(1, 4);
                $randomProducts = $products->random($itemsCount);

                foreach ($randomProducts as $product) {
                    $quantity = rand(1, 3);

                    // 2. إنشاء تفاصيل الأوردر وتمرير السعر 🚀
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $product->price, // 👈 السطر السحري اللي حل المشكلة تماماً
                    ]);

                    $totalAmount += ($product->price * $quantity);
                }

                // 3. تحديث إجمالي الأوردر
                $order->update([
                    'total_amount' => $totalAmount
                ]);
            }
        }

        $this->command->info('تم تفجير الداتا بيز بالأوردرات بنجاح! 🚀');
    }
}