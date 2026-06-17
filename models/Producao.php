<?php
/**
 * Modelo para gerenciar Produções
 * Sistema de Gestão da Doceria
 * Implementa lógica LIFO para consumo de insumos por lotes
 */

require_once __DIR__ . '/../config/database.php';

class Producao {
    private $conn;
    private $table_name = "producoes";
    private $receitas_table = "receitas";
    private $ingredientes_table = "receita_ingredientes";
    private $insumos_table = "insumos";
    private $controle_validade_table = "controle_validade";
    private $historico_table = "historico_estoque";

    public $id;
    public $receita_id;
    public $quantidade_produzida;
    public $data_producao;
    public $custo_total;
    public $observacoes;
    public $warnings = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Registrar produção com consumo LIFO dos insumos
     */
    public function registrar() {
        // Iniciar transação
        $this->conn->beginTransaction();
        
        try {
            // Buscar receita e calcular custo total
            $query_receita = "SELECT * FROM " . $this->receitas_table . " WHERE id = :receita_id";
            $stmt_receita = $this->conn->prepare($query_receita);
            $stmt_receita->bindParam(':receita_id', $this->receita_id);
            $stmt_receita->execute();
            $receita = $stmt_receita->fetch(PDO::FETCH_ASSOC);
            
            if(!$receita) {
                throw new Exception("Receita não encontrada");
            }

            // Buscar ingredientes da receita
            $query_ingredientes = "SELECT ri.*, i.unidade_compra, i.fator_conversao, i.custo_unitario_atual
                                  FROM " . $this->ingredientes_table . " ri
                                  INNER JOIN " . $this->insumos_table . " i ON ri.insumo_id = i.id
                                  WHERE ri.receita_id = :receita_id
                                  ORDER BY ri.ordem ASC";
            
            $stmt_ingredientes = $this->conn->prepare($query_ingredientes);
            $stmt_ingredientes->bindParam(':receita_id', $this->receita_id);
            $stmt_ingredientes->execute();
            $ingredientes = $stmt_ingredientes->fetchAll(PDO::FETCH_ASSOC);

            $custo_total_producao = 0;

            // Processar cada ingrediente com lógica LIFO
            foreach($ingredientes as $ingrediente) {
                $quantidade_necessaria = $ingrediente['quantidade'] * $this->quantidade_produzida;
                $unidade_uso = $ingrediente['unidade_uso'];
                $unidade_compra = $ingrediente['unidade_compra'];
                $fator_conversao = $ingrediente['fator_conversao'] ?? 1.0;

                // Converter quantidade da unidade de uso para unidade de compra
                $quantidade_convertida = $this->converterUnidade($quantidade_necessaria, $unidade_uso, $unidade_compra, $fator_conversao);

                // Buscar lotes disponíveis ordenados por lote mais recente (LIFO)
                $lotes = $this->buscarLotesDisponiveis($ingrediente['insumo_id'], $quantidade_convertida);

                $quantidade_restante = $quantidade_convertida;
                $custo_ingrediente = 0;

                // Consumir dos lotes seguindo LIFO
                foreach($lotes as $lote) {
                    if($quantidade_restante <= 0) {
                        break;
                    }

                    $quantidade_consumir = min($quantidade_restante, $lote['quantidade_atual']);
                    
                    // Buscar custo do lote (pode ser calculado a partir das compras)
                    $custo_lote = $this->obterCustoLote($lote['id'], $ingrediente['insumo_id']);
                    $custo_ingrediente += $quantidade_consumir * $custo_lote;

                    // Atualizar quantidade do lote
                    $this->atualizarLote($lote['id'], $lote['quantidade_atual'] - $quantidade_consumir);

                    // Atualizar estoque geral para refletir consumo de lote
                    $this->atualizarEstoqueGeral($ingrediente['insumo_id'], $quantidade_consumir);

                    // Registrar no histórico
                    $this->registrarHistorico($ingrediente['insumo_id'], $quantidade_consumir, $custo_lote, $lote['id']);

                    $quantidade_restante -= $quantidade_consumir;
                }

                // Se ainda há quantidade restante, consumir do estoque geral (sem lote)
                if($quantidade_restante > 0) {
                    $custo_unitario = $ingrediente['custo_unitario_atual'];
                    $custo_ingrediente += $quantidade_restante * $custo_unitario;
                    
                    // Atualizar estoque geral
                    $novo_estoque = $this->atualizarEstoqueGeral($ingrediente['insumo_id'], $quantidade_restante);
                    if ($novo_estoque !== null && $novo_estoque < 0) {
                        $this->registrarAvisoEstoqueNegativo($ingrediente['insumo_id'], $novo_estoque);
                    }
                    
                    // Registrar no histórico
                    $this->registrarHistorico($ingrediente['insumo_id'], $quantidade_restante, $custo_unitario, null);
                }

                $custo_total_producao += $custo_ingrediente;
            }

            $this->custo_total = $custo_total_producao;

            // Inserir registro de produção
            $query = "INSERT INTO " . $this->table_name . " 
                      (receita_id, quantidade_produzida, custo_total, observacoes) 
                      VALUES (:receita_id, :quantidade_produzida, :custo_total, :observacoes)";

            $stmt = $this->conn->prepare($query);
            $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));
            $stmt->bindParam(':receita_id', $this->receita_id);
            $stmt->bindParam(':quantidade_produzida', $this->quantidade_produzida);
            $stmt->bindParam(':custo_total', $this->custo_total);
            $stmt->bindParam(':observacoes', $this->observacoes);

            if(!$stmt->execute()) {
                throw new Exception("Erro ao registrar produção");
            }

            $this->id = $this->conn->lastInsertId();

            // Commit da transação
            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            // Rollback em caso de erro
            $this->conn->rollBack();
            error_log("Erro ao registrar produção: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar lotes disponíveis ordenados por lote mais recente (LIFO)
     */
    private function buscarLotesDisponiveis($insumo_id, $quantidade_necessaria) {
        $query = "SELECT * FROM " . $this->controle_validade_table . " 
                  WHERE insumo_id = :insumo_id 
                  AND quantidade_atual > 0 
                  AND status != 'vencido'
                  ORDER BY id DESC, data_validade DESC, data_fabricacao DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->execute();
        
        $lotes = [];
        $quantidade_total = 0;
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lotes[] = $row;
            $quantidade_total += $row['quantidade_atual'];
            
            if($quantidade_total >= $quantidade_necessaria) {
                break;
            }
        }
        
        return $lotes;
    }

    /**
     * Obter custo do lote
     */
    private function obterCustoLote($lote_id, $insumo_id) {
        // Buscar custo médio das compras relacionadas ao insumo e unidade de compra
        $query = "SELECT AVG(c.custo_unitario) as custo_medio, i.unidade_compra, i.fator_conversao 
                  FROM compras c 
                  INNER JOIN insumos i ON c.insumo_id = i.id 
                  WHERE c.insumo_id = :insumo_id ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result && $result['custo_medio']) {
            $unidade = $result['unidade_compra'];
            $fator = (float) ($result['fator_conversao'] ?? 1.0);

            if (in_array($unidade, ['kg', 'L']) && $fator > 1) {
                return $result['custo_medio'] / $fator;
            }

            return $result['custo_medio'];
        }
        
        // Se não houver compras, usar custo atual do insumo
        $query_insumo = "SELECT custo_unitario_atual, unidade_compra, fator_conversao FROM " . $this->insumos_table . " WHERE id = :insumo_id";
        $stmt_insumo = $this->conn->prepare($query_insumo);
        $stmt_insumo->bindParam(':insumo_id', $insumo_id);
        $stmt_insumo->execute();
        $insumo = $stmt_insumo->fetch(PDO::FETCH_ASSOC);

        $custo = $insumo['custo_unitario_atual'] ?? 0;
        $unidade = $insumo['unidade_compra'] ?? '';
        $fator = (float) ($insumo['fator_conversao'] ?? 1.0);

        if (in_array($unidade, ['kg', 'L']) && $fator > 1) {
            return $custo / $fator;
        }

        return $custo;
    }

    /**
     * Atualizar quantidade do lote
     */
    private function atualizarLote($lote_id, $nova_quantidade) {
        $query = "UPDATE " . $this->controle_validade_table . " 
                  SET quantidade_atual = :quantidade_atual 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantidade_atual', $nova_quantidade);
        $stmt->bindParam(':id', $lote_id);
        $stmt->execute();
    }

    /**
     * Atualizar estoque geral do insumo
     */
    private function atualizarEstoqueGeral($insumo_id, $quantidade) {
        $query = "UPDATE " . $this->insumos_table . " 
                  SET estoque_atual = estoque_atual - :quantidade 
                  WHERE id = :insumo_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->execute();

        return $this->obterEstoqueAtual($insumo_id);
    }

    private function obterEstoqueAtual($insumo_id) {
        $query = "SELECT estoque_atual, nome FROM " . $this->insumos_table . " WHERE id = :insumo_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->execute();
        $insumo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $insumo ? (float) $insumo['estoque_atual'] : null;
    }

    private function registrarAvisoEstoqueNegativo($insumo_id, $novo_estoque) {
        $query = "SELECT nome FROM " . $this->insumos_table . " WHERE id = :insumo_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->execute();
        $insumo = $stmt->fetch(PDO::FETCH_ASSOC);
        $nome_insumo = $insumo['nome'] ?? 'desconhecido';

        $this->warnings[] = "O estoque do insumo '{$nome_insumo}' ficou negativo ({$novo_estoque}). Provavelmente há compras feitas que ainda não foram adicionadas.";
    }

    /**
     * Registrar movimentação no histórico
     */
    private function registrarHistorico($insumo_id, $quantidade, $custo_unitario, $referencia_id) {
        $query = "INSERT INTO " . $this->historico_table . " 
                  (insumo_id, tipo_movimentacao, quantidade, custo_unitario, motivo, referencia_id) 
                  VALUES (:insumo_id, 'saida', :quantidade, :custo_unitario, 'Produção de receita', :referencia_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':insumo_id', $insumo_id);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':custo_unitario', $custo_unitario);
        $stmt->bindParam(':referencia_id', $referencia_id);
        $stmt->execute();
    }

    /**
     * Converter unidade de uso para unidade de compra
     */
    private function converterUnidade($quantidade, $unidade_origem, $unidade_destino, $fator_conversao) {
        // Se as unidades são iguais, não precisa converter
        if($unidade_origem == $unidade_destino) {
            return $quantidade;
        }

        // Conversões básicas (pode ser expandido)
        $conversoes = [
            'kg' => ['g' => 1000, 'L' => 1, 'ml' => 1000],
            'g' => ['kg' => 0.001],
            'L' => ['ml' => 1000, 'kg' => 1],
            'ml' => ['L' => 0.001]
        ];

        // Se houver fator de conversão definido, usar ele
        if($fator_conversao != 1.0) {
            // Aplicar fator de conversão
            if(in_array($unidade_origem, ['kg', 'L']) && in_array($unidade_destino, ['g', 'ml'])) {
                return $quantidade * $fator_conversao;
            } elseif(in_array($unidade_origem, ['g', 'ml']) && in_array($unidade_destino, ['kg', 'L'])) {
                return $quantidade / $fator_conversao;
            }
        }

        // Usar conversões padrão se disponíveis
        if(isset($conversoes[$unidade_origem][$unidade_destino])) {
            return $quantidade * $conversoes[$unidade_origem][$unidade_destino];
        }

        // Se não houver conversão, retornar quantidade original
        return $quantidade;
    }

    /**
     * Listar produções
     */
    public function listar($limite = 50) {
        $query = "SELECT p.*, r.nome as receita_nome 
                  FROM " . $this->table_name . " p
                  INNER JOIN " . $this->receitas_table . " r ON p.receita_id = r.id
                  ORDER BY p.data_producao DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Listar produções por receita
     */
    public function listarPorReceita($receita_id, $limite = 50) {
        $query = "SELECT p.*, r.nome as receita_nome 
                  FROM " . $this->table_name . " p
                  INNER JOIN " . $this->receitas_table . " r ON p.receita_id = r.id
                  WHERE p.receita_id = :receita_id
                  ORDER BY p.data_producao DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':receita_id', $receita_id);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Buscar por ID
     */
    public function buscarPorId($id) {
        $query = "SELECT p.*, r.nome as receita_nome 
                  FROM " . $this->table_name . " p
                  INNER JOIN " . $this->receitas_table . " r ON p.receita_id = r.id
                  WHERE p.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->receita_id = $row['receita_id'];
            $this->quantidade_produzida = $row['quantidade_produzida'];
            $this->data_producao = $row['data_producao'];
            $this->custo_total = $row['custo_total'];
            $this->observacoes = $row['observacoes'];
            return true;
        }
        return false;
    }

    /**
     * Obter estatísticas de produções
     */
    public function obterEstatisticas($receita_id = null) {
        $where = $receita_id ? "WHERE p.receita_id = :receita_id" : "";
        $query = "SELECT 
                    COUNT(*) as total_producoes,
                    SUM(quantidade_produzida) as quantidade_total_produzida,
                    SUM(custo_total) as custo_total_producoes,
                    AVG(custo_total) as custo_medio_producao
                  FROM " . $this->table_name . " p " . $where;
        
        $stmt = $this->conn->prepare($query);
        if($receita_id) {
            $stmt->bindParam(':receita_id', $receita_id);
        }
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

