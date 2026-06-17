<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/ControleValidade.php';

class ControleValidadeController extends Controller {
    private $controle;

    public function __construct($db) {
        parent::__construct($db);
        $this->controle = new ControleValidade($db);
    }

    public function handle(): void {
        $this->dispatch([
            'GET' => 'handleGet',
            'POST' => 'handlePost',
            'PUT' => 'handlePut',
            'DELETE' => 'handleDelete',
        ]);
    }

    protected function handleGet(): void {
        if ($id = (int) $this->getQuery('id')) {
            if ($this->controle->buscarPorId($id)) {
                $this->sendSuccess(['data' => [
                    'id' => $this->controle->id,
                    'insumo_id' => $this->controle->insumo_id,
                    'lote' => $this->controle->lote,
                    'quantidade_lote' => $this->controle->quantidade_lote,
                    'data_fabricacao' => $this->controle->data_fabricacao,
                    'data_validade' => $this->controle->data_validade,
                    'quantidade_atual' => $this->controle->quantidade_atual,
                    'status' => $this->controle->status,
                    'observacoes' => $this->controle->observacoes,
                ]] );
            }
            $this->sendError('Lote não encontrado', 404);
        }

        if ($this->getQuery('por_insumo')) {
            $insumo_id = (int) ($this->getQuery('insumo_id') ?? 0);
            if ($insumo_id <= 0) {
                $this->sendError('ID do insumo não fornecido', 400);
            }
            $stmt = $this->controle->listarLotesPorInsumo($insumo_id);
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        if ($this->getQuery('proximos_vencer')) {
            $dias = (int) ($this->getQuery('dias') ?? 7);
            $stmt = $this->controle->listarLotesProximosVencer($dias);
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        if ($this->getQuery('vencidos')) {
            $stmt = $this->controle->listarLotesVencidos();
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        if ($this->getQuery('alertas')) {
            $this->controle->verificarAlertasValidade();
            $stmt = $this->controle->listarAlertasNaoVisualizados();
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        if ($this->getQuery('estatisticas')) {
            $this->sendSuccess(['data' => $this->controle->obterEstatisticasValidade()]);
        }

        if ($this->getQuery('verificar_alertas')) {
            $alertas_gerados = $this->controle->verificarAlertasValidade();
            $this->sendSuccess(['data' => ['alertas_gerados' => $alertas_gerados]], "Verificação concluída. {$alertas_gerados} novos alertas gerados.");
        }

        $stmt = $this->controle->listarLotes();
        $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
    }

    protected function handlePost(): void {
        if (isset($this->input['cadastrar_lote'])) {
            if (empty($this->input['insumo_id']) || empty($this->input['data_validade'])) {
                $this->sendError('Dados obrigatórios não fornecidos', 400);
            }

            $this->controle->insumo_id = $this->input['insumo_id'];
            $this->controle->lote = $this->input['lote'] ?? '';
            $this->controle->quantidade_lote = $this->input['quantidade_lote'] ?? 0;
            $this->controle->data_fabricacao = $this->input['data_fabricacao'] ?? null;
            $this->controle->data_validade = $this->input['data_validade'];
            $this->controle->quantidade_atual = $this->input['quantidade_atual'] ?? $this->controle->quantidade_lote;
            $this->controle->observacoes = $this->input['observacoes'] ?? '';

            if ($this->controle->cadastrarLote()) {
                $this->sendSuccess(['data' => ['id' => $this->controle->id, 'status' => $this->controle->status]], 'Lote cadastrado com sucesso', 201);
            }

            $this->sendError('Erro ao cadastrar lote', 500);
        }

        if (isset($this->input['consumir_quantidade'])) {
            $lote_id = (int) ($this->input['lote_id'] ?? 0);
            $quantidade_consumida = (float) ($this->input['quantidade_consumida'] ?? 0);

            if ($lote_id <= 0 || $quantidade_consumida <= 0) {
                $this->sendError('Dados obrigatórios não fornecidos', 400);
            }

            if (!$this->controle->buscarPorId($lote_id)) {
                $this->sendError('Lote não encontrado', 404);
            }

            if ($this->controle->consumirQuantidade($quantidade_consumida)) {
                $this->sendSuccess([], 'Quantidade consumida com sucesso');
            }
            $this->sendError('Quantidade insuficiente no lote', 400);
        }

        if (isset($this->input['marcar_alerta_visualizado'])) {
            $alerta_id = (int) ($this->input['alerta_id'] ?? 0);
            if ($alerta_id <= 0) {
                $this->sendError('ID do alerta não fornecido', 400);
            }
            if ($this->controle->marcarAlertaVisualizado($alerta_id)) {
                $this->sendSuccess([], 'Alerta marcado como visualizado');
            }
            $this->sendError('Erro ao marcar alerta', 500);
        }

        $this->sendError('Dados inválidos', 400);
    }

    protected function handlePut(): void {
        if (empty($this->input['id']) || !isset($this->input['quantidade_atual'])) {
            $this->sendError('Dados obrigatórios não fornecidos', 400);
        }

        $this->controle->id = $this->input['id'];
        if ($this->controle->atualizarQuantidade($this->input['quantidade_atual'])) {
            $this->sendSuccess([], 'Quantidade atualizada com sucesso');
        }

        $this->sendError('Erro ao atualizar quantidade', 500);
    }

    protected function handleDelete(): void {
        if (empty($this->input['id'])) {
            $this->sendError('ID do lote não fornecido', 400);
        }

        $this->controle->id = $this->input['id'];
        if ($this->controle->excluirLote()) {
            $this->sendSuccess([], 'Lote excluído com sucesso');
        }

        $this->sendError('Erro ao excluir lote', 500);
    }
}
