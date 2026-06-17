-- Schema definitivo do banco de dados para implementação e testes
CREATE DATABASE IF NOT EXISTS confeitaria_db;
USE confeitaria_db;

-- Tabela de Insumos
CREATE TABLE IF NOT EXISTS insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    unidade_compra ENUM('kg', 'g', 'L', 'ml', 'un', 'cx', 'pct') NOT NULL,
    fator_conversao DECIMAL(10,3) DEFAULT 1.000,
    estoque_atual DECIMAL(10,3) DEFAULT 0,
    estoque_minimo DECIMAL(10,3) DEFAULT 0,
    custo_unitario_atual DECIMAL(10,2) DEFAULT 0,
    categoria VARCHAR(100),
    fornecedor VARCHAR(255),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Compras
CREATE TABLE IF NOT EXISTS compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insumo_id INT NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL,
    preco_total DECIMAL(10,2) NOT NULL,
    custo_unitario DECIMAL(10,2) NOT NULL,
    fornecedor VARCHAR(255),
    data_compra DATE NOT NULL,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Histórico de Estoque
CREATE TABLE IF NOT EXISTS historico_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insumo_id INT NOT NULL,
    tipo_movimentacao ENUM('entrada', 'saida', 'ajuste', 'desperdicio') NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL,
    custo_unitario DECIMAL(10,2),
    motivo VARCHAR(255),
    referencia_id INT,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Alertas de Estoque
CREATE TABLE IF NOT EXISTS alertas_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insumo_id INT NOT NULL,
    tipo_alerta ENUM('estoque_minimo', 'estoque_zerado') NOT NULL,
    quantidade_atual DECIMAL(10,3) NOT NULL,
    quantidade_minima DECIMAL(10,3) NOT NULL,
    data_alerta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visualizado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Receitas
CREATE TABLE IF NOT EXISTS receitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    categoria VARCHAR(100),
    rendimento DECIMAL(10,2) NOT NULL DEFAULT 1,
    unidade_rendimento VARCHAR(50) DEFAULT 'un',
    tempo_preparo INT DEFAULT 0,
    dificuldade ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
    instrucoes TEXT,
    custo_total DECIMAL(10,2) DEFAULT 0,
    preco_venda_sugerido DECIMAL(10,2) DEFAULT 0,
    margem_lucro DECIMAL(5,2) DEFAULT 30.00,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Ingredientes das Receitas
CREATE TABLE IF NOT EXISTS receita_ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receita_id INT NOT NULL,
    insumo_id INT NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL,
    unidade_uso ENUM('kg', 'g', 'L', 'ml', 'un', 'cx', 'pct') NOT NULL,
    observacoes TEXT,
    ordem INT DEFAULT 0,
    FOREIGN KEY (receita_id) REFERENCES receitas(id) ON DELETE CASCADE,
    FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Produção
CREATE TABLE IF NOT EXISTS producoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receita_id INT NOT NULL,
    quantidade_produzida DECIMAL(10,2) NOT NULL,
    data_producao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    custo_total DECIMAL(10,2) NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (receita_id) REFERENCES receitas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Controle de Validade de Insumos
CREATE TABLE IF NOT EXISTS controle_validade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insumo_id INT NOT NULL,
    lote VARCHAR(100),
    quantidade_lote DECIMAL(10,3) NOT NULL,
    data_fabricacao DATE,
    data_validade DATE NOT NULL,
    quantidade_atual DECIMAL(10,3) NOT NULL,
    status ENUM('valido', 'proximo_vencer', 'vencido') DEFAULT 'valido',
    observacoes TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Alertas de Validade
CREATE TABLE IF NOT EXISTS alertas_validade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    controle_validade_id INT NOT NULL,
    insumo_id INT NOT NULL,
    tipo_alerta ENUM('proximo_vencer', 'vencido') NOT NULL,
    dias_para_vencer INT,
    data_alerta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visualizado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (controle_validade_id) REFERENCES controle_validade(id) ON DELETE CASCADE,
    FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Desperdício
CREATE TABLE IF NOT EXISTS desperdicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insumo_id INT NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL,
    motivo ENUM('validade', 'quebra', 'consumo_interno', 'outro') NOT NULL,
    descricao TEXT,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registrado_por VARCHAR(255),
    FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(255),
    endereco TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Encomendas
CREATE TABLE IF NOT EXISTS encomendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NULL,
    cliente_nome VARCHAR(255) NOT NULL,
    cliente_telefone VARCHAR(20),
    cliente_email VARCHAR(255),
    receita_id INT NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    preco_total DECIMAL(10,2) NOT NULL,
    data_entrega DATE NOT NULL,
    status ENUM('pendente', 'em_producao', 'pronta', 'entregue', 'cancelada') DEFAULT 'pendente',
    pago TINYINT(1) DEFAULT 0,
    baixa_realizada TINYINT(1) NOT NULL DEFAULT 0,
    observacoes TEXT,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (receita_id) REFERENCES receitas(id) ON DELETE RESTRICT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Itens de Encomenda
CREATE TABLE IF NOT EXISTS item_encomenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    encomenda_id INT NOT NULL,
    receita_id INT NOT NULL,
    quantidade_vendida DECIMAL(10,2) NOT NULL,
    preco_unitario DECIMAL(10,2) DEFAULT NULL,
    preco_total DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (encomenda_id) REFERENCES encomendas(id) ON DELETE CASCADE,
    FOREIGN KEY (receita_id) REFERENCES receitas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Custo Extra
CREATE TABLE IF NOT EXISTS custo_extra (
    id INT NOT NULL AUTO_INCREMENT,
    id_receita INT NOT NULL,
    descricao VARCHAR(150) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY id_receita_idx (id_receita),
    FOREIGN KEY (id_receita) REFERENCES receitas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Índices principais
CREATE INDEX IF NOT EXISTS idx_insumos_nome ON insumos(nome);
CREATE INDEX IF NOT EXISTS idx_insumos_categoria ON insumos(categoria);
CREATE INDEX IF NOT EXISTS idx_insumos_ativo ON insumos(ativo);
CREATE INDEX IF NOT EXISTS idx_compras_insumo ON compras(insumo_id);
CREATE INDEX IF NOT EXISTS idx_compras_data ON compras(data_compra);
CREATE INDEX IF NOT EXISTS idx_historico_insumo ON historico_estoque(insumo_id);
CREATE INDEX IF NOT EXISTS idx_historico_data ON historico_estoque(data_movimentacao);
CREATE INDEX IF NOT EXISTS idx_alertas_visualizado ON alertas_estoque(visualizado);
CREATE INDEX IF NOT EXISTS idx_receitas_categoria ON receitas(categoria);
CREATE INDEX IF NOT EXISTS idx_receitas_ativo ON receitas(ativo);
CREATE INDEX IF NOT EXISTS idx_receita_ingredientes_receita ON receita_ingredientes(receita_id);
CREATE INDEX IF NOT EXISTS idx_receita_ingredientes_insumo ON receita_ingredientes(insumo_id);
CREATE INDEX IF NOT EXISTS idx_producoes_receita ON producoes(receita_id);
CREATE INDEX IF NOT EXISTS idx_producoes_data ON producoes(data_producao);
CREATE INDEX IF NOT EXISTS idx_controle_validade_insumo ON controle_validade(insumo_id);
CREATE INDEX IF NOT EXISTS idx_controle_validade_status ON controle_validade(status);
CREATE INDEX IF NOT EXISTS idx_controle_validade_data_validade ON controle_validade(data_validade);
CREATE INDEX IF NOT EXISTS idx_alertas_validade_visualizado ON alertas_validade(visualizado);
CREATE INDEX IF NOT EXISTS idx_desperdicios_insumo ON desperdicios(insumo_id);
CREATE INDEX IF NOT EXISTS idx_desperdicios_data ON desperdicios(data_registro);
CREATE INDEX IF NOT EXISTS idx_desperdicios_motivo ON desperdicios(motivo);
CREATE INDEX IF NOT EXISTS idx_encomendas_status ON encomendas(status);
CREATE INDEX IF NOT EXISTS idx_encomendas_data_entrega ON encomendas(data_entrega);
CREATE INDEX IF NOT EXISTS idx_encomendas_receita ON encomendas(receita_id);
CREATE INDEX IF NOT EXISTS idx_encomendas_data_pedido ON encomendas(data_pedido);
CREATE INDEX IF NOT EXISTS idx_item_encomenda_encomenda_id ON item_encomenda(encomenda_id);
CREATE INDEX IF NOT EXISTS idx_item_encomenda_receita_id ON item_encomenda(receita_id);
CREATE INDEX IF NOT EXISTS idx_clientes_nome ON clientes(nome);
