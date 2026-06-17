<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Receita.php';

class ReceitaController extends Controller {
    private $receita;

    public function __construct($db) {
        parent::__construct($db);
        $this->receita = new Receita($db);
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
            if (!$this->receita->buscarPorId($id)) {
                $this->sendError('Receita não encontrada', 404);
            }

            $ingredientes_stmt = $this->receita->listarIngredientes();
            $ingredientes = $this->fetchAll($ingredientes_stmt);

            $custos_extras_stmt = $this->receita->listarCustosExtras();
            $custos_extras = $this->fetchAll($custos_extras_stmt);

            $this->sendSuccess(['data' => array_merge($this->formatReceita(), [
                'ingredientes' => $ingredientes,
                'custos_extras' => $custos_extras
            ])]);
        }

        if ($categoria = $this->getQuery('categoria')) {
            $stmt = $this->receita->buscarPorCategoria($categoria);
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        if ($this->getQuery('ingredientes')) {
            $receita_id = (int) ($this->getQuery('receita_id') ?? 0);
            if ($receita_id <= 0) {
                $this->sendError('ID da receita não fornecido', 400);
            }
            $this->receita->id = $receita_id;
            $stmt = $this->receita->listarIngredientes();
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        if ($this->getQuery('producoes')) {
            $receita_id = (int) ($this->getQuery('receita_id') ?? 0);
            $limite = (int) ($this->getQuery('limite') ?? 10);
            if ($receita_id <= 0) {
                $this->sendError('ID da receita não fornecido', 400);
            }
            $this->receita->id = $receita_id;
            $this->sendSuccess(['data' => $this->receita->listarProducoes($limite)]);
        }

        if ($this->getQuery('estatisticas')) {
            $receita_id = (int) ($this->getQuery('receita_id') ?? 0);
            if ($receita_id <= 0) {
                $this->sendError('ID da receita não fornecido', 400);
            }
            $this->receita->id = $receita_id;
            $this->sendSuccess(['data' => $this->receita->obterEstatisticas()]);
        }

        if ($this->getQuery('calcular_preco')) {
            $receita_id = (int) ($this->getQuery('receita_id') ?? 0);
            $margem_lucro = (float) ($this->getQuery('margem_lucro') ?? 0);
            if ($receita_id <= 0) {
                $this->sendError('ID da receita não fornecido', 400);
            }
            $this->receita->id = $receita_id;
            $this->sendSuccess(['data' => [
                'custo_total' => $this->receita->calcularCustoTotal(),
                'margem_lucro' => $margem_lucro,
                'preco_venda' => $this->receita->calcularPrecoVenda($margem_lucro),
            ]]);
        }

        if ($this->getQuery('calcular_margem')) {
            $receita_id = (int) ($this->getQuery('receita_id') ?? 0);
            $preco_venda = (float) ($this->getQuery('preco_venda') ?? 0);
            if ($receita_id <= 0) {
                $this->sendError('ID da receita não fornecido', 400);
            }
            $this->receita->id = $receita_id;
            $this->sendSuccess(['data' => [
                'custo_total' => $this->receita->calcularCustoTotal(),
                'preco_venda' => $preco_venda,
                'margem_lucro' => $this->receita->calcularMargemLucro($preco_venda),
            ]]);
        }

        $stmt = $this->receita->listar();
        $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
    }

    protected function handlePost(): void {
        if (isset($this->input['criar_receita'])) {
            if (empty($this->input['nome']) || empty($this->input['rendimento'])) {
                $this->sendError('Dados obrigatórios não fornecidos', 400);
            }

            $this->receita->nome = $this->input['nome'];
            $this->receita->descricao = $this->input['descricao'] ?? '';
            $this->receita->categoria = $this->input['categoria'] ?? '';
            $this->receita->rendimento = $this->input['rendimento'];
            $this->receita->unidade_rendimento = $this->input['unidade_rendimento'] ?? 'un';
            $this->receita->tempo_preparo = $this->input['tempo_preparo'] ?? 0;
            $this->receita->dificuldade = $this->input['dificuldade'] ?? 'medio';
            $this->receita->instrucoes = $this->input['instrucoes'] ?? '';
            $this->receita->custo_total = 0;
            $this->receita->preco_venda_sugerido = $this->input['preco_venda_sugerido'] ?? 0;
            $this->receita->margem_lucro = $this->input['margem_lucro'] ?? 0;

            if ($this->receita->criar()) {
                $this->sendSuccess(['data' => ['id' => $this->receita->id]], 'Receita criada com sucesso', 201);
            }

            $errorMessage = 'Erro ao criar receita';
            if (!empty($this->receita->lastError)) {
                $errorMessage .= ': ' . $this->receita->lastError;
            }
            $this->sendError($errorMessage, 500);
        }

        if (isset($this->input['adicionar_ingrediente'])) {
            $receita_id = (int) ($this->input['receita_id'] ?? 0);
            $insumo_id = (int) ($this->input['insumo_id'] ?? 0);
            $quantidade = (float) ($this->input['quantidade'] ?? 0);
            $unidade_uso = $this->input['unidade_uso'] ?? $this->input['unidade_medida'] ?? '';
            $observacoes = $this->input['observacoes'] ?? '';
            $ordem = (int) ($this->input['ordem'] ?? 0);

            if ($receita_id <= 0 || $insumo_id <= 0 || $quantidade <= 0) {
                $this->sendError('Dados obrigatórios não fornecidos', 400);
            }

            $this->receita->id = $receita_id;
            if ($this->receita->adicionarIngrediente($insumo_id, $quantidade, $unidade_uso, $observacoes, $ordem)) {
                $this->receita->atualizarCustoTotal();
                $this->sendSuccess([], 'Ingrediente adicionado com sucesso');
            }

            $this->sendError('Erro ao adicionar ingrediente', 500);
        }

        // Atualizar margem de lucro
        if ($this->hasBody('atualizar_margem')) {
            $receita_id = (int) ($this->getBody('receita_id') ?? 0);
            $margem = (float) ($this->getBody('margem_lucro') ?? 0);

            if ($receita_id <= 0) {
                $this->sendError('ID da receita não fornecido', 400);
            }

            $this->receita->id = $receita_id;
            if ($this->receita->atualizarMargem($margem)) {
                $this->receita->atualizarCustoTotal();
                $this->sendSuccess([], 'Margem atualizada com sucesso');
            }

            $this->sendError('Erro ao atualizar margem', 500);
        }

        // Registrar produção
        if ($this->hasBody('registrar_producao')) {
            $receita_id = (int) ($this->getBody('receita_id') ?? 0);
            $quantidade = (float) ($this->getBody('quantidade_produzida') ?? 0);
            $observacoes = $this->getBody('observacoes', '');

            if ($receita_id <= 0 || $quantidade <= 0) {
                $this->sendError('Dados inválidos para registrar produção', 400);
            }

            $receita = new Receita($this->db);
            $receita->id = $receita_id;
            if ($receita->registrarProducao($quantidade, $observacoes)) {
                $receita->atualizarCustoTotal();
                $responseData = [];

                if (!empty($receita->warnings)) {
                    $responseData['warning'] = implode(' ', $receita->warnings);
                }

                $this->sendSuccess($responseData, 'Produção registrada com sucesso');
            }

            $this->sendError('Erro ao registrar produção', 500);
        }

        $this->sendError('Dados inválidos', 400);
    }

    protected function handlePut(): void {
        if (empty($this->input['id'])) {
            $this->sendError('ID da receita não fornecido', 400);
        }

        $this->receita->id = $this->input['id'];
        $this->receita->nome = $this->input['nome'] ?? '';
        $this->receita->descricao = $this->input['descricao'] ?? '';
        $this->receita->categoria = $this->input['categoria'] ?? '';
        $this->receita->rendimento = $this->input['rendimento'] ?? 0;
        $this->receita->unidade_rendimento = $this->input['unidade_rendimento'] ?? 'un';
        $this->receita->tempo_preparo = $this->input['tempo_preparo'] ?? 0;
        $this->receita->dificuldade = $this->input['dificuldade'] ?? 'medio';
        $this->receita->instrucoes = $this->input['instrucoes'] ?? '';
        $this->receita->custo_total = $this->input['custo_total'] ?? 0;
        $this->receita->preco_venda_sugerido = $this->input['preco_venda_sugerido'] ?? 0;
        $this->receita->margem_lucro = $this->input['margem_lucro'] ?? 0;

        if ($this->receita->atualizar()) {
            $this->receita->atualizarCustoTotal();
            $this->sendSuccess([], 'Receita atualizada com sucesso');
        }

        $this->sendError('Erro ao atualizar receita', 500);
    }

    protected function handleDelete(): void {
        if (!empty($this->input['id'])) {
            $this->receita->id = $this->input['id'];
            if ($this->receita->excluir()) {
                $this->sendSuccess([], 'Receita excluída com sucesso');
            }
            $this->sendError('Erro ao excluir receita', 500);
        }

        if (isset($this->input['remover_ingrediente'])) {
            $receita_id = (int) ($this->input['receita_id'] ?? 0);
            $ingrediente_id = (int) ($this->input['ingrediente_id'] ?? 0);

            if ($receita_id <= 0 || $ingrediente_id <= 0) {
                $this->sendError('IDs não fornecidos', 400);
            }

            $this->receita->id = $receita_id;
            if ($this->receita->removerIngrediente($ingrediente_id)) {
                $this->receita->atualizarCustoTotal();
                $this->sendSuccess([], 'Ingrediente removido com sucesso');
            }
            $this->sendError('Erro ao remover ingrediente', 500);
        }

        $this->sendError('ID não fornecido', 400);
    }

    private function formatReceita(): array {
        return [
            'id' => $this->receita->id,
            'nome' => $this->receita->nome,
            'descricao' => $this->receita->descricao,
            'categoria' => $this->receita->categoria,
            'rendimento' => $this->receita->rendimento,
            'unidade_rendimento' => $this->receita->unidade_rendimento,
            'tempo_preparo' => $this->receita->tempo_preparo,
            'dificuldade' => $this->receita->dificuldade,
            'instrucoes' => $this->receita->instrucoes,
            'custo_total' => $this->receita->custo_total,
            'preco_venda_sugerido' => $this->receita->preco_venda_sugerido,
            'margem_lucro' => $this->receita->margem_lucro,
        ];
    }
}
