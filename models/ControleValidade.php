<?php
/**
 * Modelo para controle de validade de insumos
 * Sistema de Gestão da Doceria
 */

require_once __DIR__ . '/../config/database.php';

class ControleValidade {
    private $conn;
    private $table_name = "controle_validade";
    private $alertas_table = "alertas_validade";

    public $id;
    public $insumo_id;
    public $lote;
    public $quantidade_lote;
    public $data_fabricacao;
    public $data_validade;
    public $quantidade_atual;
    public $status;
    public $observacoes;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Converter quantidade para a unidade interna do insumo
     */
    private function converterQuantidadeParaUnidadeInterna($insumo_id, $quantidade) {
        $query = "SELECT unidade_compra, fator_conversao FROM insumos WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $insumo_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $quantidade;
        }

        $unidade = $row['unidade_compra'];
        $fator = (float) ($row['fator_conversao'] ?? 1.0);

        if (in_array($unidade, ['kg', 'L']) && $fator > 1) {
            return $quantidade * $fator;
        }

        return $quantidade;
    }

    /**
     * Cadastrar novo lote com controle de validade
     */
    public function cadastrarLote() {
        $this->quantidade_lote = $this->converterQuantidadeParaUnidadeInterna($this->insumo_id, $this->quantidade_lote);
        $this->quantidade_atual = $this->converterQuantidadeParaUnidadeInterna($this->insumo_id, $this->quantidade_atual);

        $query = "INSERT INTO " . $this->table_name . " 
                  (insumo_id, lote, quantidade_lote, data_fabricacao, data_validade, 
                   quantidade_atual, observacoes) 
                  VALUES (:insumo_id, :lote, :quantidade_lote, :data_fabricacao, :data_validade, 
                          :quantidade_atual, :observacoes)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->lote = htmlspecialchars(strip_tags($this->lote));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));

        // Bind dos parâmetros
        $stmt->bindParam(':insumo_id', $this->insumo_id);
        $stmt->bindParam(':lote', $this->lote);
        $stmt->bindParam(':quantidade_lote', $this->quantidade_lote);
        $stmt->bindParam(':data_fabricacao', $this->data_fabricacao);
        $stmt->bindParam(':data_validade', $this->data_validade);
        $stmt->bindParam(':quantidade_atual', $this->quantidade_atual);
        $stmt->bindParam(':observacoes', $this->observacoes);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->atualizarStatus();
            return true;
        }
        return false;
    }

    /**
     * Atualizar status do lote baseado na data de validade
     */
    public function atualizarStatus() {
        $hoje = date('Y-m-d');
        $proximo_vencer = date('Y-m-d', strtotime('+7 days')); // 7 dias antes

        $query = "UPDATE " . $this->table_name . " 
                  SET status = CASE 
                      WHEN data_validade < :hoje THEN 'vencido'
                      WHEN data_validade <= :proximo_vencer THEN 'proximo_vencer'
                      ELSE 'valido'
                  END
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->bindParam(':proximo_vencer', $proximo_vencer);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        // Buscar o status atualizado
        $status_query = "SELECT status FROM " . $this->table_name . " WHERE id = :id";
        $status_stmt = $this->conn->prepare($status_query);
        $status_stmt->bindParam(':id', $this->id);
        $status_stmt->execute();
        
        $result = $status_stmt->fetch(PDO::FETCH_ASSOC);
        $this->status = $result['status'];
    }

    /**
     * Listar todos os lotes
     */
    public function listarLotes() {
        $query = "SELECT cv.*, i.nome as insumo_nome, i.unidade_compra, i.fator_conversao 
                  FROM " . $this->table_name . " cv
                  INNER JOIN insumos i ON cv.insumo_id = i.id
                  ORDER BY cv.data_validade DESC, cv.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Listar lotes por insumo
     */
    public function listarLotesPorInsumo($insumo_id) {
        $query = "SELECT cv.*, i.nome as insumo_nome, i.unidade_compra, i.fator_conversao 
                  FROM " . $this->table_name . " cv
                  INNER JOIN insumos i ON cv.insumo_id = i.id
                  WHERE cv.insumo_id = :insumo_id
                  ORDER BY cv.data_validade DESC, cv.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Listar lotes próximos ao vencimento
     */
    public function listarLotesProximosVencer($dias = 7) {
        $data_limite = date('Y-m-d', strtotime("+{$dias} days"));
        
        $query = "SELECT cv.*, i.nome as insumo_nome, i.unidade_compra, i.fator_conversao,
                         DATEDIFF(cv.data_validade, CURDATE()) as dias_para_vencer
                  FROM " . $this->table_name . " cv
                  INNER JOIN insumos i ON cv.insumo_id = i.id
                  WHERE cv.data_validade <= :data_limite AND cv.data_validade >= CURDATE()
                  ORDER BY cv.data_validade ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_limite', $data_limite);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Listar lotes vencidos
     */
    public function listarLotesVencidos() {
        $hoje = date('Y-m-d');
        
        $query = "SELECT cv.*, i.nome as insumo_nome, i.unidade_compra, i.fator_conversao,
                         DATEDIFF(CURDATE(), cv.data_validade) as dias_vencido
                  FROM " . $this->table_name . " cv
                  INNER JOIN insumos i ON cv.insumo_id = i.id
                  WHERE cv.data_validade < :hoje
                  ORDER BY cv.data_validade ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Atualizar quantidade atual do lote
     */
    public function atualizarQuantidade($nova_quantidade) {
        $query = "UPDATE " . $this->table_name . " 
                  SET quantidade_atual = :quantidade_atual 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantidade_atual', $nova_quantidade);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            $this->quantidade_atual = $nova_quantidade;
            return true;
        }
        return false;
    }

    /**
     * Consumir quantidade do lote (usar insumo)
     */
    public function consumirQuantidade($quantidade_consumida) {
        if($this->quantidade_atual >= $quantidade_consumida) {
            $nova_quantidade = $this->quantidade_atual - $quantidade_consumida;
            return $this->atualizarQuantidade($nova_quantidade);
        }
        return false;
    }

    /**
     * Verificar e gerar alertas de validade
     */
    public function verificarAlertasValidade() {
        $alertas_gerados = 0;
        
        // Atualizar status de todos os lotes
        $this->atualizarTodosStatus();
        
        // Verificar lotes próximos ao vencimento
        $lotes_proximos = $this->listarLotesProximosVencer();
        while($row = $lotes_proximos->fetch(PDO::FETCH_ASSOC)) {
            if(!$this->existeAlertaAtivo($row['id'], 'proximo_vencer')) {
                $this->criarAlertaValidade($row['id'], $row['insumo_id'], 'proximo_vencer', $row['dias_para_vencer']);
                $alertas_gerados++;
            }
        }
        
        // Verificar lotes vencidos
        $lotes_vencidos = $this->listarLotesVencidos();
        while($row = $lotes_vencidos->fetch(PDO::FETCH_ASSOC)) {
            if(!$this->existeAlertaAtivo($row['id'], 'vencido')) {
                $this->criarAlertaValidade($row['id'], $row['insumo_id'], 'vencido', 0);
                $alertas_gerados++;
            }
        }
        
        return $alertas_gerados;
    }

    /**
     * Atualizar status de todos os lotes
     */
    private function atualizarTodosStatus() {
        $hoje = date('Y-m-d');
        $proximo_vencer = date('Y-m-d', strtotime('+7 days'));

        $query = "UPDATE " . $this->table_name . " 
                  SET status = CASE 
                      WHEN data_validade < :hoje THEN 'vencido'
                      WHEN data_validade <= :proximo_vencer THEN 'proximo_vencer'
                      ELSE 'valido'
                  END";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->bindParam(':proximo_vencer', $proximo_vencer);
        $stmt->execute();
    }

    /**
     * Verificar se já existe alerta ativo para o lote
     */
    private function existeAlertaAtivo($controle_validade_id, $tipo_alerta) {
        $query = "SELECT id FROM " . $this->alertas_table . " 
                  WHERE controle_validade_id = :controle_validade_id 
                  AND tipo_alerta = :tipo_alerta 
                  AND visualizado = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':controle_validade_id', $controle_validade_id);
        $stmt->bindParam(':tipo_alerta', $tipo_alerta);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Criar alerta de validade
     */
    private function criarAlertaValidade($controle_validade_id, $insumo_id, $tipo_alerta, $dias_para_vencer) {
        $query = "INSERT INTO " . $this->alertas_table . " 
                  (controle_validade_id, insumo_id, tipo_alerta, dias_para_vencer) 
                  VALUES (:controle_validade_id, :insumo_id, :tipo_alerta, :dias_para_vencer)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':controle_validade_id', $controle_validade_id);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->bindParam(':tipo_alerta', $tipo_alerta);
        $stmt->bindParam(':dias_para_vencer', $dias_para_vencer);
        
        return $stmt->execute();
    }

    /**
     * Listar alertas de validade não visualizados
     */
    public function listarAlertasNaoVisualizados() {
        $query = "SELECT av.*, cv.lote, cv.data_validade, cv.quantidade_atual,
                         i.nome as insumo_nome, i.unidade_compra, i.fator_conversao
                  FROM " . $this->alertas_table . " av
                  INNER JOIN " . $this->table_name . " cv ON av.controle_validade_id = cv.id
                  INNER JOIN insumos i ON av.insumo_id = i.id
                  WHERE av.visualizado = 0
                  ORDER BY av.data_alerta DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Marcar alerta como visualizado
     */
    public function marcarAlertaVisualizado($alerta_id) {
        $query = "UPDATE " . $this->alertas_table . " 
                  SET visualizado = 1 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $alerta_id);
        
        return $stmt->execute();
    }

    /**
     * Obter estatísticas de validade
     */
    public function obterEstatisticasValidade() {
        $query = "SELECT 
                    COUNT(*) as total_lotes,
                    SUM(CASE WHEN status = 'valido' THEN 1 ELSE 0 END) as lotes_validos,
                    SUM(CASE WHEN status = 'proximo_vencer' THEN 1 ELSE 0 END) as lotes_proximos_vencer,
                    SUM(CASE WHEN status = 'vencido' THEN 1 ELSE 0 END) as lotes_vencidos,
                    SUM(quantidade_atual) as quantidade_total_estoque
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar lote por ID
     */
    public function buscarPorId($id) {
        $query = "SELECT cv.*, i.nome as insumo_nome, i.unidade_compra, i.fator_conversao 
                  FROM " . $this->table_name . " cv
                  INNER JOIN insumos i ON cv.insumo_id = i.id
                  WHERE cv.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->insumo_id = $row['insumo_id'];
            $this->lote = $row['lote'];
            $this->quantidade_lote = $row['quantidade_lote'];
            $this->data_fabricacao = $row['data_fabricacao'];
            $this->data_validade = $row['data_validade'];
            $this->quantidade_atual = $row['quantidade_atual'];
            $this->status = $row['status'];
            $this->observacoes = $row['observacoes'];
            return true;
        }
        return false;
    }

    /**
     * Excluir lote
     */
    public function excluirLote() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }
}
?>
