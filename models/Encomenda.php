<?php
/**
 * Modelo para gerenciar Encomendas
 * Sistema de Gestão da Doceria
 */

require_once __DIR__ . '/../config/database.php';

class Encomenda {
    private $conn;
    private $table_name = "encomendas";
    private $receitas_table = "receitas";
    private $items_table = "item_encomenda";

    public $id;
    public $cliente_id;
    public $cliente_nome;
    public $cliente_telefone;
    public $cliente_email;
    public $itens = [];
    public $receita_id;
    public $quantidade;
    public $preco_unitario;
    public $preco_total;
    public $data_entrega;
    public $status;
    public $observacoes;
    public $pago;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function buscarItens($encomenda_id) {
        $query = "SELECT ie.*, r.nome as receita_nome, r.preco_venda_sugerido
                  FROM " . $this->items_table . " ie
                  INNER JOIN receitas r ON ie.receita_id = r.id
                  WHERE ie.encomenda_id = :encomenda_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':encomenda_id', $encomenda_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function criarItens($encomenda_id, $itens) {
        if (empty($itens) || !is_array($itens)) {
            return true;
        }

        $query = "INSERT INTO " . $this->items_table . " (encomenda_id, receita_id, quantidade_vendida, preco_unitario, preco_total)
                  VALUES (:encomenda_id, :receita_id, :quantidade_vendida, :preco_unitario, :preco_total)";

        $stmt = $this->conn->prepare($query);

        foreach ($itens as $item) {
            $receita_id = isset($item['receita_id']) ? (int)$item['receita_id'] : 0;
            $quantidade_vendida = isset($item['quantidade_vendida']) ? (float)$item['quantidade_vendida'] : 0;
            if ($receita_id <= 0 || $quantidade_vendida <= 0) {
                continue;
            }

            $preco_unitario = isset($item['preco_unitario']) ? (float)$item['preco_unitario'] : null;
            $preco_total = isset($item['preco_total']) ? (float)$item['preco_total'] : null;
            if ($preco_total === null && $preco_unitario !== null) {
                $preco_total = $preco_unitario * $quantidade_vendida;
            }

            $stmt->bindValue(':encomenda_id', $encomenda_id, PDO::PARAM_INT);
            $stmt->bindValue(':receita_id', $receita_id, PDO::PARAM_INT);
            $stmt->bindValue(':quantidade_vendida', $quantidade_vendida);
            $stmt->bindValue(':preco_unitario', $preco_unitario);
            $stmt->bindValue(':preco_total', $preco_total);
            $stmt->execute();
        }

        return true;
    }

    private function atualizarItens($encomenda_id, $itens) {
        $query = "DELETE FROM " . $this->items_table . " WHERE encomenda_id = :encomenda_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':encomenda_id', $encomenda_id, PDO::PARAM_INT);
        $stmt->execute();

        return $this->criarItens($encomenda_id, $itens);
    }

    private function calcularTotalItens() {
        if (empty($this->itens) || !is_array($this->itens)) {
            return null;
        }

        $total = 0;
        foreach ($this->itens as $item) {
            $quantidade = isset($item['quantidade_vendida']) ? (float)$item['quantidade_vendida'] : 0;
            $preco_unitario = isset($item['preco_unitario']) ? (float)$item['preco_unitario'] : null;
            $preco_total = isset($item['preco_total']) ? (float)$item['preco_total'] : null;
            if ($preco_total !== null) {
                $total += $preco_total;
            } elseif ($preco_unitario !== null) {
                $total += $preco_unitario * $quantidade;
            }
        }

        return $total;
    }

    /**
     * Criar nova encomenda
     */
    public function criar() {
        // Calcular preço total com base nos itens, quando fornecidos
        if (!empty($this->itens)) {
            $totalItens = $this->calcularTotalItens();
            if ($totalItens !== null) {
                $this->preco_total = $totalItens;
            }
        } else {
            $this->preco_total = $this->preco_unitario * $this->quantidade;
        }

        $query = "INSERT INTO " . $this->table_name . " 
              (cliente_id, cliente_nome, cliente_telefone, cliente_email, receita_id, quantidade, 
               preco_unitario, preco_total, data_entrega, status, pago, observacoes) 
              VALUES (:cliente_id, :cliente_nome, :cliente_telefone, :cliente_email, :receita_id, :quantidade, 
                  :preco_unitario, :preco_total, :data_entrega, :status, :pago, :observacoes)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->cliente_nome = htmlspecialchars(strip_tags($this->cliente_nome));
        $this->cliente_telefone = htmlspecialchars(strip_tags($this->cliente_telefone));
        $this->cliente_email = htmlspecialchars(strip_tags($this->cliente_email));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));

        // Bind dos parâmetros
        if (!empty($this->cliente_id)) {
            $stmt->bindValue(':cliente_id', $this->cliente_id, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':cliente_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(':cliente_nome', $this->cliente_nome);
        $stmt->bindParam(':cliente_telefone', $this->cliente_telefone);
        $stmt->bindParam(':cliente_email', $this->cliente_email);
        $stmt->bindParam(':receita_id', $this->receita_id);
        $stmt->bindParam(':quantidade', $this->quantidade);
        $stmt->bindParam(':preco_unitario', $this->preco_unitario);
        $stmt->bindParam(':preco_total', $this->preco_total);
        $stmt->bindParam(':data_entrega', $this->data_entrega);
        $stmt->bindParam(':status', $this->status);
        $pago_val = isset($this->pago) ? (int)$this->pago : 0;
        $stmt->bindParam(':pago', $pago_val, PDO::PARAM_INT);
        $stmt->bindParam(':observacoes', $this->observacoes);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            if (!empty($this->itens)) {
                $this->criarItens($this->id, $this->itens);
            } else {
                $this->criarItens($this->id, [[
                    'receita_id' => $this->receita_id,
                    'quantidade_vendida' => $this->quantidade,
                    'preco_unitario' => $this->preco_unitario,
                    'preco_total' => $this->preco_total
                ]]);
            }
            return true;
        }
        return false;
    }

    /**
     * Listar todas as encomendas
     */
    public function listar($limite = 50) {
        $query = "SELECT e.*, r.nome as receita_nome, r.categoria as receita_categoria, 
                  c.id as cliente_id, COALESCE(c.nome, e.cliente_nome) as cliente_nome, 
                  COALESCE(c.telefone, e.cliente_telefone) as cliente_telefone, 
                  COALESCE(c.email, e.cliente_email) as cliente_email 
                  FROM " . $this->table_name . " e
                  LEFT JOIN clientes c ON e.cliente_id = c.id
                  INNER JOIN " . $this->receitas_table . " r ON e.receita_id = r.id
                  ORDER BY e.data_entrega ASC, e.data_pedido DESC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Listar encomendas por status
     */
    public function listarPorStatus($status, $limite = 50) {
        $query = "SELECT e.*, r.nome as receita_nome, r.categoria as receita_categoria, 
                  c.id as cliente_id, COALESCE(c.nome, e.cliente_nome) as cliente_nome, 
                  COALESCE(c.telefone, e.cliente_telefone) as cliente_telefone, 
                  COALESCE(c.email, e.cliente_email) as cliente_email 
                  FROM " . $this->table_name . " e
                  LEFT JOIN clientes c ON e.cliente_id = c.id
                  INNER JOIN " . $this->receitas_table . " r ON e.receita_id = r.id
                  WHERE e.status = :status
                  ORDER BY e.data_entrega ASC
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Listar encomendas por data de entrega
     */
    public function listarPorDataEntrega($data_inicio, $data_fim) {
        $query = "SELECT e.*, r.nome as receita_nome, r.categoria as receita_categoria, 
                  c.id as cliente_id, COALESCE(c.nome, e.cliente_nome) as cliente_nome, 
                  COALESCE(c.telefone, e.cliente_telefone) as cliente_telefone, 
                  COALESCE(c.email, e.cliente_email) as cliente_email 
                  FROM " . $this->table_name . " e
                  LEFT JOIN clientes c ON e.cliente_id = c.id
                  INNER JOIN " . $this->receitas_table . " r ON e.receita_id = r.id
                  WHERE e.data_entrega BETWEEN :data_inicio AND :data_fim
                  ORDER BY e.data_entrega ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Buscar encomenda por ID
     */
    public function buscarPorId($id) {
        $query = "SELECT e.*, r.nome as receita_nome, r.categoria as receita_categoria, 
                  c.id as cliente_id, COALESCE(c.nome, e.cliente_nome) as cliente_nome, 
                  COALESCE(c.telefone, e.cliente_telefone) as cliente_telefone, 
                  COALESCE(c.email, e.cliente_email) as cliente_email 
                  FROM " . $this->table_name . " e
                  LEFT JOIN clientes c ON e.cliente_id = c.id
                  INNER JOIN " . $this->receitas_table . " r ON e.receita_id = r.id
                  WHERE e.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->cliente_id = $row['cliente_id'] ?? null;
            $this->cliente_nome = $row['cliente_nome'];
            $this->cliente_telefone = $row['cliente_telefone'];
            $this->cliente_email = $row['cliente_email'];
            $this->receita_id = $row['receita_id'];
            $this->quantidade = $row['quantidade'];
            $this->preco_unitario = $row['preco_unitario'];
            $this->preco_total = $row['preco_total'];
            $this->data_entrega = $row['data_entrega'];
            $this->pago = isset($row['pago']) ? (int)$row['pago'] : 0;
            $this->status = $row['status'];
            $this->observacoes = $row['observacoes'];
            $this->itens = $this->buscarItens($this->id);
            return true;
        }
        return false;
    }

    /**
     * Atualizar encomenda
     */
    public function atualizar() {
        // Recalcular preço total com base nos itens, quando fornecidos
        if (!empty($this->itens)) {
            $totalItens = $this->calcularTotalItens();
            if ($totalItens !== null) {
                $this->preco_total = $totalItens;
            }
        } elseif($this->preco_unitario && $this->quantidade) {
            $this->preco_total = $this->preco_unitario * $this->quantidade;
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET cliente_id = :cliente_id, cliente_nome = :cliente_nome, cliente_telefone = :cliente_telefone,
                      cliente_email = :cliente_email, receita_id = :receita_id,
                      quantidade = :quantidade, preco_unitario = :preco_unitario,
                      preco_total = :preco_total, data_entrega = :data_entrega,
                      status = :status, pago = :pago, observacoes = :observacoes
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->cliente_nome = htmlspecialchars(strip_tags($this->cliente_nome));
        $this->cliente_telefone = htmlspecialchars(strip_tags($this->cliente_telefone));
        $this->cliente_email = htmlspecialchars(strip_tags($this->cliente_email));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));

        // Bind dos parâmetros
        if (!empty($this->cliente_id)) {
            $stmt->bindValue(':cliente_id', $this->cliente_id, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':cliente_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(':cliente_nome', $this->cliente_nome);
        $stmt->bindParam(':cliente_telefone', $this->cliente_telefone);
        $stmt->bindParam(':cliente_email', $this->cliente_email);
        $stmt->bindParam(':receita_id', $this->receita_id);
        $stmt->bindParam(':quantidade', $this->quantidade);
        $stmt->bindParam(':preco_unitario', $this->preco_unitario);
        $stmt->bindParam(':preco_total', $this->preco_total);
        $stmt->bindParam(':data_entrega', $this->data_entrega);
        $stmt->bindParam(':status', $this->status);
        $pago_val = isset($this->pago) ? (int)$this->pago : 0;
        $stmt->bindParam(':pago', $pago_val, PDO::PARAM_INT);
        $stmt->bindParam(':observacoes', $this->observacoes);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            if (!empty($this->itens)) {
                $this->atualizarItens($this->id, $this->itens);
            } elseif ($this->receita_id && $this->quantidade) {
                $this->atualizarItens($this->id, [[
                    'receita_id' => $this->receita_id,
                    'quantidade_vendida' => $this->quantidade,
                    'preco_unitario' => $this->preco_unitario,
                    'preco_total' => $this->preco_total
                ]]);
            }
            return true;
        }
        return false;
    }

    /**
     * Atualizar status da encomenda
     */
    public function atualizarStatus($novo_status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $novo_status);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            $this->status = $novo_status;
            return true;
        }
        return false;
    }

    /**
     * Excluir encomenda
     */
    public function excluir() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Obter estatísticas de encomendas
     */
    public function obterEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total_encomendas,
                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as encomendas_pendentes,
                    SUM(CASE WHEN status = 'em_producao' THEN 1 ELSE 0 END) as encomendas_em_producao,
                    SUM(CASE WHEN status = 'pronta' THEN 1 ELSE 0 END) as encomendas_prontas,
                    SUM(CASE WHEN status = 'entregue' THEN 1 ELSE 0 END) as encomendas_entregues,
                    SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) as encomendas_canceladas,
                    SUM(preco_total) as valor_total,
                    SUM(CASE WHEN status = 'entregue' THEN preco_total ELSE 0 END) as valor_entregue
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Listar encomendas pendentes para hoje
     */
    public function listarPendentesHoje() {
        $hoje = date('Y-m-d');
        $query = "SELECT e.*, r.nome as receita_nome, 
                  c.id as cliente_id, COALESCE(c.nome, e.cliente_nome) as cliente_nome, 
                  COALESCE(c.telefone, e.cliente_telefone) as cliente_telefone, 
                  COALESCE(c.email, e.cliente_email) as cliente_email 
                  FROM " . $this->table_name . " e
                  LEFT JOIN clientes c ON e.cliente_id = c.id
                  INNER JOIN " . $this->receitas_table . " r ON e.receita_id = r.id
                  WHERE e.data_entrega = :hoje 
                  AND e.status IN ('pendente', 'em_producao', 'pronta')
                  ORDER BY e.data_entrega ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Verificar disponibilidade de insumos para a receita desta encomenda
     * Retorna array de itens faltantes: [ ['insumo_id'=>int,'nome'=>string,'required'=>float,'available'=>float,'missing'=>float], ... ]
     */
    public function verificarDisponibilidade($receita_id = null, $quantidade = 1) {
        $receita_id = $receita_id ?? $this->receita_id;
        $quantidade = (float) $quantidade;

        $query = "SELECT ri.*, i.nome as insumo_nome, i.unidade_compra, i.fator_conversao, i.custo_unitario_atual
                  FROM receita_ingredientes ri
                  INNER JOIN insumos i ON ri.insumo_id = i.id
                  WHERE ri.receita_id = :receita_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':receita_id', $receita_id);
        $stmt->execute();

        $faltantes = [];

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // quantidade necessária total considerando rendimento da receita
            $quantidade_necessaria = (float) $row['quantidade'] * $quantidade;

            // converter para unidade de compra (assume fator_conversao similar ao usado em Receitas)
            $unidade_uso = $row['unidade_uso'] ?? $row['unidade_medida'] ?? '';
            $unidade_compra = $row['unidade_compra'] ?? '';
            $fator_conversao = $row['fator_conversao'] ?? 1.0;

            $quantidade_convertida = $this->converterUnidadeSimple($quantidade_necessaria, $unidade_uso, $unidade_compra, $fator_conversao);

            // Disponível: soma de lotes válidos + estoque_atual
            $query_lotes = "SELECT SUM(quantidade_atual) as disponivel FROM controle_validade WHERE insumo_id = :insumo_id AND status != 'vencido'";
            $stmt_l = $this->conn->prepare($query_lotes);
            $stmt_l->bindParam(':insumo_id', $row['insumo_id']);
            $stmt_l->execute();
            $res_l = $stmt_l->fetch(PDO::FETCH_ASSOC);
            $disponivel_lotes = (float) ($res_l['disponivel'] ?? 0);

            $query_insumo = "SELECT estoque_atual FROM insumos WHERE id = :insumo_id";
            $stmt_i = $this->conn->prepare($query_insumo);
            $stmt_i->bindParam(':insumo_id', $row['insumo_id']);
            $stmt_i->execute();
            $res_i = $stmt_i->fetch(PDO::FETCH_ASSOC);
            $estoque_geral = (float) ($res_i['estoque_atual'] ?? 0);

            $total_available = $disponivel_lotes + $estoque_geral;

            if ($total_available < $quantidade_convertida) {
                $faltantes[] = [
                    'insumo_id' => (int) $row['insumo_id'],
                    'nome' => $row['insumo_nome'] ?? 'desconhecido',
                    'required' => $quantidade_convertida,
                    'available' => $total_available,
                    'missing' => $quantidade_convertida - $total_available,
                    'unidade' => $unidade_compra
                ];
            }
        }

        return $faltantes;
    }

    private function converterUnidadeSimple($quantidade, $unidade_origem, $unidade_destino, $fator_conversao) {
        if($unidade_origem == $unidade_destino) return $quantidade;
        if($fator_conversao != 1.0) {
            if(in_array($unidade_origem, ['kg','L']) && in_array($unidade_destino, ['g','ml'])) {
                return $quantidade * $fator_conversao;
            } elseif(in_array($unidade_origem, ['g','ml']) && in_array($unidade_destino, ['kg','L'])) {
                return $quantidade / $fator_conversao;
            }
        }
        $conversoes = [
            'kg' => ['g' => 1000],
            'g' => ['kg' => 0.001],
            'L' => ['ml' => 1000],
            'ml' => ['L' => 0.001]
        ];
        if(isset($conversoes[$unidade_origem][$unidade_destino])) {
            return $quantidade * $conversoes[$unidade_origem][$unidade_destino];
        }
        return $quantidade;
    }
}
?>

