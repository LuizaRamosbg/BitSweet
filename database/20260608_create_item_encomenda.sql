-- Migration: criar a tabela item_encomenda e migrar dados existentes de encomendas
CREATE TABLE item_encomenda (
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

INSERT INTO item_encomenda (encomenda_id, receita_id, quantidade_vendida, preco_unitario, preco_total)
SELECT id, receita_id, quantidade, preco_unitario, preco_total FROM encomendas;
