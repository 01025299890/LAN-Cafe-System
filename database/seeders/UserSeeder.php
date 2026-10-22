<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. حساب الـ Superadmin (المالك الأصلي للكافيه)
        User::updateOrCreate(
            ['email' => 'a01025299890@gmail.com'], // الفحص بالإيميل لمنع التكرار لو شغلت السييدر تاني
            [
                'name' => 'أحمد رامي',
                'password' => Hash::make('12345678'), // كلمة مرور افتراضية للتجربة
                'role' => 'superadmin',
            ]
        );

        // 2. حساب الـ Admin (المدير المسؤول عن الوردية أو المكان)
        // User::updateOrCreate(
        //     ['email' => 'waiter@electrocafe.com'],
        //     [
        //         'name' => 'أحمد كامل',
        //         'password' => Hash::make('12345678'),
        //         'role' => 'waiter',
        //     ]
        // );
        // User::updateOrCreate(
        //     ['email' => 'chief@electrocafe.com'],
        //     [
        //         'name' => 'أحمد عشور',
        //         'password' => Hash::make('12345677'),
        //         'role' => 'chief',
        //     ]
        // );

        $this->command->info('تم تأسيس حسابات الـ Superadmin والـ Admin بنجاح! 👑☕');
    }
}