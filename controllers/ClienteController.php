<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteController extends Controller {
    private $cliente;

    public function __construct($db) {
        parent::__construct($db);
        $this->cliente = new Cliente($db);
    }

    public function handle(): void {
        $this->dispatch([
            'GET' => 'handleGet',
            'POST' => 'handlePost',
            'PUT' => 'handlePut',
            'DELETE' => 'handleDelete'
        ]);
    }

    protected function handleGet(): void {
        if ($id = (int) $this->getQuery('id')) {
            if ($this->cliente->buscarPorId($id)) {
                $this->sendSuccess(['data' => [
                    'id' => $this->cliente->id,
                    'nome' => $this->cliente->nome,
                    'telefone' => $this->cliente->telefone,
                    'email' => $this->cliente->email,
                    'endereco' => $this->cliente->endereco
                ]]);
                return;
            }
            $this->sendError('Cliente não encontrado', 404);
        }

        $limite = (int) ($this->getQuery('limite') ?? 100);
        $stmt = $this->cliente->listar($limite);
        $rows = $this->fetchAll($stmt);
        $this->sendSuccess(['data' => $rows]);
    }

    protected function handlePost(): void {
        if (empty($this->input['nome'])) {
            $this->sendError('Nome do cliente obrigatório', 400);
        }
        $this->cliente->nome = $this->input['nome'];
        $this->cliente->telefone = $this->input['telefone'] ?? '';
        $this->cliente->email = $this->input['email'] ?? '';
        $this->cliente->endereco = $this->input['endereco'] ?? '';

        if ($this->cliente->criar()) {
            $this->sendSuccess(['data' => ['id' => $this->cliente->id]], 'Cliente criado', 201);
        }
        $this->sendError('Erro ao criar cliente', 500);
    }

    protected function handlePut(): void {
        if (empty($this->input['id'])) {
            $this->sendError('ID obrigatório', 400);
        }
        $this->cliente->id = $this->input['id'];
        $this->cliente->nome = $this->input['nome'] ?? '';
        $this->cliente->telefone = $this->input['telefone'] ?? '';
        $this->cliente->email = $this->input['email'] ?? '';
        $this->cliente->endereco = $this->input['endereco'] ?? '';

        if ($this->cliente->atualizar()) {
            $this->sendSuccess([], 'Cliente atualizado');
        }
        $this->sendError('Erro ao atualizar cliente', 500);
    }

    protected function handleDelete(): void {
        if (empty($this->input['id'])) {
            $this->sendError('ID obrigatório', 400);
        }
        $this->cliente->id = $this->input['id'];
        if ($this->cliente->excluir()) {
            $this->sendSuccess([], 'Cliente excluído');
        }
        $this->sendError('Erro ao excluir cliente', 500);
    }
}

?>
