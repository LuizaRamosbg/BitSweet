class ReceitaRepository:
    """Acesso a dados de receitas (ficha técnica), custos extras e produções."""

    def __init__(self, conn):
        self.conn = conn

    def listar(self, incluir_inativas=False):
        sql = "SELECT * FROM receitas" + ("" if incluir_inativas else " WHERE ativo = 1") + " ORDER BY nome"
        return self.conn.execute(sql).fetchall()

    def buscar(self, receita_id):
        return self.conn.execute("SELECT * FROM receitas WHERE id = ?", (receita_id,)).fetchone()

    def buscar_por_nome(self, nome):
        return self.conn.execute("SELECT * FROM receitas WHERE nome = ?", (nome,)).fetchone()

    def inserir(self, d):
        cur = self.conn.execute(
            """INSERT INTO receitas (nome, rendimento, unidade_rendimento, margem_lucro, cmv_alvo,
                                     preco_venda_centavos) VALUES (?, ?, ?, ?, ?, ?)""",
            (d["nome"], d["rendimento"], d["unidade_rendimento"], d["margem_lucro"], d["cmv_alvo"],
             d["preco_venda_centavos"]))
        return cur.lastrowid

    def atualizar(self, receita_id, d):
        self.conn.execute(
            """UPDATE receitas SET nome = ?, rendimento = ?, unidade_rendimento = ?, margem_lucro = ?,
                      cmv_alvo = ?, preco_venda_centavos = ? WHERE id = ?""",
            (d["nome"], d["rendimento"], d["unidade_rendimento"], d["margem_lucro"], d["cmv_alvo"],
             d["preco_venda_centavos"], receita_id))

    def definir_ativo(self, receita_id, ativo):
        self.conn.execute("UPDATE receitas SET ativo = ? WHERE id = ?", (1 if ativo else 0, receita_id))

    def excluir(self, receita_id):
        self.conn.execute("DELETE FROM receitas WHERE id = ?", (receita_id,))

    def em_uso(self, receita_id):
        q = """SELECT (SELECT COUNT(*) FROM producoes WHERE receita_id = :r)
                    + (SELECT COUNT(*) FROM encomenda_itens WHERE receita_id = :r)"""
        return self.conn.execute(q, {"r": receita_id}).fetchone()[0] > 0

    # --- itens (insumos da ficha técnica) ---
    def substituir_itens(self, receita_id, itens):
        self.conn.execute("DELETE FROM receita_itens WHERE receita_id = ?", (receita_id,))
        self.conn.executemany("INSERT INTO receita_itens (receita_id, insumo_id, quantidade) VALUES (?, ?, ?)",
                              [(receita_id, i, q) for i, q in itens])

    def itens(self, receita_id):
        return self.conn.execute(
            """SELECT ri.insumo_id, ri.quantidade, i.nome, i.tipo, i.unidade_base, i.saldo,
                      (SELECT COALESCE(SUM(l.saldo), 0) FROM lotes l WHERE l.insumo_id = i.id
                         AND (l.validade IS NULL OR l.validade >= date('now', 'localtime'))) AS disponivel,
                      i.custo_medio_centavos
               FROM receita_itens ri JOIN insumos i ON i.id = ri.insumo_id
               WHERE ri.receita_id = ? ORDER BY i.tipo, i.nome""", (receita_id,)).fetchall()

    # --- custos extras ---
    def substituir_extras(self, receita_id, extras):
        self.conn.execute("DELETE FROM custos_extras WHERE receita_id = ?", (receita_id,))
        self.conn.executemany("INSERT INTO custos_extras (receita_id, descricao, valor_centavos) VALUES (?, ?, ?)",
                              [(receita_id, d, v) for d, v in extras])

    def extras(self, receita_id):
        return self.conn.execute("SELECT * FROM custos_extras WHERE receita_id = ? ORDER BY id",
                                 (receita_id,)).fetchall()

    # --- produções ---
    def inserir_producao(self, receita_id, data, lotes, porcoes, custo_centavos, encomenda_id=None):
        cur = self.conn.execute(
            """INSERT INTO producoes (receita_id, data, lotes, porcoes, custo_centavos, encomenda_id)
               VALUES (?, ?, ?, ?, ?, ?)""", (receita_id, data, lotes, porcoes, custo_centavos, encomenda_id))
        return cur.lastrowid

    def producoes_recentes(self, limite=20):
        return self.conn.execute(
            """SELECT p.*, r.nome, r.unidade_rendimento FROM producoes p JOIN receitas r ON r.id = p.receita_id
               ORDER BY p.data DESC, p.id DESC LIMIT ?""", (limite,)).fetchall()
