<?php

use App\Http\Controllers\RequisitionPdfController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Expenses\ExpenseIndex;
use App\Livewire\Measures\MeasureIndex;
use App\Livewire\Notifications\NotificationIndex;
use App\Livewire\Products\CategoryIndex;
use App\Livewire\Products\ProductIndex;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\QuickBudgets\QuickBudgetIndex;
use App\Livewire\QuickBudgets\QuickBudgetWizard;
use App\Livewire\Reports\ReportIndex;
use App\Livewire\Requisitions\ManualRequisition;
use App\Livewire\Requisitions\QuotationWizard;
use App\Livewire\Requisitions\RequisitionIndex;
use App\Livewire\Requisitions\RequisitionShow;
use App\Livewire\Suppliers\SupplierIndex;
use App\Livewire\Users\UserIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
    Route::get('/proyectos/{id}', ProjectShow::class)->name('proyectos.show');

    // Gastos (RF-GASTO)
    Route::get('/gastos', ExpenseIndex::class)->name('gastos.index');

    // Requisiciones (RF-REQ)
    Route::get('/requisiciones', RequisitionIndex::class)->name('requisiciones.index');
    Route::get('/requisiciones/manual', ManualRequisition::class)->name('requisiciones.manual');
    Route::get('/requisiciones/subir-cotizacion', QuotationWizard::class)->name('requisiciones.upload');
    Route::get('/requisiciones/{id}', RequisitionShow::class)->name('requisiciones.show');
    Route::get('/requisiciones/{id}/pdf', [RequisitionPdfController::class, 'download'])->name('requisiciones.pdf');

    // Proveedores (RF-PROV)
    Route::get('/proveedores', SupplierIndex::class)->name('proveedores.index');

    // Clientes
    Route::get('/clientes', ClientIndex::class)->name('clientes.index');

    // Documentos (RF-DOC)

    // Usuarios
    Route::get('/usuarios', UserIndex::class)->name('usuarios.index');

    // Reportes
    Route::get('/reportes', ReportIndex::class)->name('reportes.index');

    // Cotizador (Trabajos Menores)
    Route::get('/cotizador', QuickBudgetIndex::class)->name('cotizador.index');
    Route::get('/cotizador/wizard/{id?}', QuickBudgetWizard::class)->name('cotizador.wizard');

    // Catálogo de Productos (RF-REQ-05)
    Route::get('/productos', ProductIndex::class)->name('productos.index');

    // Catálogo de Medidas
    Route::get('/medidas', MeasureIndex::class)->name('medidas.index');

    // Catálogo de Categorías
    Route::get('/categorias', CategoryIndex::class)->name('categorias.index');

    // Configuración
    Route::get('/configuracion', function () {
        if (! auth()->user()?->hasPermission('configuracion.ver') && ! auth()->user()?->hasPermission('configuracion.editar') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para acceder a la configuración del sistema.');
        }
        return redirect()->route('dashboard')->with('open_settings', true);
    })->name('settings.index');

    // Notificaciones
    Route::get('/notificaciones', NotificationIndex::class)->name('notifications.index');

    // Previsualización de archivos
    Route::get('/preview-file', function (Request $request) {
        $path = $request->query('path');
        $disk = $request->query('disk');

        return \App\Support\StorageResolver::streamResponse($path, $disk) ?? abort(404);
    })->name('file.preview');
});
