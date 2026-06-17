<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Encomenda.php';

class EncomendaController extends Controller {
    private $encomenda;
    private $clienteModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->encomenda = new Encomenda($db);
        require_once __DIR__ . '/ClienteController.php';
        require_once __DIR__ . '/../models/Cliente.php';
        $this->clienteModel = new Cliente($db);
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
            if ($this->encomenda->buscarPorId($id)) {
                $this->sendSuccess(['data' => [
                    'id' => $this->encomenda->id,
                    'cliente_id' => $this->encomenda->cliente_id,
                    'cliente_nome' => $this->encomenda->cliente_nome,
                    'cliente_telefone' => $this->encomenda->cliente_telefone,
                    'cliente_email' => $this->encomenda->cliente_email,
                    'receita_id' => $this->encomenda->receita_id,
                    'quantidade' => $this->encomenda->quantidade,
                    'preco_unitario' => $this->encomenda->preco_unitario,
                    'preco_total' => $this->encomenda->preco_total,
                    'data_entrega' => $this->encomenda->data_entrega,
                    'status' => $this->encomenda->status,
                    'pago' => $this->encomenda->pago ?? 0,
                    'observacoes' => $this->encomenda->observacoes,
                    'items' => $this->encomenda->itens,
                ]]);
            }
            $this->sendError('Encomenda não encontrada', 404);
        }

        if ($status = $this->getQuery('status')) {
            $stmt = $this->encomenda->listarPorStatus($status);
            $this->sendSuccess(['data' => $this->carregarEncomendasComItens($stmt)]);
        }

        if ($this->getQuery('pendentes_hoje')) {
            $stmt = $this->encomenda->listarPendentesHoje();
            $this->sendSuccess(['data' => $this->carregarEncomendasComItens($stmt)]);
        }

        if ($this->getQuery('estatisticas')) {
            $this->sendSuccess(['data' => $this->encomenda->obterEstatisticas()]);
        }

        $limite = (int) ($this->getQuery('limite') ?? 50);
        $stmt = $this->encomenda->listar($limite);
        $this->sendSuccess(['data' => $this->carregarEncomendasComItens($stmt)]);
    }

    private function carregarEncomendasComItens(PDOStatement $stmt): array {
        $encomendas = $this->fetchAll($stmt);
        foreach ($encomendas as &$encomenda) {
            $encomenda['items'] = $this->encomenda->buscarItens($encomenda['id']);
        }
        return $encomendas;
    }

    protected function handlePost(): void {
        // aceitar cliente cadastrado (cliente_id) ou cliente_nome manualmente
        $hasClienteId = !empty($this->input['cliente_id']);
        $hasClienteNome = !empty($this->input['cliente_nome']);
        $hasItems = !empty($this->input['items']) && is_array($this->input['items']);
        $hasReceita = !empty($this->input['receita_id']) || $hasItems;

        if ((!$hasClienteId && !$hasClienteNome) || !$hasReceita || empty($this->input['data_entrega'])) {
            $this->sendError('Dados obrigatórios não fornecidos', 400);
        }

        if ($hasClienteId) {
            $cliente_id = (int) $this->input['cliente_id'];
            $this->encomenda->cliente_id = $cliente_id;
            if ($this->clienteModel->buscarPorId($cliente_id)) {
                $this->encomenda->cliente_nome = $this->clienteModel->nome;
                $this->encomenda->cliente_telefone = $this->clienteModel->telefone;
                $this->encomenda->cliente_email = $this->clienteModel->email;
            } else {
                // fallback para nome manual se cliente_id inválido
                $this->encomenda->cliente_nome = $this->input['cliente_nome'] ?? '';
                $this->encomenda->cliente_telefone = $this->input['cliente_telefone'] ?? '';
                $this->encomenda->cliente_email = $this->input['cliente_email'] ?? '';
            }
        } else {
            $this->encomenda->cliente_id = null;
            $this->encomenda->cliente_nome = $this->input['cliente_nome'];
            $this->encomenda->cliente_telefone = $this->input['cliente_telefone'] ?? '';
            $this->encomenda->cliente_email = $this->input['cliente_email'] ?? '';
        }
        $this->encomenda->receita_id = $this->input['receita_id'];
        $this->encomenda->quantidade = $this->input['quantidade'] ?? 1;
        $this->encomenda->preco_unitario = $this->input['preco_unitario'] ?? 0;
        $this->encomenda->data_entrega = $this->input['data_entrega'];
        $this->encomenda->status = $this->input['status'] ?? 'pendente';
        $this->encomenda->observacoes = $this->input['observacoes'] ?? '';
        if (!empty($this->input['items']) && is_array($this->input['items'])) {
            $this->encomenda->itens = $this->input['items'];
            $firstItem = $this->input['items'][0];
            $this->encomenda->receita_id = $firstItem['receita_id'] ?? $this->encomenda->receita_id;
            $this->encomenda->quantidade = $firstItem['quantidade_vendida'] ?? $this->encomenda->quantidade;
            $this->encomenda->preco_unitario = $firstItem['preco_unitario'] ?? $this->encomenda->preco_unitario;
        }

        // verificar disponibilidade de insumos para a receita/quantidade informada
        $missing = $this->encomenda->verificarDisponibilidade($this->encomenda->receita_id, $this->encomenda->quantidade);

        if ($this->encomenda->criar()) {
            $responseData = ['id' => $this->encomenda->id];
            if (!empty($missing)) {
                $responseData['missing_insumos'] = $missing;
            }
            $this->sendSuccess(['data' => $responseData], 'Encomenda criada com sucesso', 201);
        }

        $this->sendError('Erro ao criar encomenda', 500);
    }

    protected function handlePut(): void {
        if (empty($this->input['id'])) {
            $this->sendError('ID da encomenda não fornecido', 400);
        }
        $id = $this->input['id'];

        // If only updating status
        if (!empty($this->input['atualizar_status'])) {
            $novo = $this->input['status'] ?? null;
            if ($novo === null) {
                $this->sendError('Novo status não fornecido', 400);
            }
            $this->encomenda->id = $id;
            if ($this->encomenda->atualizarStatus($novo)) {
                $this->sendSuccess([], 'Status atualizado com sucesso');
            }
            $this->sendError('Erro ao atualizar status', 500);
        }

        // For other updates, load existing record first to avoid FK/empty overwrite issues
        if (!$this->encomenda->buscarPorId($id)) {
            $this->sendError('Encomenda não encontrada', 404);
        }

        // Update only provided fields
        if (isset($this->input['cliente_id'])) {
            $this->encomenda->cliente_id = (int) $this->input['cliente_id'];
            if ($this->clienteModel->buscarPorId($this->encomenda->cliente_id)) {
                $this->encomenda->cliente_nome = $this->clienteModel->nome;
                $this->encomenda->cliente_telefone = $this->clienteModel->telefone;
                $this->encomenda->cliente_email = $this->clienteModel->email;
            }
        }
        if (isset($this->input['cliente_nome'])) $this->encomenda->cliente_nome = $this->input['cliente_nome'];
        if (isset($this->input['cliente_telefone'])) $this->encomenda->cliente_telefone = $this->input['cliente_telefone'];
        if (isset($this->input['cliente_email'])) $this->encomenda->cliente_email = $this->input['cliente_email'];
        if (isset($this->input['receita_id'])) $this->encomenda->receita_id = $this->input['receita_id'];
        if (isset($this->input['quantidade'])) $this->encomenda->quantidade = $this->input['quantidade'];
        if (isset($this->input['preco_unitario'])) $this->encomenda->preco_unitario = $this->input['preco_unitario'];
        if (isset($this->input['data_entrega'])) $this->encomenda->data_entrega = $this->input['data_entrega'];
        if (isset($this->input['status'])) $this->encomenda->status = $this->input['status'];
        if (isset($this->input['observacoes'])) $this->encomenda->observacoes = $this->input['observacoes'];
        if (isset($this->input['pago'])) $this->encomenda->pago = (int)$this->input['pago'];
        if (!empty($this->input['items']) && is_array($this->input['items'])) {
            $this->encomenda->itens = $this->input['items'];
            $firstItem = $this->input['items'][0];
            $this->encomenda->receita_id = $firstItem['receita_id'] ?? $this->encomenda->receita_id;
            $this->encomenda->quantidade = $firstItem['quantidade_vendida'] ?? $this->encomenda->quantidade;
            $this->encomenda->preco_unitario = $firstItem['preco_unitario'] ?? $this->encomenda->preco_unitario;
        }

        if ($this->encomenda->atualizar()) {
            $this->sendSuccess([], 'Encomenda atualizada com sucesso');
        }

        $this->sendError('Erro ao atualizar encomenda', 500);
    }

    protected function handleDelete(): void {
        if (empty($this->input['id'])) {
            $this->sendError('ID da encomenda não fornecido', 400);
        }

        $this->encomenda->id = $this->input['id'];
        if ($this->encomenda->excluir()) {
            $this->sendSuccess([], 'Encomenda excluída com sucesso');
        }

        $this->sendError('Erro ao excluir encomenda', 500);
    }
}
