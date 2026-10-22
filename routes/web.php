<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'customer.menu')->name('customer.menu');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Route::redirect('settings', 'settings/profile');

    Volt::route('settings-profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings-password', 'settings.password')->name('settings.password');
    // Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});
Route::middleware(['auth', 'chick.admin-or-superadmin'])->group(function () {
    Volt::route('/admin-products', 'admin.products-manager')->name('admin.products-manager');
    Volt::route('/admin-dashboard', 'admin.dashboard')->name('admin.dashboard');
    Volt::route('/admin-employees', 'admin.employees-manager')->name('admin.employees-manager');
});
Route::middleware(['chick.waiter'])->group(function () {
    Volt::route('/waiter-orders', 'waiter.orders')->name('waiter.orders');
});
Route::middleware('chick.chief')->group(function () {
    Volt::route('/chief-orders', 'chief.orders')->name('chief.orders');
});
Volt::route('/screen', 'screen.display')->name('screen.display');
Volt::route('/menu', 'customer.menu')->name('customer.menu');
Volt::route('/track-order', 'customer.track-order')->name('customer.track-order');



require __DIR__ . '/auth.php';
