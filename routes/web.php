<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\QueueMonitorController;
use App\Http\Controllers\InventoryPageController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\ReportPageController;
use App\Http\Controllers\OrderDetailPageController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('menu/{id}', [MenuController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('menu.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS (cashier)
    Route::get('pos', [PosController::class, 'index'])
        ->name('pos.index')
        ->middleware('can:create orders');

    // Kitchen monitor
    Route::get('kitchen', [QueueMonitorController::class, 'index'])
        ->name('kitchen.index')
        ->middleware('can:update orders');

    // Inventory management
    Route::get('inventory', [InventoryPageController::class, 'index'])
        ->name('inventory.index')
        ->middleware('can:view inventory');

    // Product management (admin only)
    Route::get('products', [ProductPageController::class, 'index'])
        ->name('products.index')
        ->middleware('role:admin');

    // Order detail
    Route::get('orders/{order}', [OrderDetailPageController::class, 'show'])
        ->name('orders.show')
        ->middleware('can:view orders');

    // Reports
    Route::get('reports', [ReportPageController::class, 'index'])
        ->name('reports.index')
        ->middleware('can:view reports');
});

require __DIR__.'/settings.php';
