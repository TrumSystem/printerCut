<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class TestePrintController extends Controller
{
    public function printHelloWorld()
    {
        try {
            $connector = new WindowsPrintConnector("smb://localhost/tm-printer");
            $printer = new Printer($connector);

            $printer->text("Hello World!\n");
            $printer->feed();
            $printer->feed();
            $printer->feed();
            $printer->cut();

            // Importante: Sempre feche a conexão com a impressora ao terminar
            $printer->close();
        } catch (\Exception $e) {
            // Caso ocorra algum erro, você pode querer tratar aqui
            echo "Não foi possível imprimir. Erro: " . $e->getMessage();
            exit();
        }

        return "Mensagem 'Hello World!' enviada para a impressora.";
    }
}

