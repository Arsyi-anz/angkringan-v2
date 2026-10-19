<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BirthdayController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\Admin\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return auth('admin')->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/customers/export/csv', [CustomerController::class, 'exportCsv'])
        ->name('customers.export');
    Route::get('/customers/export/pdf', [CustomerController::class, 'exportPdf'])
        ->name('customers.export.pdf');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::match(['put', 'patch'], '/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::get('/visits', [VisitController::class, 'index'])->name('visits.index');
    Route::post('/visits', [VisitController::class, 'store'])->name('visits.store');

    Route::get('/birthday', [BirthdayController::class, 'index'])->name('birthday.index');
    Route::patch('/hampers/klaim/{customer}', [BirthdayController::class, 'klaim'])->name('hampers.claim');

    Route::get('/vouchers/export', [VoucherController::class, 'export'])->name('vouchers.export');
    Route::get('/vouchers/export/pdf', [VoucherController::class, 'exportPdf'])->name('vouchers.export.pdf');
    Route::get('/vouchers/codes', [VoucherController::class, 'codes'])->name('vouchers.codes');
    Route::post('/vouchers/generate', [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::get('/vouchers/validate', [VoucherController::class, 'validatePage'])->name('vouchers.validate');
    Route::post('/vouchers/check', [VoucherController::class, 'check'])->name('vouchers.check');
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::patch('/vouchers/{voucher}', [VoucherController::class, 'redeem'])->name('vouchers.redeem');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/brochure', [SettingsController::class, 'brochure'])->name('settings.brochure');
});
