<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/CustoExtra.php';
require_once __DIR__ . '/../models/Receita.php';

class CustoExtraController extends Controller {
    private $custo;

    public function __construct($db) {
        parent::__construct($db);
        $this->custo = new CustoExtra($db);
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
        $receita_id = (int) ($this->getQuery('receita_id') ?? 0);
        if ($receita_id <= 0) {
            $this->sendError('receita_id não fornecido', 400);
        }

        $stmt = $this->custo->listarPorReceita($receita_id);
        $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
    }

    protected function handlePost(): void {
        $receita_id = (int) ($this->getBody('receita_id') ?? 0);
        $descricao = $this->getBody('descricao', '');
        $valor = (float) ($this->getBody('valor') ?? 0);

        if ($receita_id <= 0 || empty($descricao) || $valor <= 0) {
            $this->sendError('Dados inválidos', 400);
        }

        $this->custo->id_receita = $receita_id;
        $this->custo->descricao = $descricao;
        $this->custo->valor = $valor;

        if ($this->custo->criar()) {
            $receita = new Receita($this->db);
            $receita->atualizarCustoTotalPorReceita($receita_id);
            $this->sendSuccess(['data' => ['id' => $this->custo->id]], 'Custo extra adicionado', 201);
        }

        $this->sendError('Erro ao adicionar custo extra', 500);
    }

    protected function handlePut(): void {
        $id = (int) ($this->getBody('id') ?? 0);
        $receita_id = (int) ($this->getBody('receita_id') ?? 0);
        $descricao = $this->getBody('descricao', '');
        $valor = (float) ($this->getBody('valor') ?? 0);

        if ($id <= 0 || empty($descricao) || $valor <= 0) {
            $this->sendError('Dados inválidos para atualizar custo extra', 400);
        }

        $this->custo->id = $id;
        $this->custo->descricao = $descricao;
        $this->custo->valor = $valor;

        if ($this->custo->atualizar()) {
            if ($receita_id > 0) {
                $receita = new Receita($this->db);
                $receita->atualizarCustoTotalPorReceita($receita_id);
            }
            $this->sendSuccess([], 'Custo extra atualizado');
        }

        $this->sendError('Erro ao atualizar custo extra', 500);
    }

    protected function handleDelete(): void {
        $id = (int) ($this->getBody('id') ?? 0);
        $receita_id = (int) ($this->getBody('receita_id') ?? 0);

        if ($id <= 0) {
            $this->sendError('ID não fornecido', 400);
        }

        if ($this->custo->excluir($id)) {
            if ($receita_id > 0) {
                $receita = new Receita($this->db);
                $receita->atualizarCustoTotalPorReceita($receita_id);
            }
            $this->sendSuccess([], 'Custo extra removido');
        }

        $this->sendError('Erro ao remover custo extra', 500);
    }
}
