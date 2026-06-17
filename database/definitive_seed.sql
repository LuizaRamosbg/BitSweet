-- Dados iniciais para testes do sistema PHP_Confeitaria
CREATE DATABASE IF NOT EXISTS confeitaria_db;
USE confeitaria_db;

SET FOREIGN_KEY_CHECKS = 0;

-- Insumos de exemplo
INSERT INTO insumos (nome, descricao, unidade_compra, fator_conversao, estoque_atual, estoque_minimo, custo_unitario_atual, categoria, fornecedor) VALUES
('Açúcar', 'Açúcar refinado para confeitaria', 'kg', 1000.000, 50.000, 10.000, 4.50, 'Ingredientes Básicos', 'Distribuidora ABC'),
('Farinha de Trigo', 'Farinha de trigo especial para bolos', 'kg', 1000.000, 25.000, 5.000, 3.80, 'Ingredientes Básicos', 'Moinho XYZ'),
('Ovos', 'Ovos frescos tipo A', 'un', 1.000, 100.000, 20.000, 0.35, 'Ingredientes Básicos', 'Granja São José'),
('Manteiga', 'Manteiga sem sal', 'kg', 1000.000, 8.000, 2.000, 12.50, 'Laticínios', 'Laticínio Central'),
('Chocolate em Pó', 'Cacau em pó 100%', 'kg', 1000.000, 5.000, 1.000, 18.90, 'Chocolates', 'Cacau Brasil'),
('Fermento', 'Fermento químico', 'kg', 1000.000, 2.000, 0.500, 8.90, 'Ingredientes Básicos', 'Distribuidora ABC'),
('Leite', 'Leite integral', 'L', 1000.000, 20.000, 5.000, 3.20, 'Laticínios', 'Laticínio Central'),
('Baunilha', 'Essência de baunilha', 'ml', 1.000, 500.000, 50.000, 0.15, 'Aromatizantes', 'Distribuidora ABC');

-- Clientes de exemplo
INSERT INTO clientes (nome, telefone, email, endereco) VALUES
('Maria Silva', '(11) 91234-5678', 'maria@example.com', 'Rua das Flores, 123'),
('Padaria Central', '(11) 99876-5432', 'contato@padariacentral.com', 'Av. Principal, 456');

-- Receitas de exemplo
INSERT INTO receitas (nome, descricao, categoria, rendimento, unidade_rendimento, tempo_preparo, dificuldade, instrucoes) VALUES
('Bolo de Chocolate', 'Delicioso bolo de chocolate tradicional', 'Bolos', 1.00, 'un', 60, 'facil', 'Misture ingredientes, asse por 40 minutos.'),
('Cupcake de Baunilha', 'Cupcakes fofinhos de baunilha', 'Cupcakes', 12.00, 'un', 45, 'facil', 'Prepare a massa e asse em forminhas por 20 minutos.'),
('Torta de Morango', 'Torta cremosa com morangos frescos', 'Tortas', 1.00, 'un', 90, 'medio', 'Monte a torta com creme e morango e refrigere.');

-- Ingredientes para receitas
INSERT INTO receita_ingredientes (receita_id, insumo_id, quantidade, unidade_uso, ordem) VALUES
(1, 1, 2.000, 'kg', 1),
(1, 2, 3.000, 'kg', 2),
(1, 3, 6.000, 'un', 3),
(1, 4, 0.500, 'kg', 4),
(1, 5, 0.200, 'kg', 5),
(1, 6, 0.050, 'kg', 6),
(1, 7, 1.000, 'L', 7),
(1, 8, 10.000, 'ml', 8),
(2, 1, 1.500, 'kg', 1),
(2, 2, 2.000, 'kg', 2),
(2, 3, 4.000, 'un', 3),
(2, 4, 0.300, 'kg', 4),
(2, 6, 0.030, 'kg', 5),
(2, 7, 0.500, 'L', 6),
(2, 8, 15.000, 'ml', 7),
(3, 1, 1.000, 'kg', 1),
(3, 2, 1.500, 'kg', 2),
(3, 3, 3.000, 'un', 3),
(3, 4, 0.400, 'kg', 4),
(3, 7, 0.800, 'L', 5),
(3, 8, 5.000, 'ml', 6);

-- Compras de exemplo
INSERT INTO compras (insumo_id, quantidade, preco_total, custo_unitario, fornecedor, data_compra, observacoes) VALUES
(1, 10.000, 45.00, 4.50, 'Distribuidora ABC', CURDATE(), 'Reposição de açúcar'),
(2, 15.000, 57.00, 3.80, 'Moinho XYZ', CURDATE(), 'Compra de farinha'),
(3, 30.000, 10.50, 0.35, 'Granja São José', CURDATE(), 'Compra de ovos');

-- Encomendas de exemplo
INSERT INTO encomendas (cliente_id, cliente_nome, cliente_telefone, cliente_email, receita_id, quantidade, preco_unitario, preco_total, data_entrega, status, pago, observacoes) VALUES
(1, 'Maria Silva', '(11) 91234-5678', 'maria@example.com', 1, 1.00, 45.00, 45.00, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'pendente', 0, 'Encomenda para aniversário');

INSERT INTO item_encomenda (encomenda_id, receita_id, quantidade_vendida, preco_unitario, preco_total) VALUES
(1, 1, 1.00, 45.00, 45.00);

SET FOREIGN_KEY_CHECKS = 1;
