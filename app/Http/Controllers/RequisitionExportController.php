<?php

namespace App\Http\Controllers;

use App\Actions\Requisitions\ExportRequisitionsCsvAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * RequisitionExportController
 *
 * Maneja la descarga de archivos CSV de requisiciones como respuestas HTTP puras,
 * separado del ciclo de renderizado de Livewire para evitar corrupción de datos binarios.
 *
 * Flujo:
 *   Livewire → almacena IDs en sesión con token → redirect a esta ruta →
 *   Controller lee IDs → devuelve StreamedResponse limpia
 */
class RequisitionExportController extends Controller
{
    public function csv(Request $request, ExportRequisitionsCsvAction $action)
    {
        if (! auth()->user()?->hasPermission('requisiciones.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403);
        }

        $token = $request->query('token');

        if (! $token) {
            abort(400, 'Token de exportación requerido.');
        }

        $payload = Session::get("export_csv_{$token}");

        if (! $payload || ! is_array($payload['ids'] ?? null)) {
            abort(410, 'El enlace de exportación ha expirado o ya fue utilizado.');
        }

        // Consumir el token inmediatamente (single-use)
        Session::forget("export_csv_{$token}");

        $type = $payload['type'] ?? 'summary';
        $ids  = $payload['ids'];

        return $action->execute($ids, $type);
    }
}
