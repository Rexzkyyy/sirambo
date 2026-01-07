<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdrbController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pdrb', [PdrbController::class, 'index'])->name('pdrb.data');
Route::post('/pdrb/kategori', [PdrbController::class, 'storeKategori']);
Route::post('/pdrb/sub', [PdrbController::class, 'storeSub']);
Route::get('/pdrb/hasil', [PdrbController::class, 'hasil'])->name('pdrb.hasil');
Route::get('/pdrb/hasil-pertahun', [PdrbController::class, 'hasilPerTahun'])->name('pdrb.hasil.pertahun');


Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth', 'akses.wilayah'])->group(function () {

    Route::get('/pdrb/hasil', [PdrbController::class, 'hasil'])
        ->name('pdrb.hasil');

    Route::get('/pdrb/hasil-pertahun', [PdrbController::class, 'hasilPerTahun'])
        ->name('pdrb.hasil.pertahun');

});

Route::get('/login', function () {
    return 'Silakan login dulu';
})->name('login');
Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

    

