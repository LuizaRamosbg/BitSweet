-- Migration: adicionar cliente_id a encomendas e criar chave estrangeira
ALTER TABLE encomendas
    ADD COLUMN cliente_id INT NULL AFTER id,
    ADD CONSTRAINT fk_encomendas_cliente_id FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;
