<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataController;

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