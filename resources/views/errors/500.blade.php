@extends('errors.minimal')

@section('title', 'Error interno del servidor')
@section('code', '500')
@section('message', 'Se ha producido un error inesperado al procesar tu solicitud. Nuestro equipo de desarrollo ha sido notificado y estamos revisando el incidente.')

@section('accent-color', 'bg-danger-600 dark:bg-danger-500')
@section('badge-color', 'bg-danger-500/10 text-danger-active dark:text-danger border border-danger-500/30')
@section('dot-color', 'bg-danger-500')
