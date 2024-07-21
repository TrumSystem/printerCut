<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/imprimir/cupon',
        '/imprimir/novo/cupon',
        '/imprimir/aviso',
        '/imprimir/novo/aviso',
        '/imprimir/cupon/pedido',
        '/imprimir/pedido/transferencia',
        '/imprimir/pedido/comprovante/transferencia'
    ];
}
