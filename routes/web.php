<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintrController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/imprimir/cupon', [PrintrController::class, 'cupon']);
Route::post('/imprimir/aviso', [PrintrController::class, 'aviso']);
Route::get('/imprimir/dados/cliente', [PrintrController::class, 'dadosCliente']);
Route::post('/imprimir/cupon/pedido', [PrintrController::class, 'dadosCliente']);
