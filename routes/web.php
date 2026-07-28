<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PdrbController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\RekonsiliasiController;
// use App\Http\Controllers\KotaDashboardController;
use App\Http\Controllers\HasilPdrbController;
use App\Http\Controllers\RekonP1Controller;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\DynamicTableController;
use App\Http\Controllers\PendudukController;
use App\Http\Controllers\CekSelisihController;
use App\Http\Controllers\FenomenaController;
use App\Http\Controllers\RekonLkController;
use App\Http\Controllers\RekapLkHasilController;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});


// ======================================================
// AUTH
// ======================================================
Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('login.post');
    Route::post('/logout', 'logout')->name('logout');
});

Route::controller(\App\Http\Controllers\SsoController::class)->group(function () {
    Route::get('/login/sso', 'redirect')->name('login.sso');
    Route::get('/login/sso/callback', 'callback')->name('login.sso.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile/password', [\App\Http\Controllers\ProfileController::class, 'showChangePassword'])->name('profile.password');
    Route::post('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');
});


// ======================================================
// PUBLIC PDRB
// ======================================================
Route::prefix('pdrb')->controller(PdrbController::class)->group(function () {
    Route::get('/', 'index')->name('pdrb.data');
    Route::post('/kategori', 'storeKategori');
    Route::post('/sub', 'storeSub');

    Route::get('/hasil', [HasilPdrbController::class, 'index'])->name('pdrb.hasil');
    Route::get('/hasil/export', [HasilPdrbController::class, 'export'])->name('pdrb.hasil.export');
    Route::get('/tabel-dinamis', [DynamicTableController::class, 'index'])->name('pdrb.dynamic');
    Route::get('/tabel-dinamis/preview', [DynamicTableController::class, 'preview'])->name('pdrb.dynamic.preview');
    Route::get('/tabel-dinamis/export', [DynamicTableController::class, 'export'])->name('pdrb.dynamic.export');

    Route::get('/hasil-pertahun', 'hasilPerTahun')->name('pdrb.hasil.pertahun');

    Route::get('/menu', 'menu')->name('pdrb.menu');
    Route::get('/edit', 'edit')->name('pdrb.edit');
    Route::delete('/hapus', 'hapus')->name('pdrb.hapus');

    Route::post('/import', 'import')->middleware('auth')->name('pdrb.import');
    Route::get('/template', 'template')->name('pdrb.template');

    // Lock/Unlock routes for PDRB import
    Route::get('/lock-status', 'getLockStatus')->middleware('auth')->name('pdrb.lock-status');
    Route::post('/toggle-lock', 'toggleLock')->middleware('auth')->name('pdrb.toggle-lock');

    Route::get('/import-logs', [\App\Http\Controllers\PdrbImportLogController::class, 'index'])
        ->middleware(['auth', 'role:provinsi,provinsi_supervisor'])
        ->name('pdrb.import_logs');
});

// ======================================================
// LEMBAR KERJA
// ======================================================
Route::get('/lembar-kerja', [\App\Http\Controllers\LembarKerjaController::class, 'index'])
    ->middleware('auth')
    ->name('lembar_kerja.index');
Route::get('/lembar-kerja/rekap', [RekapLkHasilController::class, 'index'])
    ->middleware('auth')
    ->name('lembar_kerja.rekap');
Route::get('/lembar-kerja/rekap/export', [HasilPdrbController::class, 'export'])
    ->middleware('auth')
    ->name('lembar_kerja.rekap.export');
Route::get('/lembar-kerja/detail/{id_sub_kategori}', [\App\Http\Controllers\LembarKerjaController::class, 'detail'])
    ->middleware('auth')
    ->name('lembar_kerja.detail');
Route::post('/lembar-kerja/detail/{id_sub_kategori}', [\App\Http\Controllers\LembarKerjaController::class, 'saveDetail'])
    ->middleware('auth')
    ->name('lembar_kerja.detail.save');

Route::delete('/lembar-kerja/detail/{id_sub_kategori}', [\App\Http\Controllers\LembarKerjaController::class, 'deleteItem'])
    ->middleware('auth')
    ->name('lembar_kerja.detail.delete');
Route::delete('/lembar-kerja/detail/{id_sub_kategori}/item', [\App\Http\Controllers\LembarKerjaController::class, 'deleteItem'])
    ->middleware('auth')
    ->name('lembar_kerja.detail.delete_item');
Route::delete('/lembar-kerja/detail/{id_sub_kategori}/reset', [\App\Http\Controllers\LembarKerjaController::class, 'resetDetail'])
    ->middleware('auth')
    ->name('lembar_kerja.detail.reset');
Route::post('/lembar-kerja/detail/{id_sub_kategori}/komoditas', [\App\Http\Controllers\LembarKerjaController::class, 'saveKomoditas'])
    ->middleware('auth')
    ->name('lembar_kerja.detail.komoditas');
Route::get('/lembar-kerja/detail-kategori/{id_kategori}', [\App\Http\Controllers\LembarKerjaController::class, 'detailKategori'])
    ->middleware('auth')
    ->name('lembar_kerja.detail.kategori');

// Rekonsiliasi Lembar Kerja - show aggregates and breakdown
Route::get('/rekon-lk', [RekonLkController::class, 'index'])
    ->middleware('auth')
    ->name('rekon_lk');
Route::post('/rekon-lk/update-adjustment', [RekonLkController::class, 'updateAdjustment'])
    ->middleware('auth')
    ->name('rekon_lk.update_adjustment');
Route::post('/rekon-lk/lock', [RekonLkController::class, 'toggleLock'])
    ->middleware('auth')
    ->name('rekon_lk.lock');
Route::post('/rekon-lk/release', [RekonLkController::class, 'releaseData'])
    ->middleware('auth')
    ->name('rekon_lk.release');
Route::get('/rekon-lk/history/{id}', [RekonLkController::class, 'history'])
    ->middleware('auth')
    ->name('rekon_lk.history');
Route::get('/rekon-lk/poll-updates', [RekonLkController::class, 'pollUpdates'])
    ->middleware('auth')
    ->name('rekon_lk.poll');


// ======================================================
// PROTECTED PDRB (AUTH + WILAYAH)
// ======================================================
Route::middleware(['auth', 'akses.wilayah'])
    ->prefix('pdrb')
    ->controller(PdrbController::class)
    ->group(function () {

        Route::get('/hasil/semua-tahun', 'semuaTahun')->name('pdrb.hasil.semua-tahun');

        Route::get('/template/full', 'templateFull')->name('pdrb.template.full');
        Route::post('/import/full', 'importFull')->name('pdrb.import.full');

        // Analisis
        Route::get('/diskrepansi', 'diskrepansi')->name('pdrb.diskrepansi');
        Route::get('/q-to-q', 'qtoq')->name('pdrb.qtoq');
        Route::get('/y-on-y', 'yony')->name('pdrb.yony');
        Route::get('/c-to-c', 'ctoc')->name('pdrb.ctoc');
        Route::get('/indeks-implisit', 'indeksImplisit')->name('pdrb.indeks-implisit');
        Route::get('/laju-implisit', 'lajuImplisit')->name('pdrb.laju-implisit');
        Route::get('/struktur-dalam', 'strukturDalam')->name('pdrb.struktur-dalam');
        Route::get('/struktur-antar', 'strukturAntar')->name('pdrb.struktur-antar');
    });


// ======================================================
// DASHBOARD
// ======================================================
Route::middleware('auth')->get(
    'pdrb/dashboard',
    [DashboardController::class, 'index']
)->name('pdrb.dashboard');


// ======================================================
// AJAX
// ======================================================
Route::get('/kabupaten/{provinsi_id}', [UserController::class, 'getKabupaten'])
    ->name('kabupaten.byProvinsi');


// ======================================================
// PENDUDUK (PROVINSI + KAB/KOTA)
// ======================================================
Route::prefix('admin')
    ->middleware(['auth', 'role:provinsi,provinsi_supervisor,kabupaten,kota'])
    ->group(function () {
        Route::get('penduduk/template', [PendudukController::class, 'template'])->name('penduduk.template');
        Route::post('penduduk/import', [PendudukController::class, 'import'])->name('penduduk.import');
        Route::resource('penduduk', PendudukController::class);
    });

// ======================================================
// ADMIN PROVINSI (DASHBOARD & MASTER DATA)
// ======================================================
Route::prefix('admin')
    ->middleware(['auth', 'role:provinsi,provinsi_supervisor'])
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('admin.dashboard');

        Route::resource('users', UserController::class);
        Route::resource('kategori', KategoriController::class);

        Route::get('/wilayah', [WilayahController::class, 'index'])->name('wilayah.index');
        Route::get('/wilayah/{wilayah}', [WilayahController::class, 'show'])->name('wilayah.show');
    });

// ======================================================
// ADMIN KABUPATEN / KOTA
// ======================================================
Route::prefix('admin-kota')
    ->middleware(['auth', 'role:kabupaten,kota'])
    ->group(function () {
        /*
                Route::get('/', [KotaDashboardController::class, 'index'])
                    ->name('admin.kota.dashboard');
        */
    });


// ======================================================
// REKONSILIASI ANALISIS
// ======================================================
Route::prefix('rekonsiliasi')
    ->controller(RekonsiliasiController::class)
    ->group(function () {

        Route::get('/', 'index')->name('rekonsiliasi.index');
        Route::get('/redirect', 'redirect')->name('rekonsiliasi.redirect');

        Route::get('/qtoq', 'qtoq')->name('rekonsiliasi.qtoq');
        Route::get('/yony', 'yony')->name('rekonsiliasi.ytoy');
        Route::get('/ctoc', 'ctoc')->name('rekonsiliasi.ctoc');
        Route::get('/indeksImplisit', 'indeksImplisit')->name('rekonsiliasi.indeksImplisit');
        Route::get('/lajuImplisit', 'lajuImplisit')->name('rekonsiliasi.lajuImplisit');
        Route::get('/stukturDalam', 'stukturDalam')->name('rekonsiliasi.stukturDalam');
        Route::get('/stukturAntar', 'stukturAntar')->name('rekonsiliasi.stukturAntar');

        Route::get('/cek-selisih', [RekonsiliasiController::class, 'cekSelisih'])
            ->name('rekonsiliasi.cekSelisih');

        Route::get('/get-nilai-fenomena', [RekonsiliasiController::class, 'getNilaiForFenomena'])
            ->name('rekonsiliasi.getNilaiFenomena');

        Route::get('/get-nilai-fenomena-triwulanan', [RekonsiliasiController::class, 'getNilaiForFenomenaTriwulanan'])
            ->name('rekonsiliasi.getNilaiFenomenaTriwulanan');
    });

// ======================================================
// REKON P1
// ======================================================
Route::prefix('rekonsiliasi-p1')
    ->name('rekon_p1.')
    ->controller(RekonP1Controller::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('/detail/{id_sub_kategori}', 'detail')->name('detail');
        Route::get('/detail-kategori/{id_kategori}', 'detail')->name('detail.kategori');

        Route::post('/save-adjustment', 'saveAdjustment')->name('save-adjustment');
        Route::post('/update-adj', 'updateAdj')->middleware('auth')->name('update_adj');
        Route::post('/recalculate', 'recalculate')->name('recalculate');
        Route::get('/poll-updates', 'pollUpdates')->middleware('auth')->name('poll');
        Route::post('/lock', 'toggleLock')->middleware('auth')->name('lock');

        Route::get('/history/{id}', 'history')
            ->middleware('auth')
            ->name('history');
    });

// ======================================================
// PENGELUARAN (alias PDRB index)
// ======================================================
Route::get('/pengeluaran', [PdrbController::class, 'index'])
    ->name('pengeluaran.data');

Route::get('/rekonsiliasi/export/{mode}', [RekonsiliasiController::class, 'exportUniversal'])
    ->name('rekonsiliasi.export');

// ======================================================
// RELEASE & RESET
// ======================================================
Route::middleware(['auth'])->group(function () {
    Route::get('rekonsiliasi/release', [RekonsiliasiController::class, 'release'])
        ->name('rekonsiliasi.release');

    Route::get('rekonsiliasi/reset', [RekonsiliasiController::class, 'reset'])
        ->name('rekonsiliasi.reset');
});



Route::middleware(['auth'])->group(function () {
    // Fenomena routes
    Route::get('/fenomena', [FenomenaController::class, 'index'])->name('fenomena.index');
    Route::get('/fenomena/create', [FenomenaController::class, 'create'])->name('fenomena.create');
    Route::post('/fenomena', [FenomenaController::class, 'store'])->name('fenomena.store');
    Route::get('/fenomena/{id}/edit', [FenomenaController::class, 'edit'])->name('fenomena.edit');
    Route::put('/fenomena/{id}', [FenomenaController::class, 'update'])->name('fenomena.update');
    Route::delete('/fenomena/{id}', [FenomenaController::class, 'destroy'])->name('fenomena.destroy');

    // Template download
    Route::get('/fenomena/template/download', [FenomenaController::class, 'downloadTemplate'])->name('fenomena.template');

    // Lock/Unlock routes
    Route::post('/fenomena/toggle-upload-lock', [FenomenaController::class, 'toggleUploadLock'])->name('fenomena.toggle-upload-lock');
    Route::post('/fenomena/toggle-lock', [FenomenaController::class, 'toggleLock'])->name('fenomena.toggle-lock');
    Route::get('/histori', [FenomenaController::class, 'historiFenomena'])->name('fenomena.histori');
    Route::get('/histori/{id}/detail', [FenomenaController::class, 'detailHistori'])->name('histori.detail');
    Route::post('/fenomena/cancel-schedule-lock', [FenomenaController::class, 'cancelScheduleLock'])->name('fenomena.cancel-schedule-lock');
    Route::get('/fenomena/check-schedule-lock', [FenomenaController::class, 'checkScheduleLock'])->name('fenomena.check-schedule-lock');

    Route::get('fenomena/download-template', [FenomenaController::class, 'downloadTemplate'])->name('fenomena.download-template');
    Route::get('/fenomena/ranking', [FenomenaController::class, 'ranking'])->name('fenomena.ranking');
    // Route untuk export fenomena ke Excel
    Route::get('/fenomena/export', [FenomenaController::class, 'export'])->name('fenomena.export');

    // AJAX routes
    Route::get('/subkategori/{idKategori}', [FenomenaController::class, 'getSubKategori']);
    Route::get('/subkategori/all', [FenomenaController::class, 'getAllSubKategori']);
    Route::get('/wilayah/list', [FenomenaController::class, 'getWilayahList']);
});

Route::get('/clear-cache', function() {
    // 1. Clear compiled views (HTML cache)
    $views = glob(storage_path('framework/views/*.php'));
    if (is_array($views)) {
        foreach ($views as $view) {
            if (is_file($view)) {
                @unlink($view);
            }
        }
    }

    // 2. Clear bootstrap config & routes cache files
    @unlink(base_path('bootstrap/cache/config.php'));
    @unlink(base_path('bootstrap/cache/routes-v7.php'));
    @unlink(base_path('bootstrap/cache/services.php'));
    @unlink(base_path('bootstrap/cache/packages.php'));

    return "All Laravel cache cleared successfully via manual file deletion!";
});
