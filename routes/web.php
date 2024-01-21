<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintrController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/imprimir/cupon', [PrintrController::class, 'cupon']);
