<?php
/**
 * Modelo para gerenciar Desperdícios
 * Sistema de Gestão da Doceria
 */

require_once __DIR__ . '/../config/database.php';

class Desperdicio {
    private $conn;
    private $table_name = "desperdicios";
    private $historico_table = "historico_estoque";
    private $insumos_table = "insumos";

    public $id;
    public $insumo_id;
    public $quantidade;
    public $motivo;
    public $descricao;
    public $data_registro;
    public $registrado_por;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Registrar desperdício
     */
    public function registrar() {
        // Iniciar transação
        $this->conn->beginTransaction();
        
        try {
            // Inserir registro de desperdício
            $query = "INSERT INTO " . $this->table_name . " 
                      (insumo_id, quantidade, motivo, descricao, registrado_por) 
                      VALUES (:insumo_id, :quantidade, :motivo, :descricao, :registrado_por)";

            $stmt = $this->conn->prepare($query);

            // Sanitizar dados
            $this->descricao = htmlspecialchars(strip_tags($this->descricao));
            $this->registrado_por = htmlspecialchars(strip_tags($this->registrado_por));

            // Bind dos parâmetros
            $stmt->bindParam(':insumo_id', $this->insumo_id);
            $stmt->bindParam(':quantidade', $this->quantidade);
            $stmt->bindParam(':motivo', $this->motivo);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':registrado_por', $this->registrado_por);

            if(!$stmt->execute()) {
                throw new Exception("Erro ao registrar desperdício");
            }

            $this->id = $this->conn->lastInsertId();

            // Buscar custo unitário do insumo
            $query_custo = "SELECT custo_unitario_atual FROM " . $this->insumos_table . " WHERE id = :insumo_id";
            $stmt_custo = $this->conn->prepare($query_custo);
            $stmt_custo->bindParam(':insumo_id', $this->insumo_id);
            $stmt_custo->execute();
            $custo = $stmt_custo->fetch(PDO::FETCH_ASSOC);
            $custo_unitario = $custo['custo_unitario_atual'] ?? 0;

            // Registrar no histórico de estoque
            $query_historico = "INSERT INTO " . $this->historico_table . " 
                               (insumo_id, tipo_movimentacao, quantidade, custo_unitario, motivo, referencia_id) 
                               VALUES (:insumo_id, 'desperdicio', :quantidade, :custo_unitario, :motivo, :referencia_id)";

            $stmt_historico = $this->conn->prepare($query_historico);
            $motivo_historico = "Desperdício: " . $this->motivo . ($this->descricao ? " - " . $this->descricao : "");
            $stmt_historico->bindParam(':insumo_id', $this->insumo_id);
            $stmt_historico->bindParam(':quantidade', $this->quantidade);
            $stmt_historico->bindParam(':custo_unitario', $custo_unitario);
            $stmt_historico->bindParam(':motivo', $motivo_historico);
            $stmt_historico->bindParam(':referencia_id', $this->id);

            if(!$stmt_historico->execute()) {
                throw new Exception("Erro ao registrar no histórico");
            }

            // Atualizar estoque do insumo
            $query_estoque = "UPDATE " . $this->insumos_table . " 
                             SET estoque_atual = estoque_atual - :quantidade 
                             WHERE id = :insumo_id";

            $stmt_estoque = $this->conn->prepare($query_estoque);
            $stmt_estoque->bindParam(':quantidade', $this->quantidade);
            $stmt_estoque->bindParam(':insumo_id', $this->insumo_id);

            if(!$stmt_estoque->execute()) {
                throw new Exception("Erro ao atualizar estoque");
            }

            // Commit da transação
            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            // Rollback em caso de erro
            $this->conn->rollBack();
            error_log("Erro ao registrar desperdício: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar todos os desperdícios
     */
    public function listar($limite = 50) {
        $query = "SELECT d.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " d
                  INNER JOIN " . $this->insumos_table . " i ON d.insumo_id = i.id
                  ORDER BY d.data_registro DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Listar desperdícios por insumo
     */
    public function listarPorInsumo($insumo_id, $limite = 50) {
        $query = "SELECT d.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " d
                  INNER JOIN " . $this->insumos_table . " i ON d.insumo_id = i.id
                  WHERE d.insumo_id = :insumo_id
                  ORDER BY d.data_registro DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Listar desperdícios por motivo
     */
    public function listarPorMotivo($motivo, $limite = 50) {
        $query = "SELECT d.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " d
                  INNER JOIN " . $this->insumos_table . " i ON d.insumo_id = i.id
                  WHERE d.motivo = :motivo
                  ORDER BY d.data_registro DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Buscar por ID
     */
    public function buscarPorId($id) {
        $query = "SELECT d.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " d
                  INNER JOIN " . $this->insumos_table . " i ON d.insumo_id = i.id
                  WHERE d.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->insumo_id = $row['insumo_id'];
            $this->quantidade = $row['quantidade'];
            $this->motivo = $row['motivo'];
            $this->descricao = $row['descricao'];
            $this->data_registro = $row['data_registro'];
            $this->registrado_por = $row['registrado_por'];
            return true;
        }
        return false;
    }

    /**
     * Obter estatísticas de desperdícios
     */
    public function obterEstatisticas($insumo_id = null) {
        $where = $insumo_id ? "WHERE d.insumo_id = :insumo_id" : "";
        $query = "SELECT 
                    COUNT(*) as total_desperdicios,
                    SUM(quantidade) as quantidade_total,
                    SUM(quantidade * i.custo_unitario_atual) as valor_total,
                    SUM(CASE WHEN motivo = 'validade' THEN quantidade ELSE 0 END) as quantidade_validade,
                    SUM(CASE WHEN motivo = 'quebra' THEN quantidade ELSE 0 END) as quantidade_quebra,
                    SUM(CASE WHEN motivo = 'consumo_interno' THEN quantidade ELSE 0 END) as quantidade_consumo_interno,
                    SUM(CASE WHEN motivo = 'outro' THEN quantidade ELSE 0 END) as quantidade_outro
                  FROM " . $this->table_name . " d
                  INNER JOIN " . $this->insumos_table . " i ON d.insumo_id = i.id
                  " . $where;
        
        $stmt = $this->conn->prepare($query);
        if($insumo_id) {
            $stmt->bindParam(':insumo_id', $insumo_id);
        }
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Listar desperdícios por período
     */
    public function listarPorPeriodo($data_inicio, $data_fim) {
        $query = "SELECT d.*, i.nome as insumo_nome, i.unidade_compra, i.custo_unitario_atual,
                         (d.quantidade * i.custo_unitario_atual) as valor_desperdicio
                  FROM " . $this->table_name . " d
                  INNER JOIN " . $this->insumos_table . " i ON d.insumo_id = i.id
                  WHERE DATE(d.data_registro) BETWEEN :data_inicio AND :data_fim
                  ORDER BY d.data_registro DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt;
    }
}
?>

