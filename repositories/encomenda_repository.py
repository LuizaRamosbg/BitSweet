class ClienteRepository:
    def __init__(self, conn):
        self.conn = conn

    def listar(self, busca=None):
        sql = """SELECT c.*, COUNT(e.id) AS total_encomendas,
                        COALESCE(SUM(CASE WHEN e.status <> 'Cancelado' THEN e.valor_total_centavos END), 0) AS total_gasto,
                        MAX(e.data_pedido) AS ultima_encomenda
                 FROM clientes c LEFT JOIN encomendas e ON e.cliente_id = c.id"""
        params = ()
        if busca:
            sql += " WHERE c.nome LIKE ? OR c.telefone LIKE ? OR c.email LIKE ?"
            params = (f"%{busca}%",) * 3
        sql += " GROUP BY c.id ORDER BY c.nome"
        return self.conn.execute(sql, params).fetchall()

    def buscar(self, cliente_id):
        return self.conn.execute("SELECT * FROM clientes WHERE id = ?", (cliente_id,)).fetchone()

    def inserir(self, d):
        cur = self.conn.execute(
            "INSERT INTO clientes (nome, telefone, email, endereco, observacoes) VALUES (?, ?, ?, ?, ?)",
            (d["nome"], d["telefone"], d["email"], d["endereco"], d["observacoes"]))
        return cur.lastrowid

    def atualizar(self, cliente_id, d):
        self.conn.execute(
            "UPDATE clientes SET nome = ?, telefone = ?, email = ?, endereco = ?, observacoes = ? WHERE id = ?",
            (d["nome"], d["telefone"], d["email"], d["endereco"], d["observacoes"], cliente_id))

    def excluir(self, cliente_id):
        self.conn.execute("DELETE FROM clientes WHERE id = ?", (cliente_id,))

    def tem_encomendas(self, cliente_id):
        return self.conn.execute("SELECT 1 FROM encomendas WHERE cliente_id = ? LIMIT 1", (cliente_id,)).fetchone()


class EncomendaRepository:
    def __init__(self, conn):
        self.conn = conn

    def listar(self, status=None, cliente_id=None):
        sql = """SELECT e.*, c.nome AS cliente_nome FROM encomendas e JOIN clientes c ON c.id = e.cliente_id"""
        cond, params = [], []
        if status:
            cond.append("e.status = ?")
            params.append(status)
        if cliente_id:
            cond.append("e.cliente_id = ?")
            params.append(cliente_id)
        if cond:
            sql += " WHERE " + " AND ".join(cond)
        sql += " ORDER BY COALESCE(e.data_entrega_prevista, e.data_pedido) DESC, e.id DESC"
        return self.conn.execute(sql, params).fetchall()

    def buscar(self, encomenda_id):
        return self.conn.execute(
            """SELECT e.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone
               FROM encomendas e JOIN clientes c ON c.id = e.cliente_id WHERE e.id = ?""",
            (encomenda_id,)).fetchone()

    def inserir(self, cliente_id, data_pedido, data_entrega, total, obs):
        cur = self.conn.execute(
            """INSERT INTO encomendas (cliente_id, data_pedido, data_entrega_prevista, valor_total_centavos,
                                       observacoes) VALUES (?, ?, ?, ?, ?)""",
            (cliente_id, data_pedido, data_entrega, total, obs))
        return cur.lastrowid

    def inserir_item(self, encomenda_id, receita_id, qtd, preco, custo):
        self.conn.execute(
            """INSERT INTO encomenda_itens (encomenda_id, receita_id, quantidade, preco_unit_centavos,
                                            custo_unit_centavos) VALUES (?, ?, ?, ?, ?)""",
            (encomenda_id, receita_id, qtd, preco, custo))

    def itens(self, encomenda_id):
        return self.conn.execute(
            """SELECT ei.*, r.nome, r.unidade_rendimento, r.rendimento,
                      ei.quantidade * ei.preco_unit_centavos AS subtotal
               FROM encomenda_itens ei JOIN receitas r ON r.id = ei.receita_id
               WHERE ei.encomenda_id = ?""", (encomenda_id,)).fetchall()

    def atualizar_status(self, encomenda_id, status, data_entregue=None):
        self.conn.execute("UPDATE encomendas SET status = ?, data_entregue = ? WHERE id = ?",
                          (status, data_entregue, encomenda_id))

    def marcar_estoque_baixado(self, encomenda_id):
        self.conn.execute("UPDATE encomendas SET estoque_baixado = 1 WHERE id = ?", (encomenda_id,))

    def atualizar_pagamento(self, encomenda_id, valor_pago, status_pagamento):
        self.conn.execute("UPDATE encomendas SET valor_pago_centavos = ?, status_pagamento = ? WHERE id = ?",
                          (valor_pago, status_pagamento, encomenda_id))

    def inserir_pagamento(self, encomenda_id, data, valor, forma):
        self.conn.execute("INSERT INTO pagamentos (encomenda_id, data, valor_centavos, forma) VALUES (?, ?, ?, ?)",
                          (encomenda_id, data, valor, forma))

    def pagamentos(self, encomenda_id):
        return self.conn.execute("SELECT * FROM pagamentos WHERE encomenda_id = ? ORDER BY data, id",
                                 (encomenda_id,)).fetchall()
