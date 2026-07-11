@extends('errors.minimal')

@section('title', 'Acceso denegado')
@section('code', '403')
@section('message', 'No cuentas con los permisos o roles necesarios para ingresar a este módulo o realizar esta acción dentro del sistema.')

@section('accent-color', 'bg-danger-600 dark:bg-danger-500')
@section('badge-color', 'bg-danger-500/10 text-danger-active dark:text-danger border border-danger-500/30')
@section('dot-color', 'bg-danger-500')
