<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintrController;
use App\Http\Controllers\TestePrintController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/imprimir/cupon', [PrintrController::class, 'cupon']);
Route::post('/imprimir/novo/cupon', [PrintrController::class, 'novoCupon']);
Route::post('/imprimir/aviso', [PrintrController::class, 'aviso']);
Route::post('/imprimir/novo/aviso', [PrintrController::class, 'novoAviso']);
Route::get('/imprimir/aviso/Teste', [TestePrintController::class, 'printHelloWorld']);
Route::get('/imprimir/dados/cliente', [PrintrController::class, 'dadosCliente']);
Route::post('/imprimir/cupon/pedido', [PrintrController::class, 'cuponNaoFiscal']);
Route::post('/imprimir/pedido/transferencia', [PrintrController::class, 'imprimirProdutosTransferencia']);
Route::post('/imprimir/pedido/comprovante/transferencia', [PrintrController::class, 'finalizarTransferencia']);
