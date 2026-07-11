@extends('errors.minimal')

@section('title', 'Sesión expirada')
@section('code', '419')
@section('message', 'Tu sesión ha caducado por inactividad o tu token de seguridad ha expirado. Por favor regresa al inicio de sesión e ingresa nuevamente.')

@section('accent-color', 'bg-info-600 dark:bg-info-500')
@section('badge-color', 'bg-info-500/10 text-info-active dark:text-info border border-info-500/30')
@section('dot-color', 'bg-info-500')
