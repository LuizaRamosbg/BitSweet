<?php
/**
 * Classe para cálculos de custo por unidade
 * Sistema de Gestão da Doceria
 */

require_once __DIR__ . '/../config/database.php';

class CalculadoraCusto {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Calcular custo unitário simples
     */
    public function calcularCustoUnitario($preco_total, $quantidade) {
        if($quantidade <= 0) {
            return 0;
        }
        return $preco_total / $quantidade;
    }

    /**
     * Calcular custo médio ponderado de um insumo
     */
    public function calcularCustoMedioPonderado($insumo_id) {
        $query = "SELECT 
                    SUM(quantidade) as total_quantidade,
                    SUM(preco_total) as total_valor
                  FROM compras 
                  WHERE insumo_id = :insumo_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result['total_quantidade'] > 0) {
            return $result['total_valor'] / $result['total_quantidade'];
        }
        return 0;
    }

    /**
     * Calcular custo de produção de um produto
     */
    public function calcularCustoProducao($receita_ingredientes) {
        $custo_total = 0;
        
        foreach($receita_ingredientes as $ingrediente) {
            $insumo_id = $ingrediente['insumo_id'];
            $quantidade_receita = $ingrediente['quantidade'];
            
            // Buscar custo atual do insumo
            $query = "SELECT custo_unitario_atual FROM insumos WHERE id = :insumo_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':insumo_id', $insumo_id);
            $stmt->execute();
            
            $insumo = $stmt->fetch(PDO::FETCH_ASSOC);
            if($insumo) {
                $custo_total += $insumo['custo_unitario_atual'] * $quantidade_receita;
            }
        }
        
        return $custo_total;
    }

    /**
     * Calcular margem de lucro (como percentual do custo - markup)
     * Fórmula: margem = ((preco - custo) / custo) * 100
     */
    public function calcularMargemLucro($custo_producao, $preco_venda) {
        if($custo_producao <= 0) {
            return 0;
        }
        
        if($preco_venda <= $custo_producao) {
            return 0;
        }
        
        return (($preco_venda - $custo_producao) / $custo_producao) * 100;
    }

    /**
     * Calcular preço de venda com margem desejada (como markup)
     * Fórmula: preco_venda = custo_producao * (1 + margem_percentual/100)
     * Ex: custo=100, margem=30% => preco=130
     */
    public function calcularPrecoVenda($custo_producao, $margem_percentual) {
        if($margem_percentual < 0) {
            return $custo_producao;
        }
        
        return $custo_producao * (1 + ($margem_percentual / 100));
    }

    /**
     * Obter histórico de custos de um insumo
     */
    public function obterHistoricoCustos($insumo_id, $limite = 10) {
        $query = "SELECT 
                    data_compra,
                    custo_unitario,
                    quantidade,
                    preco_total,
                    fornecedor
                  FROM compras 
                  WHERE insumo_id = :insumo_id 
                  ORDER BY data_compra DESC 
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular variação de preço entre períodos
     */
    public function calcularVariacaoPreco($insumo_id, $data_inicio, $data_fim) {
        $query = "SELECT 
                    AVG(custo_unitario) as custo_medio_inicio
                  FROM compras 
                  WHERE insumo_id = :insumo_id 
                  AND data_compra BETWEEN :data_inicio AND :data_fim";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['custo_medio_inicio'] ?? 0;
    }

    /**
     * Obter estatísticas de custos por categoria
     */
    public function obterEstatisticasCustosPorCategoria() {
        $query = "SELECT 
                    i.categoria,
                    COUNT(DISTINCT i.id) as total_insumos,
                    AVG(i.custo_unitario_atual) as custo_medio_categoria,
                    SUM(i.estoque_atual * i.custo_unitario_atual) as valor_total_estoque
                  FROM insumos i
                  WHERE i.ativo = 1
                  GROUP BY i.categoria
                  ORDER BY custo_medio_categoria DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular custo de oportunidade do estoque
     */
    public function calcularCustoOportunidadeEstoque($taxa_juros_anual = 0.12) {
        $query = "SELECT 
                    SUM(estoque_atual * custo_unitario_atual) as valor_total_estoque
                  FROM insumos 
                  WHERE ativo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $valor_estoque = $result['valor_total_estoque'] ?? 0;
        
        // Custo de oportunidade mensal
        return $valor_estoque * ($taxa_juros_anual / 12);
    }

    /**
     * Identificar insumos com maior impacto no custo
     */
    public function identificarInsumosMaiorImpacto($limite = 5) {
        $query = "SELECT 
                    i.nome,
                    i.categoria,
                    i.custo_unitario_atual,
                    i.estoque_atual,
                    (i.estoque_atual * i.custo_unitario_atual) as valor_estoque,
                    COUNT(c.id) as total_compras
                  FROM insumos i
                  LEFT JOIN compras c ON i.id = c.insumo_id
                  WHERE i.ativo = 1
                  GROUP BY i.id
                  ORDER BY valor_estoque DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
