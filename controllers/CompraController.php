<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Compra.php';

class CompraController extends Controller {
    private $compra;

    public function __construct($db) {
        parent::__construct($db);
        $this->compra = new Compra($db);
    }

    public function handle(): void {
        $this->dispatch([
            'GET' => 'handleGet',
            'POST' => 'handlePost',
        ]);
    }

    protected function handleGet(): void {
        if ($this->getQuery('custo_medio') && ($insumo_id = (int) $this->getQuery('insumo_id'))) {
            $custo_medio = $this->compra->calcularCustoMedioPonderado($insumo_id);
            $this->sendSuccess(['data' => ['custo_medio_ponderado' => $custo_medio]]);
        }

        if ($id = $this->getQuery('id')) {
            if ($this->compra->buscarPorId($id)) {
                $this->sendSuccess(['data' => [
                    'id' => $this->compra->id,
                    'insumo_id' => $this->compra->insumo_id,
                    'quantidade' => $this->compra->quantidade,
                    'preco_total' => $this->compra->preco_total,
                    'custo_unitario' => $this->compra->custo_unitario,
                    'fornecedor' => $this->compra->fornecedor,
                    'data_compra' => $this->compra->data_compra,
                    'observacoes' => $this->compra->observacoes,
                ]]);
            }
            $this->sendError('Compra não encontrada', 404);
        }

        if ($insumo_id = $this->getQuery('insumo_id')) {
            $stmt = $this->compra->listarPorInsumo($insumo_id);
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        if ($this->getQuery('estatisticas')) {
            $this->sendSuccess(['data' => $this->compra->obterEstatisticas()]);
        }

        $stmt = $this->compra->listar();
        $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
    }

    protected function handlePost(): void {
        if (empty($this->input['insumo_id']) || empty($this->input['quantidade']) || empty($this->input['preco_total'])) {
            $this->sendError('Dados obrigatórios não fornecidos', 400);
        }

        $this->compra->insumo_id = $this->input['insumo_id'];
        $this->compra->quantidade = $this->input['quantidade'];
        $this->compra->preco_total = $this->input['preco_total'];
        $this->compra->fornecedor = $this->input['fornecedor'] ?? '';
        $this->compra->data_compra = $this->input['data_compra'] ?? date('Y-m-d');
        $this->compra->observacoes = $this->input['observacoes'] ?? '';
        $this->compra->lote = $this->input['lote'] ?? '';
        $this->compra->data_fabricacao = $this->input['data_fabricacao'] ?? '';
        $this->compra->data_validade = $this->input['data_validade'] ?? '';

        if ($this->compra->registrar()) {
            $this->sendSuccess(['data' => ['id' => $this->compra->id, 'custo_unitario' => $this->compra->custo_unitario]], 'Compra registrada com sucesso', 201);
        }

        $this->sendError('Erro ao registrar compra', 500);
    }
}
