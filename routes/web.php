<?php

use App\Http\Controllers\Admin\ApiIntegrationController;
use App\Http\Controllers\Admin\BeritaAcaraController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\VoterController;
use App\Http\Controllers\Api\VoterSyncController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BilikController;
use App\Http\Controllers\ProyektorController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Bilik Suara Siswa (Voter Portal)
|--------------------------------------------------------------------------
*/
Route::get('/', [BilikController::class, 'login'])->name('bilik.login');
Route::post('/bilik/masuk', [BilikController::class, 'masuk'])->name('bilik.masuk');

Route::middleware('voter.session')->group(function (): void {
    Route::get('/bilik/suara', [BilikController::class, 'suara'])->name('bilik.suara');
    Route::post('/bilik/coblos', [BilikController::class, 'coblos'])->name('bilik.coblos');
    Route::post('/bilik/keluar', [BilikController::class, 'keluar'])->name('bilik.keluar');
});

Route::get('/bilik/sukses', [BilikController::class, 'sukses'])->name('bilik.sukses');

/*
|--------------------------------------------------------------------------
| Scoreboard Proyektor (Live Quick Count)
|--------------------------------------------------------------------------
*/
Route::get('/proyektor', [ProyektorController::class, 'index'])->name('proyektor.index');
Route::get('/api/quick-count', [ProyektorController::class, 'apiData'])->name('proyektor.api');

/*
|--------------------------------------------------------------------------
| Authentication (Admin & Panitia)
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Panel Admin & Panitia Pemilihan
|--------------------------------------------------------------------------
*/
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/toggle-tps', [DashboardController::class, 'toggleTps'])->name('toggle-tps');
    Route::post('/toggle-quick-count', [DashboardController::class, 'toggleQuickCount'])->name('toggle-quick-count');

    // Manajemen Pasangan Calon
    Route::resource('candidates', CandidateController::class)->except(['show']);

    // Manajemen DPT (Pemilih)
    Route::get('/voters/template', [VoterController::class, 'downloadTemplate'])->name('voters.template');
    Route::post('/voters/import', [VoterController::class, 'import'])->name('voters.import');
    Route::get('/voters/print-cards', [VoterController::class, 'printCards'])->name('voters.print-cards');
    Route::post('/voters/reset-votes', [VoterController::class, 'resetVotes'])->name('voters.reset-votes');
    Route::resource('voters', VoterController::class)->except(['show']);

    // Pengaturan Pemilihan
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Integrasi API Database Sekolah & Guru
    Route::get('/api-integration', [ApiIntegrationController::class, 'index'])->name('api-integration.index');
    Route::put('/api-integration', [ApiIntegrationController::class, 'updateSettings'])->name('api-integration.update');
    Route::post('/api-integration/pull', [ApiIntegrationController::class, 'pullData'])->name('api-integration.pull');
    Route::put('/api-integration/guru', [ApiIntegrationController::class, 'updateGuruSettings'])->name('api-integration.update-guru');
    Route::post('/api-integration/pull-guru', [ApiIntegrationController::class, 'pullGuruData'])->name('api-integration.pull-guru');
    Route::post('/api-integration/regenerate-key', [ApiIntegrationController::class, 'regeneratePilketosKey'])->name('api-integration.regenerate-key');

    // Laporan Pemilihan: Tab 1 Daftar Hadir & Tab 2 Berita Acara Pleno
    Route::get('/laporan', [BeritaAcaraController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/cetak-daftar-hadir', [BeritaAcaraController::class, 'printDaftarHadir'])->name('laporan.cetak-daftar-hadir');
    Route::get('/laporan/export-daftar-hadir', [BeritaAcaraController::class, 'exportDaftarHadir'])->name('laporan.export-daftar-hadir');
    Route::get('/laporan/cetak-berita-acara', [BeritaAcaraController::class, 'printBeritaAcara'])->name('laporan.cetak-berita-acara');
    Route::get('/berita-acara', [BeritaAcaraController::class, 'legacyRedirect'])->name('berita-acara.index');
});

/*
|--------------------------------------------------------------------------
| Storage & File Serving Fallback (Hosting / cPanel Compatibility)
|--------------------------------------------------------------------------
*/
Route::get('/storage-link', function () {
    $publicStorage = public_path('storage');
    if (is_link($publicStorage) || is_file($publicStorage)) {
        @unlink($publicStorage);
    }
    Artisan::call('storage:link');

    return redirect()->route('admin.settings.edit')->with('success', 'Storage link berhasil diperbarui!');
})->middleware('auth')->name('storage.link');

Route::get('/storage/{path}', function (string $path) {
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('storage.file');

/*
|--------------------------------------------------------------------------
| Inbound API Endpoints (External School Database Push)
|--------------------------------------------------------------------------
*/
Route::post('/api/v1/voters/sync', [VoterSyncController::class, 'syncFromExternal'])->name('api.voters.sync');
