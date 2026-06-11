<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('requisitions.index', function ($user) {
    // Permitir a cualquier usuario autenticado suscribirse a este canal para ver actualizaciones de la tabla
    return $user !== null;
});
