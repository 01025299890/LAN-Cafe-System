<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menuData = [
            [
                'category_name' => 'مشروبات ساخنة',
                'products' => [
                    ['name' => 'إسبريسو دبل', 'price' => 45, 'description' => 'قهوة إسبريسو مركزة من أجود حبوب البن'],
                    ['name' => 'لاتيه كافيه', 'price' => 60, 'description' => 'إسبريسو مع حليب مبخر وطبقة رغوة ناعمة'],
                    ['name' => 'كابتشينو', 'price' => 65, 'description' => 'توازن مثالي بين الإسبريسو والحليب والرغوة الكثيفة'],
                    ['name' => 'فلات وايت', 'price' => 60, 'description' => 'جرعة مزدوجة من الإسبريسو مع حليب مخملي'],
                ]
            ],
            [
                'category_name' => 'مشروبات باردة',
                'products' => [
                    ['name' => 'آيس لاتيه كراميل', 'price' => 75, 'description' => 'إسبريسو بارد مع حليب ونكهة الكراميل الفاخرة'],
                    ['name' => 'سبانش لاتيه بارد', 'price' => 80, 'description' => 'مزيج مميز من الحليب المكثف المحلى والإسبريسو والثلج'],
                    ['name' => 'مكس بيري سموذي', 'price' => 70, 'description' => 'مزيج منعش من التوت الطبيعي المثلج'],
                    ['name' => 'موهيتو بلوبيري', 'price' => 65, 'description' => 'أوراق النعناع الطازج مع الليمون، الصودا ونكهة التوت'],
                ]
            ],
            [
                'category_name' => 'الحلويات والمخبوزات',
                'products' => [
                    ['name' => 'مولتن كيك الشوكولاتة', 'price' => 90, 'description' => 'كيك الشوكولاتة الغنية بقلب ذائب يقدم ساخناً'],
                    ['name' => 'تشيز كيك لوتس', 'price' => 95, 'description' => 'طبقات التشيز كيك الكريمية مغطاة بزبدة وبسكويت اللوتس'],
                    ['name' => 'كرواسون زبدة فرنسي', 'price' => 45, 'description' => 'كرواسون هش ومقرمش مخبوز طازجاً بالزبدة النيوزيلاندية'],
                    ['name' => 'سيدام كوكيز شوكولاتة', 'price' => 35, 'description' => 'كوكيز مقرمش من الأطراف وطري من الداخل بقطع الشوكولاتة'],
                ]
            ]
        ];

        foreach ($menuData as $data) {
            // توليد الـ slug للقسم فقط يدعم العربي
            $categorySlug = preg_replace('/\s+/u', '-', trim($data['category_name']));

            // 1. إنشاء القسم أو جلبه لو موجود
            $category = Category::firstOrCreate(
                ['name' => $data['category_name']],
                ['slug' => $categorySlug]
            );

            // 2. إنشاء المنتجات التابعة للقسم (بدون حقل الـ slug)
            foreach ($data['products'] as $product) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'description' => $product['description'],
                ]);
            }
        }
    }
}