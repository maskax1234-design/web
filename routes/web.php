<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.showLogin')->name('home');
Route::get('/register', [AuthController::class, 'showRegister'])->name('showRegister');
Route::get('/login', [AuthController::class, 'showLogin'])->name('showLogin');
Route::post('/signup', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'module'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');

    Route::get('/patientindex', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patientcreate', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patientstore', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/patientshow/{record}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patientedit/{record}', [PatientController::class, 'edit'])->name('patients.edit');
    Route::post('/patientupdate/{record}', [PatientController::class, 'update'])->name('patients.update');
    Route::get('/patientdestroy/{record}', [PatientController::class, 'destroy'])->name('patients.destroy');

    Route::get('/doctorindex', [DoctorController::class, 'index'])->name('doctors.index');
    Route::get('/doctorshow/{record}', [DoctorController::class, 'show'])->name('doctors.show');
    Route::get('/userindex', [UserController::class, 'index'])->name('users.index');
    Route::get('/usershow/{record}', [UserController::class, 'show'])->name('users.show');

    Route::get('/doctorcreate', [DoctorController::class, 'create'])->name('doctors.create');
    Route::post('/doctorstore', [DoctorController::class, 'store'])->name('doctors.store');
    Route::get('/doctoredit/{record}', [DoctorController::class, 'edit'])->name('doctors.edit');
    Route::post('/doctorupdate/{record}', [DoctorController::class, 'update'])->name('doctors.update');
    Route::get('/doctordestroy/{record}', [DoctorController::class, 'destroy'])->name('doctors.destroy');

    Route::get('/usercreate', [UserController::class, 'create'])->name('users.create');
    Route::post('/userstore', [UserController::class, 'store'])->name('users.store');
    Route::get('/useredit/{record}', [UserController::class, 'edit'])->name('users.edit');
    Route::post('/userupdate/{record}', [UserController::class, 'update'])->name('users.update');
    Route::get('/userdestroy/{record}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/departmentindex', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/departmentcreate', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/departmentstore', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/departmentshow/{record}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::get('/departmentedit/{record}', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::post('/departmentupdate/{record}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::get('/departmentdestroy/{record}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

    Route::get('/appointmentindex', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointmentcreate', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointmentstore', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointmentshow/{record}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::get('/appointmentedit/{record}', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::post('/appointmentupdate/{record}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::get('/appointmentdestroy/{record}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

    Route::get('/medicineindex', [MedicineController::class, 'index'])->name('medicines.index');
    Route::get('/medicinecreate', [MedicineController::class, 'create'])->name('medicines.create');
    Route::post('/medicinestore', [MedicineController::class, 'store'])->name('medicines.store');
    Route::get('/medicineshow/{record}', [MedicineController::class, 'show'])->name('medicines.show');
    Route::get('/medicineedit/{record}', [MedicineController::class, 'edit'])->name('medicines.edit');
    Route::post('/medicineupdate/{record}', [MedicineController::class, 'update'])->name('medicines.update');
    Route::get('/medicinedestroy/{record}', [MedicineController::class, 'destroy'])->name('medicines.destroy');

    Route::get('/prescriptionindex', [PrescriptionController::class, 'index'])->name('prescriptions.index');
    Route::get('/prescriptioncreate', [PrescriptionController::class, 'create'])->name('prescriptions.create');
    Route::post('/prescriptionstore', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    Route::get('/prescriptionshow/{record}', [PrescriptionController::class, 'show'])->name('prescriptions.show');
    Route::get('/prescriptionedit/{record}', [PrescriptionController::class, 'edit'])->name('prescriptions.edit');
    Route::post('/prescriptionupdate/{record}', [PrescriptionController::class, 'update'])->name('prescriptions.update');
    Route::get('/prescriptiondestroy/{record}', [PrescriptionController::class, 'destroy'])->name('prescriptions.destroy');

    Route::get('/invoiceindex', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoicecreate', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoicestore', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoiceshow/{record}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoiceedit/{record}', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::post('/invoiceupdate/{record}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::get('/invoicedestroy/{record}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

    Route::get('/paymentindex', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/paymentcreate', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/paymentstore', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/paymentshow/{record}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('/paymentedit/{record}', [PaymentController::class, 'edit'])->name('payments.edit');
    Route::post('/paymentupdate/{record}', [PaymentController::class, 'update'])->name('payments.update');
    Route::get('/paymentdestroy/{record}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    Route::middleware('pharmacy')->prefix('pharmacy')->name('pharmacy.')->group(function (): void {
        Route::get('/pos', [PharmacyController::class, 'index'])->name('pos');
        Route::get('/medicines', [PharmacyController::class, 'medicines'])->name('medicines');
        Route::get('/patients', [PharmacyController::class, 'patients'])->name('patients');
        Route::post('/sales', [PharmacyController::class, 'store'])->name('sales.store');
        Route::get('/sales', [PharmacyController::class, 'sales'])->name('sales.index');
        Route::get('/sales/{invoice}/edit', [PharmacyController::class, 'edit'])->name('sales.edit');
        Route::put('/sales/{invoice}', [PharmacyController::class, 'update'])->name('sales.update');
        Route::get('/sales/{invoice}/receipt', [PharmacyController::class, 'receipt'])->name('sales.receipt');
        Route::get('/sales/{invoice}', [PharmacyController::class, 'show'])->name('sales.show');
        Route::get('/reports/daily', [PharmacyController::class, 'dailyReport'])->name('reports.daily');

        Route::post('/stock/batches', [PharmacyController::class, 'receive'])->name('stock.receive');
        Route::post('/sales/{invoice}/cancel', [PharmacyController::class, 'cancel'])->name('sales.cancel');
    });
});
