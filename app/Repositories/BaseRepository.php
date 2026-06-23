<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    /**
     * El nombre de la clase del modelo que maneja este repositorio.
     * Ejemplo: Category::class
     */
    protected string $modelClass;

    /**
     * Guarda un registro. Crea si $id es nulo, actualiza si existe.
     * Elimina el uso innecesario de DB::transaction para operaciones simples.
     *
     * @param array $data Los datos a guardar (obtenidos del DTO)
     * @param int|null $id El ID del registro (para actualizaciones)
     * @return Model
     */
    protected function saveRecord(array $data, ?int $id = null): Model
    {
        if ($id) {
            $record = $this->modelClass::findOrFail($id);
            // Filtramos el array para que solo se actualicen los campos pasados
            $record->update(array_filter($data, fn($value) => $value !== null));
            return $record;
        }

        return $this->modelClass::create($data);
    }

    /**
     * Elimina un registro por ID.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->modelClass::findOrFail($id)->delete();
    }

    /**
     * Elimina múltiples registros.
     *
     * @param array $ids
     * @return void
     */
    public function bulkDelete(array $ids): void
    {
        if (!empty($ids)) {
            $this->modelClass::whereIn('id', $ids)->get()->each->delete();
        }
    }
}
