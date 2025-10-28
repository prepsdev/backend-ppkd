<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\TindakLanjutController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Data API routes (public access for now)
Route::get('/tema-topik', [DataController::class, 'getTemaWithTopik']);
Route::get('/topik/{topikUri}', [DataController::class, 'getDataByTopik']);
Route::get('/indikator/{indikatorUri}', [DataController::class, 'getDataByIndikator']);
Route::get('/search', [DataController::class, 'searchIndikator']);

// Tindak Lanjut API routes
Route::get('/tindak-lanjut/hasil-pengawasan', [TindakLanjutController::class, 'getHasilPengawasan']);
Route::get('/tindak-lanjut/summary-stats', [TindakLanjutController::class, 'getSummaryStats']);
Route::get('/tindak-lanjut/filter-options', [TindakLanjutController::class, 'getFilterOptions']);

// Surat Tugas Penugasan API routes
Route::get('/surat-tugas/penugasan', [TindakLanjutController::class, 'getSuratTugasPenugasan']);
Route::get('/surat-tugas/filter-options', [TindakLanjutController::class, 'getSuratTugasFilterOptions']);

// Protected routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Welcome to dashboard',
            'user' => [
                'username' => session('username'),
                'nama_gelar' => session('nama_gelar'),
                'namaunit' => session('namaunit'),
                'name' => session('name'),
            ]
        ]);
    });
});