<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LaporanPekerjaanController;
use App\Http\Controllers\Api\MonitoringController;

// --- Public Routes ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
// --- Protected Routes ---
Route::middleware('auth:sanctum')->group(function () {
    
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // routes/api.php


    // ============================================================
    // FLOW KHUSUS LAPORAN (Dari Route 1)
    // ============================================================
    // Route ini harus didefinisikan SECARA MANUAL karena tidak ada di apiResource
    
    // 1. User Upload Bukti (Mengubah status jadi 'Menunggu Persetujuan')
    // URL: /api/laporan-pekerjaan/{id}/upload
    Route::post('/laporan-pekerjaan/{id}/upload', [LaporanPekerjaanController::class, 'uploadBukti']);

    // 2. Admin Approve (Mengubah status jadi 'Selesai'/'Ditolak')
    // URL: /api/laporan-pekerjaan/{id}/approve
    Route::put('/laporan-pekerjaan/{id}/approve', [LaporanPekerjaanController::class, 'approvePekerjaan']);


    // ============================================================
    // STANDARD CRUD (Dari Route 2)
    // ============================================================
    // apiResource ini otomatis membuat route: 
    // index (GET), store (POST), show (GET), update (PUT/PATCH), destroy (DELETE)
    
    Route::apiResource('laporan-pekerjaan', LaporanPekerjaanController::class);
    
    // Monitoring
    Route::apiResource('monitoring', MonitoringController::class);
});