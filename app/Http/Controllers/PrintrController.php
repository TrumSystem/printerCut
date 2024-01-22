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

    private function formatarCelular($celular)
    {
        // Remove qualquer caractere não numérico
        $celular = preg_replace("/[^0-9]/", "", $celular);

        // Verifica se o número de celular tem 11 dígitos (com o DDD)
        if (strlen($celular) == 11) {
            // Verifica se o número já está formatado no formato (XX) 9XXXX-XXXX
            if (preg_match("/^\d{2}\s9\d{4}-\d{4}$/", $celular)) {
                return $celular; // Já está formatado, retorna o mesmo número
            } else {
                return '(' . substr($celular, 0, 2) . ') ' . substr($celular, 2, 5) . '-' . substr($celular, 7, 4);
            }
        } else {
            return false; // Número de celular inválido
        }
    }

    private function formatarDocumento($documento)
    {
        // Remove qualquer caractere não numérico
        $documento = preg_replace("/[^0-9]/", "", $documento);

        // Verifica se o documento é um CPF (11 dígitos) ou CNPJ (14 dígitos)
        if (strlen($documento) == 11) {
            return substr($documento, 0, 3)
                . '.'
                . substr($documento, 3, 3)
                . '.'
                . substr($documento, 6, 3)
                . '-'
                . substr($documento, 9, 2);
        } elseif (strlen($documento) == 14) {
            return substr($documento, 0, 2)
                . '.'
                . substr($documento, 2, 3)
                . '.'
                . substr($documento, 5, 3)
                . '/'
                . substr($documento, 8, 4)
                . '-'
                . substr($documento, 12, 2);
        } else {
            return false; // Documento inválido
        }
    }

    public function cupon(Request $request)
    {
        try {
            // Configuração da impressora
            $printer = $this->printer;
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            // Cabeçalho
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->text("MI JP\n");
            $printer->setTextSize(1, 1);
            $printer->text(date("d/m/Y H:i:s", strtotime($request->created_at)) . "\n");
            $printer->feed();
            // Detalhes do Cliente
            $printer->setPrintLeftMargin(16 * 2);
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text($this->left("Cliente: $request->cliente") . "\n");
            $printer->text($this->left("CPF/CNPJ: " . $this->formatarDocumento($request->cpf)) . "\n");
            $printer->text($this->left("Tel: " . $this->formatarCelular($request->contato)) . "\n");
            $printer->text($this->left("Atendente: " . $request->atendente) . "\n");
            $printer->text($this->left("Venda nº " . $request->numeroOs) . "\n");
            $printer->text($traco);

            $printer->text($this->left("descricao", 14));
            $printer->text($this->center("Qtd/Unidade ", 14));
            $printer->text($this->right("Total", 16) . "\n");
            $printer->text($traco);
            // Item
            $quantidade = $request->quantidade ?? 1 . "X";
            $unitario = "R$ " . number_format($request->valorTotal, 2, ',', '.');
            $total = "R$ " . number_format($request->valorTotal, 2, ',', '.');

            // Calcula a quantidade de espaços necessários para alinhar

            //inicio foreach
            $printer->setEmphasis(true);
            $printer->text($this->left($request->modelo . $request->cor) . "\n");
            $printer->setEmphasis(false);
            $printer->text($this->left($quantidade, 14));
            $printer->text($this->center($unitario, 14));
            $printer->text($this->right($total, 16) . "\n");
            $printer->text($traco);
            $printer->setEmphasis(true);
            //fim foreach

            $printer->text($this->left("Total Prdutos", 22));
            $printer->text($this->right($total, 22) . "\n");
            $printer->setEmphasis(false);

            $printer->text($this->left('Subtotal', 22));
            $printer->text($this->right($total, 22) . "\n");

            $printer->text($this->left('Taxa Entr./Frete', 22));
            $printer->text($this->right("R$ " . number_format($request->taxa ?? 0.00, 2, ',', '.'), 22) . "\n");

            $printer->text($traco);
            $printer->setEmphasis(true);
            $totalPagar = ($request->taxa ?? 0.00) + ($request->valorTotal * 1);
            $printer->text($this->left('Total a Pagar ', 22));
            $printer->text($this->right("R$ " . number_format($totalPagar, 2, ',', '.'), 22) . "\n");
            $printer->setEmphasis(false);

            $printer->feed();
            $printer->text($this->center("---- Forma de Pagamento: ----") . "\n");
            // Detalhes de Pagamento
            //inicio foreach forma de pagamento
            $printer->text($this->left(
                $request->formaPagamento == "Ávista" ? "Dinheiro" : $request->formaPagamento,
                22
            ));

            $printer->text($this->right("R$ " . number_format($request->valorTotal, 2, ',', '.'), 22) . "\n");
            //fim oreach forma de pagamento
            $printer->text($traco);

            $printer->feed();
            $printer->text($this->center("---- Obs: ----") . "\n");
            $printer->text($this->left($request->obs) . "\n");
            $printer->text($traco);

            // Informações do Produto
            $printer->feed();
            $printer->text("IMEI 1: " . $request->imei1 . "\n");
            $printer->text("IMEI 2: " . $request->imei2 . "\n");
            $printer->feed();

            // Endereço de Entrega
            $printer->text("Endereco: " . $request->rua . "\n");
            $printer->text("Bairro: " . $request->bairro . "\n");
            $printer->text("CEP: 00000-000\n");
            $printer->text("Cidade: " . $request->cidade . "\n");
            $printer->text("UF: PB\n");
            //$printer->text("Complemento:\n");
            //$printer->text("Numero: 181 B\n");
            $printer->feed();

            // Finalizar impressão
            $printer->cut();

            $printer->text($this->left("Cliente: $request->cliente") . "\n");
            $printer->text($this->left("CPF/CNPJ: " . $this->formatarDocumento($request->cpf)) . "\n");
            $printer->text($this->left("Tel: " . $this->formatarCelular($request->contato)) . "\n");
            $printer->text($this->left("Atendente: " . $request->atendente) . "\n");
            $printer->text($this->left("Venda nº " . $request->numeroOs) . "\n");
            $printer->text($traco);

            $printer->feed();

            // Finalizar impressão
            $printer->cut();
            $printer->close();

            return;
        } catch (\Exception $e) {
            echo "Não foi possível imprimir: " . $e->getMessage() . "\n";
        }
    }

    public function aviso(Request $request)
    {
        try {
            $printer = $this->printer;
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            $printer->setPrintLeftMargin(16 * 2);
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->setEmphasis(true);
            $printer->text($this->left($request->modelo . $request->cor) . "\n");
            $printer->setEmphasis(false);

            $traco = $traco . "\n";

            $printer->text($this->left("Cliente: $request->cliente") . "\n");
            $printer->text("Endereco: " . $request->rua . "\n");
            $printer->text("Bairro: " . $request->bairro . "\n");
            $printer->text("Cidade: " . $request->cidade . "\n");
            $printer->text("UF: PB\n");
            $printer->text($this->left("Venda nº " . $request->numeroOs) . "\n");

            $printer->feed();
            $printer->cut();
            $printer->close();
            return;
        } catch (\Exception $e) {
            echo "Não foi possível imprimir: " . $e->getMessage() . "\n";
        }
    }

    private function left($texto, $quantidadeCaracter = 44)
    {
        $espacos = str_repeat(' ', max(0, ($quantidadeCaracter) - strlen($texto)));
        return $texto . $espacos;
    }

    private function center($texto, $quantidadeCaracter = 44)
    {
        $espacos = str_repeat(' ', max(0, (($quantidadeCaracter) - strlen($texto)) / 2));
        return $espacos . $texto . $espacos;
    }

    private function right($texto, $quantidadeCaracter = 44)
    {
        $espacos = str_repeat(' ', max(0, ($quantidadeCaracter) - strlen($texto)));
        return $espacos . $texto;
    }
}
