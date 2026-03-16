<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckerTestController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('user');

    // Dashboard Route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/domains/{domain}/history', [DomainController::class, 'history'])->name('domains.history');
    Route::apiResource('domains', DomainController::class);

    // Test routes for individual checkers
    Route::prefix('test')->name('test.')->group(function () {
        Route::post('/dns', [CheckerTestController::class, 'testDns'])->name('dns');
        Route::post('/http', [CheckerTestController::class, 'testHttp'])->name('http');
        Route::post('/redirect', [CheckerTestController::class, 'testRedirect'])->name('redirect');
        Route::post('/ssl', [CheckerTestController::class, 'testSsl'])->name('ssl');
        Route::post('/content', [CheckerTestController::class, 'testContent'])->name('content');
        Route::post('/search', [CheckerTestController::class, 'testSearchVisibility'])->name('search');
        Route::post('/all', [CheckerTestController::class, 'testAll'])->name('all');
    });
});
