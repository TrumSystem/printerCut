<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class PrintrController extends Controller
{
    private $printer;

    public function __construct()
    {
        
        $connector = new WindowsPrintConnector("smb://localhost/tm-printer");
        $this->printer = new Printer($connector);
    }

    public function cupon()
    {
        try {
            // Configuração da impressora
            $printer = $this->printer; 
            $traco = str_repeat('-', max(0, 44));
            $traco = "  " . $traco . "  \n";
            $inicioString = "  ";
            // Cabeçalho
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->text("MI JP\n");
            $printer->setTextSize(1, 1);
            $printer->text("20/01/2024 11:46 am\n");
            $printer->feed();
            // Detalhes do Cliente
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            
            $printer->text("Cliente: ISAAC DA SILVA VICENTE" . "\n");
            $printer->text("CPF/CNPJ: 000.000.000-00\n");
            $printer->text("Tel: (83) 0000-0000\n");
            $printer->text("Vendedor: Eduardo\n");
            $printer->text("Venda nº 000000\n");
            $printer->text($traco);
        
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("  Descricao         Qtd/Unidade       Total\n");

            // Item
            
            $finalString = "  ";
            $descricao = $inicioString . "Redmi Note 128 GB PRETO" . $finalString . "\n";
            $quantidade = "1X";
            $unitario = "R$ 1.349,99";
            $total = "R$ 1.349,99";
           
            // Calcula a quantidade de espaços necessários para alinhar
            
            $espacosQuantidade = str_repeat(' ', max(0, 14 - strlen($quantidade)));
            $espacosUnitario = str_repeat(' ', max(0, 14 - strlen($unitario)));
            $espacosTotal = str_repeat(' ', max(0, 16 - strlen($total))); // Ajuste a quantidade de espaços conforme necessário
            $printer->setEmphasis(true);
            $printer->text($descricao);
            $printer->setEmphasis(false);
            // Concatena as partes com os espaços para alinhar
            $linhaItem = $inicioString . $quantidade . $espacosQuantidade . $unitario . $espacosUnitario . $espacosTotal . $total . $finalString ."\n";

            // Imprime a linha do item
            $printer->text($linhaItem);
            $printer->text($traco);
            $printer->setEmphasis(true);

            $totalProduto = "Total Prdutos";
            $valorTotal = "R$ 1.349,99";

            $espacoTotalProduto = str_repeat(' ', max(0, 22 - strlen($totalProduto)));
            $espacoValorTotal = str_repeat(' ', max(0, 22 - strlen($valorTotal)));
            $linhaResultado = "  " . $totalProduto . $espacoTotalProduto . $espacoValorTotal . $valorTotal . "  \n";
            $printer->text($linhaResultado);
            $printer->setEmphasis(false);
            $printer->text("Subtotal                   R$ 1.349,99\n");
            $printer->text("Taxa Entr./Frete           R$ 7,00\n");
            $printer->text($traco);
            $printer->setEmphasis(true);
            $printer->text("Total a Pagar              R$ 1.356,99\n");
            $printer->setEmphasis(false);
        
            // Detalhes de Pagamento
            $printer->text("Dinheiro                   R$ 1.356,99\n");
            $printer->text("Entrada de R$350,00 a Vista (Dinheiro ou Pix) 10x\n");
            $printer->text("de R$ 114,00\n");
            $printer->text("Vendedor: Eduardo\n");
            $printer->text("Bairro: Tibiri 2\n");
            $printer->text($traco);
        
            // Informações do Produto
            $printer->text("IMEI 1: 000000000000000\n");
            $printer->text("IMEI 2: 000000000000000\n");
            $printer->feed();
        
            // Endereço de Entrega
            $printer->text("Endereco: Rua Deputado Iracio Bento\n");
            $printer->text("Bairro: Tibiri 2\n");
            $printer->text("CEP: 00000-000\n");
            $printer->text("Cidade: Cidade\n");
            $printer->text("UF: PB\n");
            $printer->text("Complemento: Principal Nova\n");
            $printer->text("Numero: 181 B\n");
            $printer->feed();
        
            // Finalizar impressão
            $printer->cut();
            $printer->close();
        } catch (Exception $e) {
            echo "Não foi possível imprimir: " . $e->getMessage() . "\n";
        }
    }
}
