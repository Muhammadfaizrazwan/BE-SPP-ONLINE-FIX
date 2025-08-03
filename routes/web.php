<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\{
    AuthController,
    UserController,
    SchoolController,
    SchoolClassController,
    StudentController,
    PaymentTypeController,
    StudentBillController,
    PaymentController,
    ReportController,
    PaymentMethodController
};

// Halaman publik (contoh landing page)
Route::get('/', function () {
    return view('welcome');
});

// Admin route (akses Filament)
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Semua route admin taruh sini kalau mau manual
    // Filament otomatis akan proteksi auth, nanti kita tambah role check di provider
});

// Parent dashboard
Route::middleware(['auth', 'role:parent'])->group(function () {
    Route::get('/parent/dashboard', function () {
        return 'Parent Dashboard';
    });
});

// Student dashboard
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/dashboard', function () {
        return 'Student Dashboard';
    });
});



Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('schools', SchoolController::class);
    Route::resource('school-classes', SchoolClassController::class);
    Route::resource('students', StudentController::class);
    Route::resource('payment-types', PaymentTypeController::class);
    Route::resource('student-bills', StudentBillController::class);
    Route::resource('payments', PaymentController::class);
    Route::get('reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('reports/debts', [ReportController::class, 'debts'])->name('reports.debts');
    Route::resource('payment-methods', PaymentMethodController::class);
});


Route::middleware('auth:sanctum')->get('/dashboard', [DashboardController::class, 'index']);

Route::prefix('student-bills')->name('web.student_bills.')->group(function () {
    Route::get('/', [StudentBillController::class, 'index'])->name('index');
    Route::get('/create', [StudentBillController::class, 'create'])->name('create');
    Route::post('/', [StudentBillController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [StudentBillController::class, 'edit'])->name('edit');
    Route::put('/{id}', [StudentBillController::class, 'update'])->name('update');
    Route::delete('/{id}', [StudentBillController::class, 'destroy'])->name('destroy');
});

Route::prefix('payment-types')->name('web.payment_types.')->group(function () {
    Route::get('/', [PaymentTypeController::class, 'index'])->name('index');
    Route::get('/create', [PaymentTypeController::class, 'create'])->name('create');
    Route::post('/', [PaymentTypeController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [PaymentTypeController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PaymentTypeController::class, 'update'])->name('update');
    Route::delete('/{id}', [PaymentTypeController::class, 'destroy'])->name('destroy');
});


Route::prefix('payments')->name('web.payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
    Route::post('/{id}/verify', [PaymentController::class, 'verify'])->name('verify');
    Route::post('/{id}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    Route::delete('/{id}', [PaymentController::class, 'destroy'])->name('destroy');
});

Route::prefix('payment-methods')->name('web.payment_methods.')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
    Route::get('/create', [PaymentMethodController::class, 'create'])->name('create');
    Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [PaymentMethodController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PaymentMethodController::class, 'update'])->name('update');
    Route::delete('/{id}', [PaymentMethodController::class, 'destroy'])->name('destroy');
});
