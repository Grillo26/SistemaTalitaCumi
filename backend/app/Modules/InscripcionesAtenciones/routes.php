<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('salas-grupales')
    ->name('salas-grupales.')
    ->group(__DIR__.'/routes-salas-grupales.php');

Route::middleware('auth:sanctum')
    ->prefix('paquetes-sesiones')
    ->name('paquetes-sesiones.')
    ->group(__DIR__.'/routes-paquetes-sesiones.php');

Route::middleware('auth:sanctum')
    ->prefix('entrevista-diagnostico')
    ->name('entrevista-diagnostico.')
    ->group(__DIR__.'/routes-entrevista-diagnostico.php');
