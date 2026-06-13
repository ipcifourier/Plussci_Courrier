<?php

use App\Http\Controllers\SyncClientController;
use App\Http\Middleware\AuthenticateSyncDevice;
use Illuminate\Support\Facades\Route;

Route::prefix('sync-client')
    ->name('sync-client.')
    ->middleware(['throttle:120,1', AuthenticateSyncDevice::class])
    ->group(function (): void {
        Route::get('/ping', [SyncClientController::class, 'ping'])->name('ping');
        Route::get('/config', [SyncClientController::class, 'config'])->name('config');
        Route::get('/changes', [SyncClientController::class, 'changes'])->name('changes');
        Route::get('/download/{mediaId}', [SyncClientController::class, 'download'])
            ->whereNumber('mediaId')
            ->name('download');

        Route::post('/scan-upload', [SyncClientController::class, 'scanUpload'])
            ->name('scan-upload');
    });
