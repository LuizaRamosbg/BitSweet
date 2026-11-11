-- BitSweet - esquema do banco (SQLite)
-- Valores monetários em CENTAVOS. Quantidades sempre na unidade base (g, ml, un).

CREATE TABLE IF NOT EXISTS insumos (                       -- RF-01 / RF-04 / RF-05
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    nome                 TEXT    NOT NULL COLLATE NOCASE,
    marca                TEXT    NOT NULL DEFAULT '' COLLATE NOCASE,   -- mesmo nome só com marca diferente
    tipo                 TEXT    NOT NULL DEFAULT 'ingrediente'
                         CHECK (tipo IN ('ingrediente', 'embalagem', 'outro')),
    unidade_base         TEXT    NOT NULL CHECK (unidade_base IN ('g', 'ml', 'un')),
    saldo                REAL    NOT NULL DEFAULT 0 CHECK (saldo >= 0),
    custo_medio_centavos REAL    NOT NULL DEFAULT 0,      -- RF-03: custo por unidade base
    estoque_minimo       REAL    NOT NULL DEFAULT 0 CHECK (estoque_minimo >= 0),
    ativo                INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS compras (                       -- RF-02
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    insumo_id            INTEGER NOT NULL REFERENCES insumos(id),
    data                 TEXT    NOT NULL,
    qtd_embalagens       REAL    NOT NULL CHECK (qtd_embalagens > 0),
    tamanho_embalagem    REAL    NOT NULL CHECK (tamanho_embalagem > 0),
    unidade_embalagem    TEXT    NOT NULL,
    quantidade_base      REAL    NOT NULL,
    preco_total_centavos INTEGER NOT NULL CHECK (preco_total_centavos >= 0),
    fornecedor           TEXT
);

CREATE TABLE IF NOT EXISTS lotes (                         -- lotes com validade (consumo FEFO)
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    insumo_id           INTEGER NOT NULL REFERENCES insumos(id),
    compra_id           INTEGER REFERENCES compras(id),
    codigo              TEXT,                              -- código impresso na embalagem (opcional)
    data_entrada        TEXT    NOT NULL,
    validade            TEXT,                              -- NULL = sem validade (consumido por último)
    quantidade_inicial  REAL    NOT NULL CHECK (quantidade_inicial > 0),
    saldo               REAL    NOT NULL CHECK (saldo >= 0),
    custo_unit_centavos REAL    NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS movimentacoes (                 -- RF-12 / RF-19 (livro-razão do estoque)
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    insumo_id            INTEGER NOT NULL REFERENCES insumos(id),
    data                 TEXT    NOT NULL,
    quantidade           REAL    NOT NULL,                -- + entrada / - saída
    custo_unit_centavos  REAL    NOT NULL DEFAULT 0,
    origem               TEXT    NOT NULL CHECK (origem IN ('compra', 'producao', 'ajuste')),
    referencia_id        INTEGER,
    observacao           TEXT,
    lote_id              INTEGER REFERENCES lotes(id)
);

CREATE TABLE IF NOT EXISTS receitas (                      -- RF-06 / RF-11
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    nome                  TEXT    NOT NULL UNIQUE COLLATE NOCASE,
    rendimento            REAL    NOT NULL CHECK (rendimento > 0),
    unidade_rendimento    TEXT    NOT NULL DEFAULT 'porção',
    margem_lucro          REAL    NOT NULL DEFAULT 100,   -- % de markup sobre o custo
    cmv_alvo              REAL    NOT NULL DEFAULT 35,    -- % do preço que o custo deve representar
    preco_venda_centavos  INTEGER,                         -- preço por porção definido pelo usuário
    ativo                 INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS receita_itens (                 -- RF-07 / RF-10 (embalagens do estoque)
    receita_id INTEGER NOT NULL REFERENCES receitas(id) ON DELETE CASCADE,
    insumo_id  INTEGER NOT NULL REFERENCES insumos(id),
    quantidade REAL    NOT NULL CHECK (quantidade > 0),
    PRIMARY KEY (receita_id, insumo_id)
);

CREATE TABLE IF NOT EXISTS custos_extras (                 -- RF-10 (custos que não passam pelo estoque)
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    receita_id     INTEGER NOT NULL REFERENCES receitas(id) ON DELETE CASCADE,
    descricao      TEXT    NOT NULL,
    valor_centavos INTEGER NOT NULL CHECK (valor_centavos >= 0)   -- por receita (lote)
);

CREATE TABLE IF NOT EXISTS producoes (                     -- RF-12 / RF-20
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    receita_id     INTEGER NOT NULL REFERENCES receitas(id),
    data           TEXT    NOT NULL,
    lotes          REAL    NOT NULL CHECK (lotes > 0),
    porcoes        REAL    NOT NULL,
    custo_centavos INTEGER NOT NULL,
    encomenda_id   INTEGER REFERENCES encomendas(id)
);

CREATE TABLE IF NOT EXISTS clientes (                      -- RF-13
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    nome     TEXT NOT NULL,
    telefone TEXT,
    email    TEXT,
    endereco TEXT,
    observacoes TEXT,
    aniversario_dia INTEGER CHECK (aniversario_dia BETWEEN 1 AND 31),   -- opcional (dia e mês)
    aniversario_mes INTEGER CHECK (aniversario_mes BETWEEN 1 AND 12)
);

CREATE TABLE IF NOT EXISTS encomendas (                    -- RF-14 / RF-15 / RF-16
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id           INTEGER NOT NULL REFERENCES clientes(id),
    data_pedido          TEXT    NOT NULL,
    data_entrega_prevista TEXT,
    data_entregue        TEXT,
    status               TEXT    NOT NULL DEFAULT 'Pendente'
                         CHECK (status IN ('Pendente', 'Em Produção', 'Pago', 'Pronto', 'Entregue', 'Cancelado')),
    status_pagamento     TEXT    NOT NULL DEFAULT 'Pendente'
                         CHECK (status_pagamento IN ('Pendente', 'Parcial', 'Total')),
    valor_total_centavos INTEGER NOT NULL DEFAULT 0,
    valor_pago_centavos  INTEGER NOT NULL DEFAULT 0,
    estoque_baixado      INTEGER NOT NULL DEFAULT 0,
    observacoes          TEXT
);

CREATE TABLE IF NOT EXISTS encomenda_itens (               -- RF-14 (preço e custo congelados no pedido)
    encomenda_id        INTEGER NOT NULL REFERENCES encomendas(id) ON DELETE CASCADE,
    receita_id          INTEGER NOT NULL REFERENCES receitas(id),
    quantidade          REAL    NOT NULL CHECK (quantidade > 0),   -- em porções
    preco_unit_centavos INTEGER NOT NULL,
    custo_unit_centavos INTEGER NOT NULL,
    PRIMARY KEY (encomenda_id, receita_id)
);

CREATE TABLE IF NOT EXISTS pagamentos (                    -- RF-16
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    encomenda_id   INTEGER NOT NULL REFERENCES encomendas(id) ON DELETE CASCADE,
    data           TEXT    NOT NULL,
    valor_centavos INTEGER NOT NULL CHECK (valor_centavos > 0),
    forma          TEXT
);

CREATE TABLE IF NOT EXISTS usuarios (                      -- login do sistema
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario    TEXT    NOT NULL UNIQUE COLLATE NOCASE,
    senha_hash TEXT    NOT NULL,
    criado_em  TEXT    NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS ux_insumo_nome_marca ON insumos (nome, marca);
CREATE INDEX IF NOT EXISTS ix_lotes_fefo       ON lotes (insumo_id, validade, data_entrada);
CREATE INDEX IF NOT EXISTS ix_mov_insumo_data ON movimentacoes (insumo_id, data);
CREATE INDEX IF NOT EXISTS ix_enc_cliente     ON encomendas (cliente_id, data_pedido);
CREATE INDEX IF NOT EXISTS ix_enc_data        ON encomendas (data_pedido);
