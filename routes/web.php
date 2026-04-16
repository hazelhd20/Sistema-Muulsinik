<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Documents\DocumentIndex;
use App\Livewire\Expenses\ExpenseIndex;
use App\Livewire\Products\ProductIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Reports\ReportIndex;
use App\Livewire\Requisitions\QuotationWizard;
use App\Livewire\Requisitions\RequisitionIndex;
use App\Livewire\Suppliers\SupplierIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web — Muulsinik ERP v1
|--------------------------------------------------------------------------
| Organizadas por módulo funcional según el ERS.
*/

// --- Rutas públicas ---
Route::get('/', fn () => redirect('/login'));
Route::get('/login', Login::class)->name('login')->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// --- Rutas protegidas ---
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Proyectos (RF-PROY)
    Route::get('/proyectos', ProjectIndex::class)->name('proyectos.index');

    // Gastos (RF-GASTO)
    Route::get('/gastos', ExpenseIndex::class)->name('gastos.index');

    // Requisiciones (RF-REQ)
    Route::get('/requisiciones', RequisitionIndex::class)->name('requisiciones.index');
    Route::get('/requisiciones/subir-cotizacion', QuotationWizard::class)->name('requisiciones.upload');

    // Proveedores (RF-PROV)
    Route::get('/proveedores', SupplierIndex::class)->name('proveedores.index');

    // Documentos (RF-DOC)
    Route::get('/documentos', DocumentIndex::class)->name('documentos.index');

    // Reportes
    Route::get('/reportes', ReportIndex::class)->name('reportes.index');

    // Catálogo de Productos (RF-REQ-05)
    Route::get('/productos', ProductIndex::class)->name('productos.index');
});
