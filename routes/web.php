<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintrController;
use App\Http\Controllers\TestePrintController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/imprimir/cupon', [PrintrController::class, 'cupon']);
Route::get('/imprimir/aviso', [PrintrController::class, 'aviso']);
Route::get('/imprimir/aviso/Teste', [TestePrintController::class, 'printHelloWorld']);
Route::get('/imprimir/dados/cliente', [PrintrController::class, 'dadosCliente']);
Route::post('/imprimir/cupon/pedido', [PrintrController::class, 'cuponNaoFiscal']);
