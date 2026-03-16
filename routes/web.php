<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DomainController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// --- Public routes for authentication views ---
Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('auth.register');
})->name('register');


// --- Protected WEB routes that require a valid session/login ---
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::get('/domains/create', [DomainController::class, 'create'])->name('domains.create');
    Route::get('/domains/{domain}/edit', [DomainController::class, 'edit'])->name('domains.edit');
    Route::get('/domains/{domain}/history', [DomainController::class, 'history'])->name('domains.history');
});

// This will handle the POST request from Laravel's default logout form
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');
