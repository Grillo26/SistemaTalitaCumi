<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('salas-grupales')
    ->name('salas-grupales.')
    ->group(__DIR__.'/routes-salas-grupales.php');
