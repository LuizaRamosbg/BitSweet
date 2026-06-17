<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Insumo.php';

class InsumoController extends Controller {
    private $insumo;

    public function __construct($db) {
        parent::__construct($db);
        $this->insumo = new Insumo($db);
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
        if ($id = $this->getQuery('id')) {
            if ($this->insumo->buscarPorId($id)) {
                $data = [
                    'id' => $this->insumo->id,
                    'nome' => $this->insumo->nome,
                    'descricao' => $this->insumo->descricao,
                    'unidade_compra' => $this->insumo->unidade_compra,
                    'fator_conversao' => $this->insumo->fator_conversao,
                    'estoque_atual' => $this->insumo->estoque_atual,
                    'estoque_minimo' => $this->insumo->estoque_minimo,
                    'custo_unitario_atual' => $this->insumo->custo_unitario_atual,
                    'categoria' => $this->insumo->categoria,
                    'fornecedor' => $this->insumo->fornecedor,
                ];
                $this->sendSuccess(['data' => $data]);
            }
            $this->sendError('Insumo não encontrado', 404);
        }

        if ($categoria = $this->getQuery('categoria')) {
            $stmt = $this->insumo->buscarPorCategoria($categoria);
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        $stmt = $this->insumo->listar();
        $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
    }

    protected function handlePost(): void {
        if (empty($this->input['nome']) || empty($this->input['unidade_compra'])) {
            $this->sendError('Dados obrigatórios não fornecidos', 400);
        }

        $this->insumo->nome = $this->input['nome'];
        $this->insumo->descricao = $this->input['descricao'] ?? '';
        $this->insumo->unidade_compra = $this->input['unidade_compra'];
        $this->insumo->fator_conversao = $this->input['fator_conversao'] ?? 1.0;
        $this->insumo->estoque_atual = $this->input['estoque_atual'] ?? 0;
        $this->insumo->estoque_minimo = $this->input['estoque_minimo'] ?? 0;
        $this->insumo->custo_unitario_atual = $this->input['custo_unitario_atual'] ?? 0;
        $this->insumo->categoria = $this->input['categoria'] ?? '';
        $this->insumo->fornecedor = $this->input['fornecedor'] ?? '';

        if ($this->insumo->unidade_compra === 'kg' || $this->insumo->unidade_compra === 'L') {
            $this->insumo->fator_conversao = 1000.0;
            $this->insumo->estoque_atual *= 1000;
            $this->insumo->estoque_minimo *= 1000;
        }

        if ($this->insumo->criar()) {
            // Se foram fornecidos dados de lote, criar automaticamente
            if (!empty($this->input['data_validade'])) {
                require_once __DIR__ . '/../models/ControleValidade.php';
                $controle = new ControleValidade($this->insumo->conn);

                // Obter o ID do insumo recém-criado
                $insumo_id = $this->insumo->conn->lastInsertId();

                $controle->insumo_id = $insumo_id;
                $controle->lote = $this->input['lote'] ?? '';
                $controle->quantidade_lote = $this->input['quantidade_lote'] ?? $this->insumo->estoque_atual;
                $controle->quantidade_atual = $controle->quantidade_lote;
                $controle->data_fabricacao = $this->input['data_fabricacao'] ?? null;
                $controle->data_validade = $this->input['data_validade'];
                $controle->observacoes = $this->input['observacoes'] ?? '';

                if ($controle->cadastrarLote()) {
                    $this->sendSuccess([], 'Insumo criado com sucesso e lote cadastrado no sistema de validade', 201);
                    return;
                } else {
                    // Insumo criado mas houve erro ao cadastrar lote
                    $this->sendSuccess([], 'Insumo criado com sucesso, mas houve erro ao cadastrar o lote de validade', 201);
                    return;
                }
            }

            $this->sendSuccess([], 'Insumo criado com sucesso', 201);
        }

        $this->sendError('Erro ao criar insumo', 500);
    }

    protected function handlePut(): void {
        if (empty($this->input['id'])) {
            $this->sendError('ID do insumo não fornecido', 400);
        }

        $this->insumo->id = $this->input['id'];
        $this->insumo->nome = $this->input['nome'] ?? '';
        $this->insumo->descricao = $this->input['descricao'] ?? '';
        $this->insumo->unidade_compra = $this->input['unidade_compra'] ?? '';
        $this->insumo->fator_conversao = $this->input['fator_conversao'] ?? 1.0;
        $this->insumo->estoque_atual = $this->input['estoque_atual'] ?? 0;
        $this->insumo->estoque_minimo = $this->input['estoque_minimo'] ?? 0;
        $this->insumo->custo_unitario_atual = $this->input['custo_unitario_atual'] ?? 0;
        $this->insumo->categoria = $this->input['categoria'] ?? '';
        $this->insumo->fornecedor = $this->input['fornecedor'] ?? '';

        if ($this->insumo->unidade_compra === 'kg' || $this->insumo->unidade_compra === 'L') {
            $this->insumo->fator_conversao = 1000.0;
            $this->insumo->estoque_atual *= 1000;
            $this->insumo->estoque_minimo *= 1000;
        }

        if ($this->insumo->atualizar()) {
            $this->sendSuccess([], 'Insumo atualizado com sucesso');
        }

        $this->sendError('Erro ao atualizar insumo', 500);
    }

    protected function handleDelete(): void {
        if (empty($this->input['id'])) {
            $this->sendError('ID do insumo não fornecido', 400);
        }

        $this->insumo->id = $this->input['id'];
        if ($this->insumo->excluir()) {
            $this->sendSuccess([], 'Insumo excluído com sucesso');
        }

        $this->sendError('Erro ao excluir insumo', 500);
    }
}
