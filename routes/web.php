<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\MarketplaceController;
use App\Http\Controllers\KvkkController;
use App\Http\Controllers\SupplierController;

Route::get('/kvkk', [KvkkController::class, 'kvkk'])->name('kvkk.terms');

Route::get('/', [StoreController::class, 'index'])->name('store.index');
Route::get('/iletisim', fn() => view('contact'))->name('contact');
Route::post('/iletisim', [ContactController::class, 'store'])->name('contact.store');

Route::prefix('yonetim')->name('admin.')->group(function () {
    Route::get('giris', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('giris', [AuthController::class, 'login']);
    Route::post('cikis', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/visitors', [AdminController::class, 'visitors'])->name('visitors');
        Route::delete('/visitors/clear', [AdminController::class, 'clearVisitors'])->name('visitors.clear');

        Route::delete('/products/destroy-all', [ProductController::class, 'destroyAll'])->name('products.destroy-all');
        Route::get('/products/export', [ProductController::class, 'exportExcel'])->name('products.export');
        Route::put('/products/update-brand-all', [ProductController::class, 'updateBrandAll'])->name('products.update-brand-all');
        Route::delete('/products/destroy-all', [ProductController::class, 'destroyAll'])->name('products.destroy-all');
        Route::resource('products', ProductController::class);
        Route::resource('products', ProductController::class);
        Route::post('/admin/api/trigger-sync', [ProductController::class, 'syncFromXmlApi'])->name('admin.api.trigger-sync');
        
        Route::post('/categories/sync-xml', [CategoryController::class, 'syncFromXml'])->name('categories.sync-xml');
        Route::delete('/categories/delete-all', [CategoryController::class, 'deleteAllCategories'])->name('categories.deleteAll');
        Route::resource('categories', CategoryController::class)->except(['show']);
        
        Route::resource('admins', AdminManagementController::class);
        Route::resource('messages', ContactMessageController::class)->only(['index', 'destroy']);

        Route::post('/suppliers/sync-all', [SupplierController::class, 'syncAllActive'])->name('suppliers.syncAll');
        Route::resource('suppliers', SupplierController::class);
        Route::post('suppliers/{supplier}/sync', [SupplierController::class, 'syncNow'])->name('suppliers.sync');

        Route::prefix('marketplaces')->name('marketplaces.')->group(function () {
            Route::get('/settings', [MarketplaceController::class, 'settings'])->name('settings');
            Route::post('/settings/{marketplace}', [MarketplaceController::class, 'updateSettings'])->name('settings.update');
            Route::get('/logs', [MarketplaceController::class, 'logs'])->name('logs');
            Route::post('/sync-all', [MarketplaceController::class, 'syncAll'])->name('syncAll');
            
            
            Route::post('/push', [MarketplaceController::class, 'pushToMarketplaces'])->name('push');
            Route::post('/muntehas/kar', [MarketplaceController::class, 'updateMargin'])->name('muntehas.kar');
            Route::post('/muntehas/sync', [MarketplaceController::class, 'uploadAndSync'])->name('muntehas.sync');
        });
    });
});