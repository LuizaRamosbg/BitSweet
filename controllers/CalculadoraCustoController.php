<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/CalculadoraCusto.php';

class CalculadoraCustoController extends Controller {
    private $calculadora;

    public function __construct($db) {
        parent::__construct($db);
        $this->calculadora = new CalculadoraCusto($db);
    }

    public function handle(): void {
        $this->dispatch([
            'GET' => 'handleGet',
            'POST' => 'handlePost',
        ]);
    }

    protected function handleGet(): void {
        if ($this->getQuery('custo_unitario')) {
            $preco_total = (float) ($this->getQuery('preco_total') ?? 0);
            $quantidade = (float) ($this->getQuery('quantidade') ?? 0);
            $this->sendSuccess(['data' => [
                'preco_total' => $preco_total,
                'quantidade' => $quantidade,
                'custo_unitario' => $this->calculadora->calcularCustoUnitario($preco_total, $quantidade),
            ]]);
        }

        if ($this->getQuery('custo_medio_ponderado')) {
            $insumo_id = (int) ($this->getQuery('insumo_id') ?? 0);
            $this->sendSuccess(['data' => [
                'insumo_id' => $insumo_id,
                'custo_medio_ponderado' => $this->calculadora->calcularCustoMedioPonderado($insumo_id),
            ]]);
        }

        if ($this->getQuery('historico_custos')) {
            $insumo_id = (int) ($this->getQuery('insumo_id') ?? 0);
            $limite = (int) ($this->getQuery('limite') ?? 10);
            $this->sendSuccess(['data' => $this->calculadora->obterHistoricoCustos($insumo_id, $limite)]);
        }

        if ($this->getQuery('estatisticas_categoria')) {
            $this->sendSuccess(['data' => $this->calculadora->obterEstatisticasCustosPorCategoria()]);
        }

        if ($this->getQuery('custo_oportunidade')) {
            $taxa_juros = (float) ($this->getQuery('taxa_juros') ?? 0.12);
            $this->sendSuccess(['data' => [
                'custo_oportunidade_mensal' => $this->calculadora->calcularCustoOportunidadeEstoque($taxa_juros),
                'taxa_juros_anual' => $taxa_juros,
            ]]);
        }

        if ($this->getQuery('maior_impacto')) {
            $limite = (int) ($this->getQuery('limite') ?? 5);
            $this->sendSuccess(['data' => $this->calculadora->identificarInsumosMaiorImpacto($limite)]);
        }

        $this->sendError('Parâmetros inválidos', 400);
    }

    protected function handlePost(): void {
        if (isset($this->input['custo_producao'])) {
            $ingredientes = $this->input['ingredientes'] ?? [];
            $this->sendSuccess(['data' => [
                'custo_producao' => $this->calculadora->calcularCustoProducao($ingredientes),
                'ingredientes' => $ingredientes,
            ]]);
        }

        if (isset($this->input['margem_lucro'])) {
            $custo_producao = (float) ($this->input['custo_producao'] ?? 0);
            $preco_venda = (float) ($this->input['preco_venda'] ?? 0);
            $this->sendSuccess(['data' => [
                'custo_producao' => $custo_producao,
                'preco_venda' => $preco_venda,
                'margem_lucro_percentual' => $this->calculadora->calcularMargemLucro($custo_producao, $preco_venda),
            ]]);
        }

        if (isset($this->input['preco_venda'])) {
            $custo_producao = (float) ($this->input['custo_producao'] ?? 0);
            $margem_percentual = (float) ($this->input['margem_percentual'] ?? 0);
            $this->sendSuccess(['data' => [
                'custo_producao' => $custo_producao,
                'margem_percentual' => $margem_percentual,
                'preco_venda_sugerido' => $this->calculadora->calcularPrecoVenda($custo_producao, $margem_percentual),
            ]]);
        }

        if (isset($this->input['variacao_preco'])) {
            $insumo_id = (int) ($this->input['insumo_id'] ?? 0);
            $data_inicio = $this->input['data_inicio'] ?? '';
            $data_fim = $this->input['data_fim'] ?? '';
            $this->sendSuccess(['data' => [
                'insumo_id' => $insumo_id,
                'data_inicio' => $data_inicio,
                'data_fim' => $data_fim,
                'custo_medio_periodo' => $this->calculadora->calcularVariacaoPreco($insumo_id, $data_inicio, $data_fim),
            ]]);
        }

        $this->sendError('Dados inválidos', 400);
    }
}
