<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\OutboxController as AdminOutboxController;
use App\Http\Controllers\Admin\InboxController as AdminInboxController;

Route::get('/', function () { return redirect(\route('admin.dashboard')); });

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // DEVICES CRUD
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/devices/create', [DeviceController::class, 'create'])->name('devices.create');
    Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');

    // Device details page (must be AFTER /devices/create)
    Route::get('/devices/{id}', [DashboardController::class, 'device'])
        ->whereNumber('id')
        ->name('device');

    // Outbox
    Route::get('/outbox', [AdminOutboxController::class, 'index'])->name('outbox.index');
    Route::get('/outbox/create', [AdminOutboxController::class, 'create'])->name('outbox.create');
    Route::post('/outbox', [AdminOutboxController::class, 'store'])->name('outbox.store');

    // Inbox
    Route::get('/inbox', [AdminInboxController::class, 'index'])->name('inbox.index');
});

require __DIR__.'/auth.php';
