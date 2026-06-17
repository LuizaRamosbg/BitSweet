<?php
/**
 * Modelo para gerenciar Alertas de Estoque
 * Sistema de Gestão da Doceria
 */

require_once __DIR__ . '/../config/database.php';

class AlertaEstoque {
    private $conn;
    private $table_name = "alertas_estoque";
    private $insumos_table = "insumos";

    public $id;
    public $insumo_id;
    public $tipo_alerta;
    public $quantidade_atual;
    public $quantidade_minima;
    public $data_alerta;
    public $visualizado;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Verificar e gerar alertas de estoque mínimo
     */
    public function obterAlertasAtuais() {
        $query = "SELECT id, nome, estoque_atual, estoque_minimo, 
                  CASE WHEN estoque_atual <= 0 THEN 'estoque_zerado' ELSE 'estoque_minimo' END as tipo_alerta 
                  FROM " . $this->insumos_table . " 
                  WHERE ativo = 1 AND estoque_atual <= estoque_minimo";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $alertas = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alertas[] = [
                'insumo_id' => $row['id'],
                'tipo_alerta' => $row['tipo_alerta'],
                'quantidade_atual' => $row['estoque_atual'],
                'quantidade_minima' => $row['estoque_minimo'],
                'insumo_nome' => $row['nome'],
            ];
        }

        return $alertas;
    }

    public function verificarAlertasEstoque() {
        $alertas = $this->obterAlertasAtuais();
        return count($alertas);
    }

    /**
     * Verificar se já existe alerta ativo para o insumo
     */
    private function existeAlertaAtivo($insumo_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE insumo_id = :insumo_id AND visualizado = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Criar novo alerta
     */
    private function criarAlerta() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (insumo_id, tipo_alerta, quantidade_atual, quantidade_minima) 
                  VALUES (:insumo_id, :tipo_alerta, :quantidade_atual, :quantidade_minima)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $this->insumo_id);
        $stmt->bindParam(':tipo_alerta', $this->tipo_alerta);
        $stmt->bindParam(':quantidade_atual', $this->quantidade_atual);
        $stmt->bindParam(':quantidade_minima', $this->quantidade_minima);

        return $stmt->execute();
    }

    /**
     * Listar alertas não visualizados
     */
    public function listarAlertasNaoVisualizados() {
        $query = "SELECT a.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " a
                  INNER JOIN " . $this->insumos_table . " i ON a.insumo_id = i.id
                  WHERE a.visualizado = 0
                  ORDER BY a.data_alerta DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Listar todos os alertas
     */
    public function listarTodosAlertas($limite = 50) {
        $query = "SELECT a.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " a
                  INNER JOIN " . $this->insumos_table . " i ON a.insumo_id = i.id
                  ORDER BY a.data_alerta DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Marcar alerta como visualizado
     */
    public function marcarComoVisualizado($alerta_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET visualizado = 1 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $alerta_id);
        
        return $stmt->execute();
    }

    /**
     * Marcar todos os alertas como visualizados
     */
    public function marcarTodosComoVisualizados() {
        $query = "UPDATE " . $this->table_name . " 
                  SET visualizado = 1 
                  WHERE visualizado = 0";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    /**
     * Obter estatísticas de alertas
     */
    public function obterEstatisticasAlertas() {
        $query = "SELECT 
                    COUNT(*) as total_alertas,
                    SUM(CASE WHEN visualizado = 0 THEN 1 ELSE 0 END) as alertas_nao_visualizados,
                    SUM(CASE WHEN tipo_alerta = 'estoque_minimo' THEN 1 ELSE 0 END) as alertas_estoque_minimo,
                    SUM(CASE WHEN tipo_alerta = 'estoque_zerado' THEN 1 ELSE 0 END) as alertas_estoque_zerado,
                    COUNT(DISTINCT insumo_id) as insumos_com_alerta
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obterEstatisticasAtuais() {
        $query = "SELECT 
                    SUM(CASE WHEN estoque_atual <= 0 THEN 1 ELSE 0 END) as alertas_estoque_zerado,
                    SUM(CASE WHEN estoque_atual > 0 AND estoque_atual <= estoque_minimo THEN 1 ELSE 0 END) as alertas_estoque_minimo,
                    COUNT(*) as total_alertas
                  FROM " . $this->insumos_table . " 
                  WHERE ativo = 1 AND estoque_atual <= estoque_minimo";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total_alertas' => (int) ($result['total_alertas'] ?? 0),
            'alertas_nao_visualizados' => (int) ($result['total_alertas'] ?? 0),
            'alertas_estoque_minimo' => (int) ($result['alertas_estoque_minimo'] ?? 0),
            'alertas_estoque_zerado' => (int) ($result['alertas_estoque_zerado'] ?? 0),
        ];
    }

    /**
     * Obter alertas por período
     */
    public function obterAlertasPorPeriodo($data_inicio, $data_fim) {
        $query = "SELECT a.*, i.nome as insumo_nome, i.unidade_compra 
                  FROM " . $this->table_name . " a
                  INNER JOIN " . $this->insumos_table . " i ON a.insumo_id = i.id
                  WHERE DATE(a.data_alerta) BETWEEN :data_inicio AND :data_fim
                  ORDER BY a.data_alerta DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Limpar alertas antigos (mais de 30 dias)
     */
    public function limparAlertasAntigos() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE data_alerta < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    /**
     * Obter insumos críticos (estoque muito baixo)
     */
    public function obterInsumosCriticos($percentual_minimo = 0.1) {
        $query = "SELECT 
                    i.id,
                    i.nome,
                    i.estoque_atual,
                    i.estoque_minimo,
                    i.unidade_compra,
                    i.categoria,
                    CASE 
                        WHEN i.estoque_atual <= 0 THEN 'ZERADO'
                        WHEN i.estoque_atual <= i.estoque_minimo THEN 'CRÍTICO'
                        WHEN i.estoque_atual <= (i.estoque_minimo * 1.5) THEN 'BAIXO'
                        ELSE 'NORMAL'
                    END as status_estoque
                  FROM " . $this->insumos_table . " i
                  WHERE i.ativo = 1 
                  AND i.estoque_atual <= (i.estoque_minimo * (1 + :percentual_minimo))
                  ORDER BY (i.estoque_atual / i.estoque_minimo) ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':percentual_minimo', $percentual_minimo);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Enviar notificação por email (simulação)
     */
    public function enviarNotificacaoEmail($alerta_id) {
        // Esta função seria implementada com um sistema de email real
        // Por enquanto, apenas registra no log
        
        $query = "SELECT a.*, i.nome as insumo_nome 
                  FROM " . $this->table_name . " a
                  INNER JOIN " . $this->insumos_table . " i ON a.insumo_id = i.id
                  WHERE a.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $alerta_id);
        $stmt->execute();
        
        $alerta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($alerta) {
            $mensagem = "ALERTA DE ESTOQUE: {$alerta['insumo_nome']} - ";
            $mensagem .= "Estoque atual: {$alerta['quantidade_atual']} ";
            $mensagem .= "Estoque mínimo: {$alerta['quantidade_minima']}";
            
            // Aqui seria enviado o email real
            error_log("NOTIFICAÇÃO: " . $mensagem);
            
            return true;
        }
        
        return false;
    }
}
?>
