<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentReportController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PaymentTypeController;
use App\Http\Controllers\Api\StudentBillController;

// Login
Route::post('/login', [AuthController::class, 'login']);

// Ambil data user login
Route::middleware('auth:sanctum')->get('/user', function ($request) {
    return $request->user();
});

// Semua endpoint yang butuh login
Route::middleware('auth:sanctum')->group(function () {

    // Bills
    Route::get('/bills', [BillController::class, 'index']);
    Route::get('/bills/{id}', [BillController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments', [PaymentController::class, 'store']);

    // Admin only
    Route::post('/payments/{id}/verify', [PaymentController::class, 'verify']);
    Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel']);
});


Route::middleware('auth:sanctum')->group(function () {

    // History pembayaran
    Route::get('/payment-history', [PaymentReportController::class, 'history']);

    // Laporan admin
    Route::get('/payment-report', [PaymentReportController::class, 'report']);
});


// Upload bukti pembayaran
Route::post('/payments/{id}/upload-proof', [PaymentController::class, 'uploadProof'])
    ->middleware('auth:sanctum');

// Verifikasi pembayaran (admin only)
Route::post('/payments/{id}/verify', [PaymentController::class, 'verify'])
    ->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('payment-methods', \App\Http\Controllers\Api\PaymentMethodController::class);
});

// Reports
Route::get('/reports/payments', [ReportController::class, 'paymentReport']);
Route::get('/reports/stats/monthly', [ReportController::class, 'monthlyStats']);
Route::get('/reports/stats/daily', [ReportController::class, 'dailyStats']);



Route::middleware('auth:sanctum')->group(function () {
    // Payment Types
    Route::get('/payment-types', [PaymentTypeController::class, 'index']);
    Route::post('/payment-types', [PaymentTypeController::class, 'store']);
    Route::get('/payment-types/{id}', [PaymentTypeController::class, 'show']);
    Route::put('/payment-types/{id}', [PaymentTypeController::class, 'update']);
    Route::delete('/payment-types/{id}', [PaymentTypeController::class, 'destroy']);

    // Student Bills
    Route::get('/student-bills', [StudentBillController::class, 'index']);
    Route::post('/student-bills', [StudentBillController::class, 'store']);
    Route::get('/student-bills/{id}', [StudentBillController::class, 'show']);
    Route::put('/student-bills/{id}', [StudentBillController::class, 'update']);
    Route::delete('/student-bills/{id}', [StudentBillController::class, 'destroy']);
});

