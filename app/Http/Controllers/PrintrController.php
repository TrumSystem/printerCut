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

            $printer->text($this->left('Cliente: ISAAC DA SILVA VINCENTE') . "\n");
            $printer->text($this->left("CPF/CNPJ: 000.000.000-00") . "\n");
            $printer->text($this->left("Tel: (83) 0000-0000") . "\n");
            $printer->text($this->left("Vendedor: Eduardo") . "\n");
            $printer->text($this->left("Venda nº 000000") . "\n");
            $printer->text($traco);


            $printer->text($this->left("descricao", 14));
            $printer->text($this->left("Qtd/Unidade ", 14));
            $printer->text($this->right("Total", 16) . "\n");
            $printer->text($traco);
            // Item

            $finalString = "  ";
            $descricao = "Redmi Note 128 GB PRETO";
            $quantidade = "1X";
            $unitario = "R$ 1.349,99";
            $total = "R$ 1.349,99";

            // Calcula a quantidade de espaços necessários para alinhar

            //inicio foreach
            $printer->setEmphasis(true);
            $printer->text($this->left($descricao) . "\n");
            $printer->setEmphasis(false);
            $printer->text($this->left($quantidade, 14));
            $printer->text($this->left($unitario, 14));
            $printer->text($this->right($total, 16) . "\n");
            $printer->text($traco);
            $printer->setEmphasis(true);
            //fim foreach

            $printer->text($this->left("Total Prdutos", 22));
            $printer->text($this->right("R$ 1.349,99", 22) . "\n");
            $printer->setEmphasis(false);

            $printer->text($this->left('Subtotal', 22));
            $printer->text($this->right("R$ 1.349,99", 22) . "\n");

            $printer->text($this->left('Taxa Entr./Frete', 22));
            $printer->text($this->right("R$ 7,00", 22) . "\n");

            $printer->text($traco);
            $printer->setEmphasis(true);
            $printer->text($this->left('Total a Pagar ', 22));
            $printer->text($this->right("R$ 1.356,99", 22) . "\n");
            $printer->setEmphasis(false);

            // Detalhes de Pagamento
            //inicio foreach forma de pagamento
            $printer->text("  ---- Forma de Pagamento: ----\n");

            $printer->text($this->left('Dinheiro ', 22));
            $printer->text($this->right("R$ 1.356,99", 22) . "\n");
            //fim oreach forma de pagamento
            $printer->text("  ---- Obs: ----\n");
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
        } catch (\Exception $e) {
            echo "Não foi possível imprimir: " . $e->getMessage() . "\n";
        }
    }

    private function left($texto, $quantidadeCaracter = 44)
    {
        $espacos = str_repeat(' ', max(0, ($quantidadeCaracter) - strlen($texto)));
        return "  " . $texto . $espacos;
    }

    private function center($texto, $quantidadeCaracter = 44)
    {
        $espacos = str_repeat(' ', max(0, ($quantidadeCaracter / 2) - strlen($texto)));
        return $espacos . $texto . $espacos;
    }

    private function right($texto, $quantidadeCaracter = 44)
    {
        $espacos = str_repeat(' ', max(0, ($quantidadeCaracter) - strlen($texto)));
        return $espacos . $texto . "  ";
    }
}
