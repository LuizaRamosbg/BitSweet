<?php
/**
 * Modelo para gerenciar Insumos
 * Sistema de Gestão da Doceria
 */

require_once __DIR__ . '/../config/database.php';

class Insumo {
    private $conn;
    private $table_name = "insumos";

    public $id;
    public $nome;
    public $descricao;
    public $unidade_compra;
    public $fator_conversao;
    public $estoque_atual;
    public $estoque_minimo;
    public $custo_unitario_atual;
    public $categoria;
    public $fornecedor;
    public $ativo;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar novo insumo
     */
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nome, descricao, unidade_compra, fator_conversao, estoque_atual, estoque_minimo, 
                   custo_unitario_atual, categoria, fornecedor) 
                  VALUES (:nome, :descricao, :unidade_compra, :fator_conversao, :estoque_atual, :estoque_minimo, 
                          :custo_unitario_atual, :categoria, :fornecedor)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->unidade_compra = htmlspecialchars(strip_tags($this->unidade_compra));
        $this->categoria = htmlspecialchars(strip_tags($this->categoria));
        $this->fornecedor = htmlspecialchars(strip_tags($this->fornecedor));

        // Se fator_conversao não foi definido, usar padrão 1
        if(!isset($this->fator_conversao) || $this->fator_conversao <= 0) {
            $this->fator_conversao = 1.0;
        }

        // Bind dos parâmetros
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':unidade_compra', $this->unidade_compra);
        $stmt->bindParam(':fator_conversao', $this->fator_conversao);
        $stmt->bindParam(':estoque_atual', $this->estoque_atual);
        $stmt->bindParam(':estoque_minimo', $this->estoque_minimo);
        $stmt->bindParam(':custo_unitario_atual', $this->custo_unitario_atual);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':fornecedor', $this->fornecedor);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Listar todos os insumos
     */
    public function listar() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ativo = 1 ORDER BY nome ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Buscar insumo por ID
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
            $this->unidade_compra = $row['unidade_compra'];
            $this->fator_conversao = $row['fator_conversao'];
            $this->estoque_atual = $row['estoque_atual'];
            $this->estoque_minimo = $row['estoque_minimo'];
            $this->custo_unitario_atual = $row['custo_unitario_atual'];
            $this->categoria = $row['categoria'];
            $this->fornecedor = $row['fornecedor'];
            $this->ativo = $row['ativo'];
            return true;
        }
        return false;
    }

    /**
     * Atualizar insumo
     */
    public function atualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome = :nome, descricao = :descricao, unidade_compra = :unidade_compra,
                      fator_conversao = :fator_conversao, estoque_atual = :estoque_atual, 
                      estoque_minimo = :estoque_minimo, custo_unitario_atual = :custo_unitario_atual, 
                      categoria = :categoria, fornecedor = :fornecedor
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->unidade_compra = htmlspecialchars(strip_tags($this->unidade_compra));
        $this->categoria = htmlspecialchars(strip_tags($this->categoria));
        $this->fornecedor = htmlspecialchars(strip_tags($this->fornecedor));

        // Bind dos parâmetros
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':unidade_compra', $this->unidade_compra);
        $stmt->bindParam(':fator_conversao', $this->fator_conversao);
        $stmt->bindParam(':estoque_atual', $this->estoque_atual);
        $stmt->bindParam(':estoque_minimo', $this->estoque_minimo);
        $stmt->bindParam(':custo_unitario_atual', $this->custo_unitario_atual);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':fornecedor', $this->fornecedor);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Excluir insumo (soft delete)
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
     * Buscar insumos por categoria
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
     * Atualizar estoque após compra
     */
    public function atualizarEstoque($quantidade, $custo_unitario) {
        $query = "UPDATE " . $this->table_name . " 
                  SET estoque_atual = estoque_atual + :quantidade,
                      custo_unitario_atual = :custo_unitario
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':custo_unitario', $custo_unitario);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Calcular custo unitário
     */
    public function calcularCustoUnitario($preco_total, $quantidade) {
        if($quantidade <= 0) {
            return 0;
        }
        return $preco_total / $quantidade;
    }

    /**
     * Atualizar saldo em estoque
     */
    public function atualizarSaldoEstoque($quantidade, $tipo = 'entrada') {
        $query = "UPDATE " . $this->table_name . " 
                  SET estoque_atual = estoque_atual " . ($tipo == 'entrada' ? '+' : '-') . " :quantidade
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            // Atualizar o atributo local
            $this->buscarPorId($this->id);
            return true;
        }
        return false;
    }

    /**
     * Verificar alerta estoque mínimo
     */
    public function verificarAlertaEstoqueMinimo() {
        $query = "SELECT estoque_atual, estoque_minimo FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            return $row['estoque_atual'] <= $row['estoque_minimo'];
        }
        return false;
    }

    /**
     * Adicionar fornecedor
     */
    public function adicionarFornecedor($fornecedor) {
        $this->fornecedor = htmlspecialchars(strip_tags($fornecedor));
        $query = "UPDATE " . $this->table_name . " SET fornecedor = :fornecedor WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fornecedor', $this->fornecedor);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    /**
     * Adicionar data validade (cria registro no controle de validade)
     */
    public function adicionarDataValidade($lote, $quantidade_lote, $data_fabricacao, $data_validade, $observacoes = '') {
        require_once __DIR__ . '/ControleValidade.php';
        $controle = new ControleValidade($this->conn);
        $controle->insumo_id = $this->id;
        $controle->lote = $lote;
        $controle->quantidade_lote = $quantidade_lote;
        $controle->quantidade_atual = $quantidade_lote;
        $controle->data_fabricacao = $data_fabricacao;
        $controle->data_validade = $data_validade;
        $controle->observacoes = $observacoes;
        
        return $controle->cadastrarLote();
    }

    /**
     * Determinar estoque mínimo
     */
    public function determinarEstoqueMinimo($dias_consumo_medio = 30) {
        // Calcular consumo médio baseado no histórico
        $query = "SELECT AVG(ABS(quantidade)) as consumo_medio 
                  FROM historico_estoque 
                  WHERE insumo_id = :insumo_id 
                  AND tipo_movimentacao = 'saida' 
                  AND data_movimentacao >= DATE_SUB(NOW(), INTERVAL :dias DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $this->id);
        $stmt->bindParam(':dias', $dias_consumo_medio, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $consumo_medio = $result['consumo_medio'] ?? 0;
        
        // Estoque mínimo = consumo médio diário * dias de segurança
        $estoque_minimo = $consumo_medio * ($dias_consumo_medio / 30);
        
        $this->estoque_minimo = max(0, $estoque_minimo);
        return $this->estoque_minimo;
    }
}
?>
