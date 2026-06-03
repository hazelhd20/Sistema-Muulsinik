<?php

use App\Http\Controllers\RequisitionPdfController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;

use App\Livewire\Expenses\ExpenseIndex;
use App\Livewire\Products\ProductIndex;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Reports\ReportIndex;
use App\Livewire\Requisitions\ManualRequisition;
use App\Livewire\Requisitions\QuotationWizard;
use App\Livewire\Requisitions\RequisitionIndex;
use App\Livewire\Suppliers\SupplierIndex;
use App\Livewire\Settings\SettingsIndex;
use App\Livewire\Users\UserIndex;
use App\Livewire\Notifications\NotificationIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Rutas Web — Muulsinik ERP v1
|--------------------------------------------------------------------------
| Organizadas por módulo funcional según el ERS.
*/

// --- Rutas públicas ---
Route::get('/', fn() => redirect('/login'));
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
    Route::get('/requisiciones/manual', ManualRequisition::class)->name('requisiciones.manual');
    Route::get('/requisiciones/subir-cotizacion', QuotationWizard::class)->name('requisiciones.upload');
    Route::get('/requisiciones/{id}/pdf', [RequisitionPdfController::class, 'download'])->name('requisiciones.pdf');

    // Proveedores (RF-PROV)
    Route::get('/proveedores', SupplierIndex::class)->name('proveedores.index');

    // Documentos (RF-DOC)

    // Usuarios
    Route::get('/usuarios', UserIndex::class)->name('usuarios.index');

    // Reportes
    Route::get('/reportes', ReportIndex::class)->name('reportes.index');

    // Cotizador (Trabajos Menores)
    Route::get('/cotizador', \App\Livewire\QuickBudgets\QuickBudgetIndex::class)->name('cotizador.index');
    Route::get('/cotizador/wizard/{id?}', \App\Livewire\QuickBudgets\QuickBudgetWizard::class)->name('cotizador.wizard');

    // Catálogo de Productos (RF-REQ-05)
    Route::get('/productos', ProductIndex::class)->name('productos.index');

    // Catálogo de Medidas
    Route::get('/medidas', \App\Livewire\Measures\MeasureIndex::class)->name('medidas.index');

    // Catálogo de Categorías
    Route::get('/categorias', \App\Livewire\Products\CategoryIndex::class)->name('categorias.index');

    // Configuración
    Route::get('/configuracion', SettingsIndex::class)->name('settings.index');

    // Notificaciones
    Route::get('/notificaciones', NotificationIndex::class)->name('notifications.index');

    // Previsualización de archivos
    Route::get('/preview-file', function (\Illuminate\Http\Request $request) {
        $path = $request->query('path');
        $disk = $request->query('disk', 'local');

        if (!in_array($disk, ['local', 'public']) || !$path || str_contains($path, '..') || !\Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $mime = \Illuminate\Support\Facades\Storage::disk($disk)->mimeType($path);

        return response()->file(\Illuminate\Support\Facades\Storage::disk($disk)->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
        ]);
    })->name('file.preview');
});
