<?php
/**
 * Modelo para gerenciar Receitas
 * Sistema de Gestão da Doceria
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CustoExtra.php';

class Receita {
    private $conn;
    private $table_name = "receitas";
    private $ingredientes_table = "receita_ingredientes";
    private $producoes_table = "producoes";

    public $id;
    public $nome;
    public $descricao;
    public $categoria;
    public $rendimento;
    public $unidade_rendimento;
    public $tempo_preparo;
    public $dificuldade;
    public $instrucoes;
    public $custo_total;
    public $preco_venda_sugerido;
    public $margem_lucro;
    public $ativo;
    public $warnings = [];
    public $lastError = '';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar nova receita
     */
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nome, descricao, categoria, rendimento, unidade_rendimento, 
                   tempo_preparo, dificuldade, instrucoes, custo_total, 
                   preco_venda_sugerido, margem_lucro) 
                  VALUES (:nome, :descricao, :categoria, :rendimento, :unidade_rendimento, 
                          :tempo_preparo, :dificuldade, :instrucoes, :custo_total, 
                          :preco_venda_sugerido, :margem_lucro)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->categoria = htmlspecialchars(strip_tags($this->categoria));
        $this->instrucoes = htmlspecialchars(strip_tags($this->instrucoes));

        // Bind dos parâmetros
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':rendimento', $this->rendimento);
        $stmt->bindParam(':unidade_rendimento', $this->unidade_rendimento);
        $stmt->bindParam(':tempo_preparo', $this->tempo_preparo);
        $stmt->bindParam(':dificuldade', $this->dificuldade);
        $stmt->bindParam(':instrucoes', $this->instrucoes);
        $stmt->bindParam(':custo_total', $this->custo_total);
        $stmt->bindParam(':preco_venda_sugerido', $this->preco_venda_sugerido);
        $stmt->bindParam(':margem_lucro', $this->margem_lucro);

        try {
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $exception) {
            $this->lastError = $exception->getMessage();
            error_log('Erro ao criar receita: ' . $exception->getMessage());
        }

        return false;
    }

    /**
     * Listar todas as receitas
     */
    public function listar() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ativo = 1 ORDER BY nome ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Buscar receita por ID
     */
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND ativo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->descricao = $row['descricao'];
            $this->categoria = $row['categoria'];
            $this->rendimento = $row['rendimento'];
            $this->unidade_rendimento = $row['unidade_rendimento'];
            $this->tempo_preparo = $row['tempo_preparo'];
            $this->dificuldade = $row['dificuldade'];
            $this->instrucoes = $row['instrucoes'];
            $this->custo_total = $row['custo_total'];
            $this->preco_venda_sugerido = $row['preco_venda_sugerido'];
            $this->margem_lucro = $row['margem_lucro'];
            $this->ativo = $row['ativo'];
            return true;
        }
        return false;
    }

    /**
     * Atualizar receita
     */
    public function atualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome = :nome, descricao = :descricao, categoria = :categoria,
                      rendimento = :rendimento, unidade_rendimento = :unidade_rendimento,
                      tempo_preparo = :tempo_preparo, dificuldade = :dificuldade,
                      instrucoes = :instrucoes, custo_total = :custo_total,
                      preco_venda_sugerido = :preco_venda_sugerido, margem_lucro = :margem_lucro
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->categoria = htmlspecialchars(strip_tags($this->categoria));
        $this->instrucoes = htmlspecialchars(strip_tags($this->instrucoes));

        // Bind dos parâmetros
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':rendimento', $this->rendimento);
        $stmt->bindParam(':unidade_rendimento', $this->unidade_rendimento);
        $stmt->bindParam(':tempo_preparo', $this->tempo_preparo);
        $stmt->bindParam(':dificuldade', $this->dificuldade);
        $stmt->bindParam(':instrucoes', $this->instrucoes);
        $stmt->bindParam(':custo_total', $this->custo_total);
        $stmt->bindParam(':preco_venda_sugerido', $this->preco_venda_sugerido);
        $stmt->bindParam(':margem_lucro', $this->margem_lucro);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Atualizar margem de lucro da receita
     */
    public function atualizarMargem(float $margem) {
        $query = "UPDATE " . $this->table_name . " SET margem_lucro = :margem WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':margem', $margem);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Recalcular custo total para uma receita específica
     */
    public function atualizarCustoTotalPorReceita(int $receita_id) {
        $this->id = $receita_id;
        return $this->atualizarCustoTotal();
    }

    /**
     * Excluir receita (soft delete)
     */
    public function excluir() {
        $query = "UPDATE " . $this->table_name . " SET ativo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Adicionar ingrediente à receita
     */
    public function adicionarIngrediente($insumo_id, $quantidade, $unidade_uso, $observacoes = '', $ordem = 0) {
        $query = "INSERT INTO " . $this->ingredientes_table . " 
                  (receita_id, insumo_id, quantidade, unidade_uso, observacoes, ordem) 
                  VALUES (:receita_id, :insumo_id, :quantidade, :unidade_uso, :observacoes, :ordem)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':receita_id', $this->id);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':unidade_uso', $unidade_uso);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':ordem', $ordem);

        return $stmt->execute();
    }

    /**
     * Listar ingredientes da receita
     */
    public function listarIngredientes() {
        $query = "SELECT ri.*, i.nome as insumo_nome, i.custo_unitario_atual, i.unidade_compra as insumo_unidade_compra,
                         i.fator_conversao
                  FROM " . $this->ingredientes_table . " ri
                  INNER JOIN insumos i ON ri.insumo_id = i.id
                  WHERE ri.receita_id = :receita_id
                  ORDER BY ri.ordem ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':receita_id', $this->id);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Listar custos extras da receita
     */
    public function listarCustosExtras() {
        $custoExtra = new CustoExtra($this->conn);
        return $custoExtra->listarPorReceita($this->id);
    }

    /**
     * Remover ingrediente da receita
     */
    public function removerIngrediente($ingrediente_id) {
        $query = "DELETE FROM " . $this->ingredientes_table . " WHERE id = :id AND receita_id = :receita_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $ingrediente_id);
        $stmt->bindParam(':receita_id', $this->id);

        return $stmt->execute();
    }

    /**
     * Calcular custo total da receita com conversão de unidades
     */
    public function calcularCustoTotal() {
        $query = "SELECT ri.quantidade, ri.unidade_uso, i.custo_unitario_atual, i.unidade_compra, i.fator_conversao
                  FROM " . $this->ingredientes_table . " ri
                  INNER JOIN insumos i ON ri.insumo_id = i.id
                  WHERE ri.receita_id = :receita_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':receita_id', $this->id);
        $stmt->execute();
        
        $custo_total = 0;
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Converter quantidade da unidade de uso para unidade de compra
            $quantidade_convertida = $this->converterUnidade(
                $row['quantidade'], 
                $row['unidade_uso'], 
                $row['unidade_compra'], 
                $row['fator_conversao']
            );
            
            $custo_total += $quantidade_convertida * $row['custo_unitario_atual'];
        }

        $custo_total += $this->obterCustoExtraTotal();
        
        return $custo_total;
    }

    private function obterCustoExtraTotal() {
        $custoExtra = new CustoExtra($this->conn);
        return (float) $custoExtra->somaPorReceita($this->id);
    }

    /**
     * Converter unidade de uso para unidade de compra
     * Normaliza as quantidades para permitir comparação e cálculo correto de custos
     */
    private function converterUnidade($quantidade, $unidade_origem, $unidade_destino, $fator_conversao) {
        // Se as unidades são iguais, não precisa converter
        if($unidade_origem == $unidade_destino) {
            return $quantidade;
        }

        // Conversões básicas padrão
        $conversoes = [
            'kg' => ['g' => 1000, 'mg' => 1000000],
            'g' => ['kg' => 0.001, 'mg' => 1000],
            'mg' => ['kg' => 0.000001, 'g' => 0.001],
            'L' => ['ml' => 1000, 'ul' => 1000000],
            'ml' => ['L' => 0.001, 'ul' => 1000],
            'ul' => ['L' => 0.000001, 'ml' => 0.001]
        ];

        // Tentar conversão padrão primeiro
        if(isset($conversoes[$unidade_origem][$unidade_destino])) {
            return $quantidade * $conversoes[$unidade_origem][$unidade_destino];
        }

        // Se houver fator de conversão customizado, usar ele
        if($fator_conversao > 0 && $fator_conversao != 1.0) {
            return $quantidade * $fator_conversao;
        }

        // Se não houver conversão conhecida, retornar quantidade original
        return $quantidade;
    }

    /**
     * Calcular preço de venda baseado na margem de lucro (como markup)
     * Fórmula: preco_venda = custo * (1 + margem%)
     * Ex: custo=100, margem=30% => preco=130
     */
    public function calcularPrecoVenda($margem_lucro_percentual) {
        $custo_total = $this->calcularCustoTotal();
        
        if($custo_total <= 0 || $margem_lucro_percentual < 0) {
            return 0;
        }
        
        $preco_venda = $custo_total * (1 + ($margem_lucro_percentual / 100));
        
        return $preco_venda;
    }

    /**
     * Calcular margem de lucro baseada no preço de venda (como markup)
     * Retorna a margem como percentual do custo
     * Fórmula: margem = ((preco - custo) / custo) * 100
     */
    public function calcularMargemLucro($preco_venda) {
        $custo_total = $this->calcularCustoTotal();
        
        if($custo_total <= 0) {
            return 0;
        }
        
        if($preco_venda <= $custo_total) {
            return 0;
        }
        
        $margem_percentual = (($preco_venda - $custo_total) / $custo_total) * 100;
        
        return $margem_percentual;
    }

    /**
     * Atualizar custo total da receita e preço de venda
     */
    public function atualizarCustoTotal() {
        $custo_total = $this->calcularCustoTotal();
        
        // Buscar margem de lucro atual da receita
        $query_margem = "SELECT margem_lucro FROM " . $this->table_name . " WHERE id = :id";
        $stmt_margem = $this->conn->prepare($query_margem);
        $stmt_margem->bindParam(':id', $this->id);
        $stmt_margem->execute();
        $margem_result = $stmt_margem->fetch(PDO::FETCH_ASSOC);
        $margem_lucro = $margem_result['margem_lucro'] ?? 0;
        
        // Calcular preço de venda baseado na margem
        $preco_venda = $this->calcularPrecoVenda($margem_lucro);
        
        $query = "UPDATE " . $this->table_name . " 
                  SET custo_total = :custo_total, preco_venda_sugerido = :preco_venda 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':custo_total', $custo_total);
        $stmt->bindParam(':preco_venda', $preco_venda);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    /**
     * Registrar produção da receita (usar modelo Producao para FIFO)
     */
    public function registrarProducao($quantidade_produzida, $observacoes = '') {
        require_once __DIR__ . '/Producao.php';
        $producao = new Producao($this->conn);
        $producao->receita_id = $this->id;
        $producao->quantidade_produzida = $quantidade_produzida;
        $producao->observacoes = $observacoes;
        
        $registrado = $producao->registrar();
        $this->warnings = $producao->warnings;

        return $registrado;
    }


    /**
     * Listar produções da receita
     */
    public function listarProducoes($limite = 10) {
        $query = "SELECT * FROM " . $this->producoes_table . " 
                  WHERE receita_id = :receita_id 
                  ORDER BY data_producao DESC 
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':receita_id', $this->id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar receitas por categoria
     */
    public function buscarPorCategoria($categoria) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE categoria = :categoria AND ativo = 1 
                  ORDER BY nome ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Obter estatísticas da receita
     */
    public function obterEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total_producoes,
                    SUM(quantidade_produzida) as total_produzido,
                    SUM(custo_total) as custo_total_producoes,
                    AVG(custo_total) as custo_medio_producao
                  FROM " . $this->producoes_table . " 
                  WHERE receita_id = :receita_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':receita_id', $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
