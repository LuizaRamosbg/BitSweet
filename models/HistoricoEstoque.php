<?php
/**
 * Modelo para gerenciar Histórico de Estoque
 * Sistema de Gestão da Doceria
 */

require_once __DIR__ . '/../config/database.php';

class HistoricoEstoque {
    private $conn;
    private $table_name = "historico_estoque";

    public $id;
    public $insumo_id;
    public $tipo_movimentacao;
    public $quantidade;
    public $custo_unitario;
    public $motivo;
    public $referencia_id;
    public $data_movimentacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Registrar movimentação no histórico
     */
    public function registrar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (insumo_id, tipo_movimentacao, quantidade, custo_unitario, motivo, referencia_id) 
                  VALUES (:insumo_id, :tipo_movimentacao, :quantidade, :custo_unitario, :motivo, :referencia_id)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->motivo = htmlspecialchars(strip_tags($this->motivo));

        // Bind dos parâmetros
        $stmt->bindParam(':insumo_id', $this->insumo_id);
        $stmt->bindParam(':tipo_movimentacao', $this->tipo_movimentacao);
        $stmt->bindParam(':quantidade', $this->quantidade);
        $stmt->bindParam(':custo_unitario', $this->custo_unitario);
        $stmt->bindParam(':motivo', $this->motivo);
        $stmt->bindParam(':referencia_id', $this->referencia_id);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Listar histórico por insumo
     */
    public function listarPorInsumo($insumo_id, $limite = 50) {
        $query = "SELECT h.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " h
                  INNER JOIN insumos i ON h.insumo_id = i.id
                  WHERE h.insumo_id = :insumo_id
                  ORDER BY h.data_movimentacao DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Listar histórico por tipo de movimentação
     */
    public function listarPorTipo($tipo_movimentacao, $limite = 50) {
        $query = "SELECT h.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " h
                  INNER JOIN insumos i ON h.insumo_id = i.id
                  WHERE h.tipo_movimentacao = :tipo_movimentacao
                  ORDER BY h.data_movimentacao DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipo_movimentacao', $tipo_movimentacao);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Listar todo o histórico
     */
    public function listar($limite = 100) {
        $query = "SELECT h.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " h
                  INNER JOIN insumos i ON h.insumo_id = i.id
                  ORDER BY h.data_movimentacao DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Buscar por ID
     */
    public function buscarPorId($id) {
        $query = "SELECT h.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " h
                  INNER JOIN insumos i ON h.insumo_id = i.id
                  WHERE h.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->insumo_id = $row['insumo_id'];
            $this->tipo_movimentacao = $row['tipo_movimentacao'];
            $this->quantidade = $row['quantidade'];
            $this->custo_unitario = $row['custo_unitario'];
            $this->motivo = $row['motivo'];
            $this->referencia_id = $row['referencia_id'];
            $this->data_movimentacao = $row['data_movimentacao'];
            return true;
        }
        return false;
    }

    /**
     * Obter estatísticas de movimentações
     */
    public function obterEstatisticas($insumo_id = null) {
        $where = $insumo_id ? "WHERE h.insumo_id = :insumo_id" : "";
        $query = "SELECT 
                    COUNT(*) as total_movimentacoes,
                    SUM(CASE WHEN tipo_movimentacao = 'entrada' THEN quantidade ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN tipo_movimentacao = 'saida' THEN quantidade ELSE 0 END) as total_saidas,
                    SUM(CASE WHEN tipo_movimentacao = 'ajuste' THEN quantidade ELSE 0 END) as total_ajustes,
                    SUM(CASE WHEN tipo_movimentacao = 'desperdicio' THEN quantidade ELSE 0 END) as total_desperdicios
                  FROM " . $this->table_name . " h " . $where;
        
        $stmt = $this->conn->prepare($query);
        if($insumo_id) {
            $stmt->bindParam(':insumo_id', $insumo_id);
        }
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Listar movimentações por período
     */
    public function listarPorPeriodo($data_inicio, $data_fim, $insumo_id = null) {
        $where = "WHERE DATE(h.data_movimentacao) BETWEEN :data_inicio AND :data_fim";
        if($insumo_id) {
            $where .= " AND h.insumo_id = :insumo_id";
        }
        
        $query = "SELECT h.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " h
                  INNER JOIN insumos i ON h.insumo_id = i.id
                  " . $where . "
                  ORDER BY h.data_movimentacao DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        if($insumo_id) {
            $stmt->bindParam(':insumo_id', $insumo_id);
        }
        $stmt->execute();
        
        return $stmt;
    }
}
?>

