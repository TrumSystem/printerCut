<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class PrintrController extends Controller
{
    private $connector;

    public function __construct()
    {
        $this->connector = new WindowsPrintConnector("smb://localhost/tm-printer");
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
            $printer = new Printer($this->connector);
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
            $printer->text($this->left($request->modelo . '  ' . $request->cor) . "\n");
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
            $printer->text("CEP: " . $request->cep . "\n");
            $printer->text("Cidade: " . $request->cidade . "\n");
            $printer->text("UF: PB\n");
            $printer->text("Complemento: " . $request->complemento . "\n");
            $printer->text("Numero: " . $request->numero . "\n");
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
        } catch (\Throwable $e) {
            echo "Não foi possível imprimir: " . $e->getMessage() . "\n";
        }
    }

    public function novoCupon(Request $request)
    {
        $pedido = $this->convertObj($request->pedido);
        $produtos = $this->convertObj($request->produtos);
        $loja = $this->convertObj($request->loja);
        $cliente = $this->convertObj($request->cliente);
        $pagamentos = $this->convertObj($request->pagamentos);
        $total_unidade = 0;
        var_dump($pagamentos);
        return;
        try {
            // Configuração da impressora
            $printer = new Printer($this->connector);
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            // Cabeçalho
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->text($this->center($loja->name, 14) . "\n");
            $printer->feed();
            $printer->setTextSize(1, 1);
            $printer->text(date("d/m/Y H:i:s", strtotime($pedido->created_at ?? '')) . "\n");
            $printer->feed();
            // Detalhes do Cliente
            $printer->setPrintLeftMargin(16 * 2);
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text($this->left("Cliente:" . $cliente->nome ?? '') . "\n");
            $printer->text($this->left("Tel: " . $this->formatarCelular(($request->contato ?? ''))) . "\n");

            $printer->text($this->left("Atendente: " . ($pedido->atendente ?? '')) . "\n");
            $printer->text($this->left("Pedido:" . $pedido->id) . "\n");
            $printer->text($traco);

            $printer->text($this->left("descricao", 14));
            $printer->text($this->center("Qtd/Unidade ", 14));
            $printer->text($this->right("Total", 16) . "\n");
            $printer->text($traco);

            // Item
            foreach ($produtos as $produto) {
                $total_unidade += $produto->quantidade;
                $quantidade = $produto->quantidade . "X";
                $unitario = "R$ " . number_format($produto->valor_unico, 2, ',', '.');
                $total = "R$ " . number_format($produto->valor_total, 2, ',', '.');

                $printer->setEmphasis(true);
                $printer->text($this->left($produto->name) . "\n");
                $printer->setEmphasis(false);
                $printer->text($this->left($quantidade, 14));
                $printer->text($this->center($unitario, 14));
                $printer->text($this->right($total, 16) . "\n");
                $printer->text($traco);
                $printer->setEmphasis(true);
            }

            $printer->text($this->left("Total de unidades", 22));
            $printer->text($this->right($total_unidade, 22) . "\n");
            $printer->setEmphasis(false);
            $printer->text($traco);
            $printer->text($this->left('Subtotal', 22));
            $printer->text($this->right("R$ " . number_format($pedido->valor_venda, 2, ',', '.'), 22) . "\n");

            $printer->text($traco);
            $printer->setEmphasis(true);
            $printer->text($this->left("Total a pagar", 22));
            $printer->text($this->right("R$ " . number_format($pedido->valor_venda, 2, ',', '.'), 22) . "\n");

            $printer->setEmphasis(false);

            $printer->feed();
            $printer->text($this->center("---- Forma de Pagamento: ----") . "\n");
            // Detalhes de Pagamento

            $form = [
                'Dinheiro' => $pedido->dinheiro,
                'Cartão' => $pedido->cartao,
                'Crediario' => $pedido->crediario,
                'Pix' => $pedido->doc,
                'Cheque' => $pedido->cheque,
            ];

            foreach ($pagamentos as $key => $pagamento) {
                if ($pagamento > 0) {
                    $printer->setEmphasis(true);
                    $printer->text($this->left($key, 22));
                    $printer->text($this->right("R$ " . number_format($pagamento, 2, ',', '.'), 22) . "\n");
                    $printer->feed();
                }
            }

            $printer->text($traco);
            $printer->setEmphasis(true);
            $printer->text($this->left("Total a pago", 22));
            $printer->text($this->right("R$ " . number_format($pedido->valor_venda, 2, ',', '.'), 22) . "\n");
            $printer->feed();
            $printer->feed();
            $printer->text($this->center("Tudo posso naquele que me fortalece.", 44));
            $printer->text($this->center("- Filipenses 4:13 -", 44));
            $printer->feed();
            $printer->feed();
            $printer->cut();
            $printer->close();

            return response()->json(['success' => 'Impresso com sucesso']);
        } catch (\Throwable $e) {
            return response()->json(["error" => $e->getMessage()]);
        }
    }

    public function convertObj($array)
    {
        return json_decode(json_encode($array), false);
    }

    public function cuponNaoFiscal(Request $request)
    {
        $pedido = $this->convertObj($request->pedido);
        $produtos = $this->convertObj($request->produtos);
        $loja = $this->convertObj($request->loja);

        $cliente = $this->convertObj($request->cliente);
        $total_unidade = 0;

        try {
            // Configuração da impressora
            $printer = new Printer($this->connector);
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            // Cabeçalho
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->text($this->center($loja->name, 14) . "\n");
            $printer->feed();
            $printer->setTextSize(1, 1);
            $printer->text(date("d/m/Y H:i:s", strtotime($pedido->created_at ?? '')) . "\n");
            $printer->feed();
            // Detalhes do Cliente
            $printer->setPrintLeftMargin(16 * 2);
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text($this->left("Cliente:" . $cliente->nome ?? '') . "\n");
            $printer->text($this->left("Tel: " . $this->formatarCelular(($request->contato ?? ''))) . "\n");

            $printer->text($this->left("Atendente: " . ($pedido->atendente ?? '')) . "\n");
            $printer->text($this->left("Pedido:" . $pedido->id) . "\n");
            $printer->text($traco);

            $printer->text($this->left("descricao", 14));
            $printer->text($this->center("Qtd/Unidade ", 14));
            $printer->text($this->right("Total", 16) . "\n");
            $printer->text($traco);

            // Item
            foreach ($produtos as $produto) {
                $total_unidade += $produto->quantidade;
                $quantidade = $produto->quantidade . "X";
                $unitario = "R$ " . number_format($produto->valor_unico, 2, ',', '.');
                $total = "R$ " . number_format($produto->valor_total, 2, ',', '.');

                $printer->setEmphasis(true);
                $printer->text($this->left($produto->name) . "\n");
                $printer->setEmphasis(false);
                $printer->text($this->left($quantidade, 14));
                $printer->text($this->center($unitario, 14));
                $printer->text($this->right($total, 16) . "\n");
                $printer->text($traco);
                $printer->setEmphasis(true);
            }

            $printer->text($this->left("Total de unidades", 22));
            $printer->text($this->right($total_unidade, 22) . "\n");
            $printer->setEmphasis(false);
            $printer->text($traco);
            $printer->text($this->left('Subtotal', 22));
            $printer->text($this->right("R$ " . number_format($pedido->valor_venda, 2, ',', '.'), 22) . "\n");

            $printer->text($traco);
            $printer->setEmphasis(true);
            $printer->text($this->left("Total a pagar", 22));
            $printer->text($this->right("R$ " . number_format($pedido->valor_venda, 2, ',', '.'), 22) . "\n");

            $printer->setEmphasis(false);

            $printer->feed();
            $printer->text($this->center("---- Forma de Pagamento: ----") . "\n");
            // Detalhes de Pagamento

            $form = [
                'Dinheiro' => $pedido->dinheiro,
                'Cartão' => $pedido->cartao,
                'Crediario' => $pedido->crediario,
                'Pix' => $pedido->doc,
                'Cheque' => $pedido->cheque,
            ];

            foreach ($form as $key => $pagamento) {
                if ($pagamento > 0) {
                    $printer->setEmphasis(true);
                    $printer->text($this->left($key, 22));
                    $printer->text($this->right("R$ " . number_format($pagamento, 2, ',', '.'), 22) . "\n");
                    $printer->feed();
                }
            }

            $printer->text($traco);
            $printer->setEmphasis(true);
            $printer->text($this->left("Total a pago", 22));
            $printer->text($this->right("R$ " . number_format($pedido->valor_venda, 2, ',', '.'), 22) . "\n");
            $printer->feed();
            $printer->feed();
            $printer->text($this->center("Tudo posso naquele que me fortalece.", 44));
            $printer->text($this->center("- Filipenses 4:13 -", 44));
            $printer->feed();
            $printer->feed();
            $printer->cut();
            $printer->close();

            return response()->json(['success' => 'Impresso com sucesso']);
        } catch (\Throwable $e) {
            return response()->json(["error" => $e->getMessage()]);
        }
    }

    public function aviso(Request $request)
    {

        $printer = new Printer($this->connector);
        $printer->text("Hello World!\n");
        $printer->feed();
        $printer->feed();
        $printer->feed();
        $printer->cut();
        $printer->close();
        return "Ola mundo!";
        try {
            $printer = new Printer($this->connector);
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            $printer->setPrintLeftMargin(16 * 2);
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->setEmphasis(true);
            $printer->text("Modelo: " . $this->left($request->modelo . '  ' . $request->cor) . "\n");
            $printer->setEmphasis(false);

            $traco = $traco . "\n";

            $printer->text($this->left("Cliente: $request->cliente") . "\n");
            $printer->text("Endereco: " . $request->rua . "\n");
            $printer->text("Bairro: " . $request->bairro . "\n");
            $printer->text("Cidade: " . $request->cidade . "\n");
            $printer->text("Cep: " . $request->cep . "\n");
            $printer->text("UF: PB\n");
            $printer->text($this->left("Venda nº " . $request->numeroOs) . "\n");

            $delivery = $request->delivery == '0' ? 'Retira em Loja' : 'Entrega em Domicilio';

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->feed();
            $printer->text($delivery . "\n");
            $printer->feed();
            $printer->cut();
            $printer->close();
            return;
        } catch (\Throwable $e) {
            echo "Não foi possível imprimir: " . $e->getMessage() . "\n";
        }
    }

    public function novoAviso(Request $request)
    {
        try {
            $printer = new Printer($this->connector);
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            $printer->setPrintLeftMargin(16 * 2);
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->setEmphasis(true);
            $printer->text("Modelo: " . $this->left($request->modelo) . "\n");
            $printer->setEmphasis(false);

            $traco = $traco . "\n";

            $printer->text($this->left("Cliente: $request->cliente") . "\n");
            $printer->text("Endereco: " . $request->rua . "\n");
            $printer->text("Bairro: " . $request->bairro . "\n");
            $printer->text("Cidade: " . $request->cidade . "\n");
            $printer->text("Cep: " . $request->cep . "\n");
            $printer->text("UF: PB\n");
            $printer->text($this->left("Venda nº " . $request->venda_id) . "\n");

            $delivery = $request->delivery == '0' ? 'Retira em Loja' : 'Entrega em Domicilio';

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->feed();
            $printer->text($delivery . "\n");
            $printer->feed();
            $printer->cut();
            $printer->close();
            return;
        } catch (\Throwable $e) {
            return response()->json(['error' => "Não foi possível imprimir: " . $e->getMessage()]);
        }
    }

    public function dadosCliente(Request $request)
    {
        try {
            $printer = new Printer($this->connector);
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            $printer->setPrintLeftMargin(16 * 2);

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setTextSize(1, 2);
            $printer->text($this->left("Cliente: $request->nome") . "\n");
            $printer->feed();
            $printer->text($this->left("Pedido: $request->idPedido") . "\n");
            $printer->feed();

            $printer->setTextSize(1, 1);

            $traco = $traco . "\n";
            $printer->setEmphasis(true);
            $printer->text("Endereço: " . $request->endereco . "\n");
            $printer->text("Bairro: " . $request->bairro . "\n");
            $printer->text("Cidade: " . $request->cidade . "\n");
            $printer->text($this->left("UF: " . $request->estado) . "\n");
            $delivery = $request->delivery == false ? 'Retira em Loja' : 'Entrega em Domicilio';

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->feed();
            $printer->text($delivery . "\n");
            $printer->feed();

            $printer->setTextSize(1, 1);
            $printer->text($this->center("Tudo posso naquele que me fortalece.", 44));
            $printer->text($this->center("- Filipenses 4:13 -", 44));
            $printer->feed();
            $printer->feed();
            $printer->cut();
            $printer->close();
            return;
        } catch (\Throwable $e) {
            return response()->json(["error" => $e->getMessage()]);
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

    public function imprimirProdutosTransferencia(Request $request)
    {
        try {
            $produtos = $this->convertObj($request->produtos);
            $loja = $this->convertObj($request->loja);
            $total_unidade = 0;
            $subtotal = 0;
            $valorTotalPedido = 0;

            $printer = new Printer($this->connector);
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            // Cabeçalho
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->text($this->center($loja->tipo, 14) . "\n");
            $printer->feed();
            $printer->setTextSize(1, 1);
            $printer->text(date("d/m/Y H:i:s", strtotime($loja->created_at ?? '')) . "\n");
            $printer->feed();
            // Detalhes do Cliente
            $printer->setPrintLeftMargin(16 * 2);
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text($this->left("Cliente:" . $loja->name ?? '') . "\n");

            $printer->text($this->left("Atendente: " . ($loja->atendente ?? '')) . "\n");
            $printer->text($this->left("Pedido:" . $loja->idPedido) . "\n");
            $printer->text($traco);

            $printer->text($this->left("descricao", 14));
            $printer->text($this->center("Qtd/Unidade ", 14));
            $printer->text($this->right("Total", 16) . "\n");
            $printer->text($traco);

            // Item
            foreach ($produtos as $produto) {
                $subtotal = ($produto->price_purchase * $produto->estoque);
                $valorTotalPedido += $subtotal;
                $total_unidade += $produto->estoque;
                $quantidade = $produto->estoque . "X";
                $unitario = "R$ " . number_format($produto->price_purchase, 2, ',', '.');
                $total = "R$ " . number_format($subtotal, 2, ',', '.');

                $printer->setEmphasis(true);
                $printer->text($this->left($produto->name) . "\n");
                $printer->setEmphasis(false);
                $printer->text($this->left($quantidade, 14));
                $printer->text($this->center($unitario, 14));
                $printer->text($this->right($total, 16) . "\n");
                $printer->text($traco);
            }

            $printer->text($traco);
            $printer->text($this->left("Total de Modelo", 22));
            $printer->text($this->right(count($produtos), 22) . "\n");
            $printer->setEmphasis(false);
            $printer->text($traco);
            $printer->text($this->left('Total de Unidade.', 22));
            $printer->text($this->right($total_unidade . "\n"));

            $printer->feed();
            $printer->feed();
            $printer->cut();
            $printer->close();

            return response()->json(['success' => 'Impresso com sucesso']);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()]);
        }
    }

    public function finalizarTransferencia(Request $request)
    {
        try {
            $i = 0;
            $cupon = 1;
            $produtos = $this->convertObj($request->produtos);
            $dados = $this->convertObj($request->all());

            $printer = new Printer($this->connector);
            $traco = str_repeat('-', max(0, 44));
            $traco = $traco . "\n";
            // Cabeçalho
            if ($dados->possuiImei) {
                $cupon = $dados->totalCupon;
            }

            for ($i = 0; $i < $cupon; $i++) {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(2, 2);
                $printer->text($this->center('Transferencia', 14) . "\n");
                $printer->feed();
                // Detalhes do Cliente
                $printer->setPrintLeftMargin(16 * 2);
                $printer->setJustification(Printer::JUSTIFY_LEFT);

                $printer->text($this->left("Cliente:" . $dados->loja ?? '') . "\n");
                $printer->text($this->left("Pedido:" . $dados->idPedido) . "\n");
                $printer->text($traco);
                $printer->feed();
                $printer->feed();

                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setTextSize(3, 3);
                $printer->text($this->center("(" . $i . "/" . $cupon . ")", 14) . "\n");
                $printer->feed();
                $printer->feed();

                $printer->cut();
            }

            $printer->close();

            return response()->json(['success' => 'Impresso com sucesso']);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()]);
        }
    }
}
