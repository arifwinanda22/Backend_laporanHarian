<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LaporanPekerjaanController;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\BarangMasukController;
use App\Http\Controllers\Api\BarangKeluarController;
use App\Http\Controllers\Api\AsetController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PeminjamanAsetController;
use App\Models\User;

// --- Public Routes ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
//  Route::get('/users', [AuthController::class, 'index']);

// --- Protected Routes ---
Route::middleware('auth:sanctum')->group(function () {
    
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Route::get('/user', function (Request $request) {
        // return $request->user();
    // });

   

    // routes/api.php

    Route::get('/user-profile', [AuthController::class, 'userProfile'])->middleware('auth:sanctum');
    Route::put('/update-profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');

     Route::get('/users', function () {
    return response()->json(User::all());
});

    // ============================================================
    // FLOW KHUSUS LAPORAN (Dari Route 1)
    // ============================================================
    // Route ini harus didefinisikan SECARA MANUAL karena tidak ada di apiResource
    
    // 1. User Upload Bukti (Mengubah status jadi 'Menunggu Persetujuan')
    // URL: /api/laporan-pekerjaan/{id}/upload
    Route::get('/laporan-pekerjaan', [LaporanPekerjaanController::class, 'index']);
    // GET - Detail laporan tertentu
    Route::get('/laporan-pekerjaan/{id}', [LaporanPekerjaanController::class, 'show']);
    
    // POST - User buat laporan baru (jika ada fitur ini)
    Route::post('/laporan-pekerjaan', [LaporanPekerjaanController::class, 'store']);
    Route::post('/laporan-pekerjaan/{id}/upload', [LaporanPekerjaanController::class, 'uploadBukti']);

    // routes/api.php

    // Edit data pekerjaan (Teks & Lampiran)
    Route::put('/laporan-pekerjaan/{id}', [LaporanPekerjaanController::class, 'update']);

    // Upload Bukti Spesifik (Opsional, jika ingin tombol terpisah)
    Route::post('/laporan-pekerjaan/{id}/upload', [LaporanPekerjaanController::class, 'uploadBukti']);  

    // 2. Admin Approve (Mengubah status jadi 'Selesai'/'Ditolak')
    // URL: /api/laporan-pekerjaan/{id}/approve
    Route::put('/laporan-pekerjaan/{id}/approve', [LaporanPekerjaanController::class, 'approvePekerjaan']);
    Route::get('/laporan-pekerjaan/{user_id}/approve', [LaporanPekerjaanController::class, 'showByUser']);


    // ============================================================
    // STANDARD CRUD (Dari Route 2)
    // ============================================================
    // apiResource ini otomatis membuat route: 
    // index (GET), store (POST), show (GET), update (PUT/PATCH), destroy (DELETE)
    
    Route::apiResource('laporan-pekerjaan', LaporanPekerjaanController::class);
    Route::get('/laporan-pekerjaan/{id}', [LaporanPekerjaanController::class, 'show']);
    
    // Monitoring
    Route::apiResource('monitoring', MonitoringController::class);
});

//Route untuk Data Aset
Route::get('data-aset', [AsetController::class, 'index']);
Route::post('data-aset', [AsetController::class, 'store']);
Route::get('data-aset/{id}', [AsetController::class, 'show']);
Route::put('data-aset/{id}', [AsetController::class, 'update']);
Route::delete('data-aset/{id}', [AsetController::class, 'destroy']);

// Route untuk Data Barang
Route::get('data-barang', [InventoryController::class, 'index']);
Route::post('data-barang', [InventoryController::class, 'store']);
Route::get('data-barang/{id}', [InventoryController::class, 'show']);
Route::put('data-barang/{id}', [InventoryController::class, 'update']);
Route::delete('data-barang/{id}', [InventoryController::class, 'destroy']);
Route::get('data-aset/kategori/{kategori}', [AsetController::class, 'getByCategory']);

Route::get('peminjaman-aset', [PeminjamanAsetController::class, 'index']);
Route::post('peminjaman-aset', [PeminjamanAsetController::class, 'store']);
Route::get('peminjaman-aset/{id}', [PeminjamanAsetController::class, 'show']);

// Route untuk barang masuk
Route::post('barang-masuk', [BarangMasukController::class, 'store']);  
Route::get('barang-masuk', [BarangMasukController::class, 'index']);
Route::get('barang-masuk/{id}', [BarangMasukController::class, 'show']);
Route::put('barang-masuk/{id}', [BarangMasukController::class, 'update']);
Route::delete('barang-masuk/{id}', [BarangMasukController::class, 'destroy']);

// Route untuk barang keluar
Route::post('barang-keluar', [BarangKeluarController::class, 'store']);     
Route::get('barang-keluar', [BarangKeluarController::class, 'index']);
Route::get('barang-keluar/{id}', [BarangKeluarController::class, 'show']);
Route::put('barang-keluar/{id}', [BarangKeluarController::class, 'update']);
Route::delete('barang-keluar/{id}', [BarangKeluarController::class, 'destroy']);    

