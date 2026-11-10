class InsumoRepository:
    """Acesso a dados de insumos, compras e movimentações de estoque."""

    def __init__(self, conn):
        self.conn = conn

    # --- insumos ---
    _DISPONIVEL = """(SELECT COALESCE(SUM(l.saldo), 0) FROM lotes l
                       WHERE l.insumo_id = i.id AND (l.validade IS NULL OR l.validade >= :hoje))"""
    _PROXIMA = """(SELECT MIN(l.validade) FROM lotes l
                    WHERE l.insumo_id = i.id AND l.saldo > 0 AND l.validade >= :hoje)"""
    _VENCIDO = """(SELECT COALESCE(SUM(l.saldo), 0) FROM lotes l
                    WHERE l.insumo_id = i.id AND l.validade < :hoje)"""

    def listar(self, hoje, incluir_inativos=False):
        sql = (f"SELECT i.*, {self._DISPONIVEL} AS disponivel, {self._PROXIMA} AS proxima_validade, "
               f"{self._VENCIDO} AS vencido FROM insumos i"
               + ("" if incluir_inativos else " WHERE i.ativo = 1") + " ORDER BY i.nome")
        return self.conn.execute(sql, {"hoje": hoje}).fetchall()

    def disponivel(self, insumo_id, hoje):
        return self.conn.execute(
            "SELECT COALESCE(SUM(saldo), 0) FROM lotes WHERE insumo_id = ? AND (validade IS NULL OR validade >= ?)",
            (insumo_id, hoje)).fetchone()[0]

    def buscar(self, insumo_id):
        return self.conn.execute("SELECT * FROM insumos WHERE id = ?", (insumo_id,)).fetchone()

    def nomes(self):
        return self.conn.execute("SELECT id, nome FROM insumos").fetchall()

    def inserir(self, nome, tipo, unidade_base, estoque_minimo):
        cur = self.conn.execute(
            "INSERT INTO insumos (nome, tipo, unidade_base, estoque_minimo) VALUES (?, ?, ?, ?)",
            (nome, tipo, unidade_base, estoque_minimo))
        return cur.lastrowid

    def atualizar_cadastro(self, insumo_id, nome, tipo, unidade_base, estoque_minimo):
        self.conn.execute(
            "UPDATE insumos SET nome = ?, tipo = ?, unidade_base = ?, estoque_minimo = ? WHERE id = ?",
            (nome, tipo, unidade_base, estoque_minimo, insumo_id))

    def atualizar_saldo_custo(self, insumo_id, saldo, custo_medio_centavos):
        self.conn.execute("UPDATE insumos SET saldo = ?, custo_medio_centavos = ? WHERE id = ?",
                          (saldo, custo_medio_centavos, insumo_id))

    def definir_ativo(self, insumo_id, ativo):
        self.conn.execute("UPDATE insumos SET ativo = ? WHERE id = ?", (1 if ativo else 0, insumo_id))

    def excluir(self, insumo_id):
        self.conn.execute("DELETE FROM insumos WHERE id = ?", (insumo_id,))

    def em_uso(self, insumo_id):
        q = """SELECT (SELECT COUNT(*) FROM movimentacoes WHERE insumo_id = :i)
                    + (SELECT COUNT(*) FROM receita_itens WHERE insumo_id = :i)"""
        return self.conn.execute(q, {"i": insumo_id}).fetchone()[0] > 0

    def abaixo_do_minimo(self, hoje):
        return self.conn.execute(
            f"""SELECT * FROM (SELECT i.*, {self._DISPONIVEL} AS disponivel FROM insumos i WHERE i.ativo = 1)
                WHERE estoque_minimo > 0 AND disponivel <= estoque_minimo
                ORDER BY disponivel / estoque_minimo, nome""", {"hoje": hoje}).fetchall()

    # --- compras ---
    def inserir_compra(self, insumo_id, data, qtd_emb, tamanho, unidade, qtd_base, preco_centavos, fornecedor):
        cur = self.conn.execute(
            """INSERT INTO compras (insumo_id, data, qtd_embalagens, tamanho_embalagem, unidade_embalagem,
                                    quantidade_base, preco_total_centavos, fornecedor)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)""",
            (insumo_id, data, qtd_emb, tamanho, unidade, qtd_base, preco_centavos, fornecedor))
        return cur.lastrowid

    def compras_do_insumo(self, insumo_id):
        return self.conn.execute(
            """SELECT c.*, l.validade, l.codigo FROM compras c LEFT JOIN lotes l ON l.compra_id = c.id
               WHERE c.insumo_id = ? ORDER BY c.data DESC, c.id DESC""", (insumo_id,)).fetchall()

    def compras_recentes(self, limite=20):
        return self.conn.execute(
            """SELECT c.*, i.nome, i.unidade_base, l.validade, l.codigo FROM compras c
               JOIN insumos i ON i.id = c.insumo_id LEFT JOIN lotes l ON l.compra_id = c.id
               ORDER BY c.data DESC, c.id DESC LIMIT ?""", (limite,)).fetchall()

    # --- movimentações ---
    def inserir_movimentacao(self, insumo_id, data, quantidade, custo_unit, origem, referencia_id=None, obs=None,
                             lote_id=None):
        self.conn.execute(
            """INSERT INTO movimentacoes (insumo_id, data, quantidade, custo_unit_centavos, origem,
                                          referencia_id, observacao, lote_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)""",
            (insumo_id, data, quantidade, custo_unit, origem, referencia_id, obs, lote_id))

    def movimentacoes_do_insumo(self, insumo_id, limite=50):
        return self.conn.execute(
            """SELECT m.*, l.codigo AS lote_codigo, l.validade AS lote_validade FROM movimentacoes m
               LEFT JOIN lotes l ON l.id = m.lote_id
               WHERE m.insumo_id = ? ORDER BY m.data DESC, m.id DESC LIMIT ?""",
            (insumo_id, limite)).fetchall()

    # --- lotes (FEFO: primeiro a vencer, primeiro a sair) ---
    ORDEM_FEFO = "validade IS NULL, validade, data_entrada, id"

    def inserir_lote(self, insumo_id, compra_id, codigo, data_entrada, validade, quantidade, custo_unit):
        cur = self.conn.execute(
            """INSERT INTO lotes (insumo_id, compra_id, codigo, data_entrada, validade, quantidade_inicial, saldo,
                                  custo_unit_centavos) VALUES (?, ?, ?, ?, ?, ?, ?, ?)""",
            (insumo_id, compra_id, codigo, data_entrada, validade, quantidade, quantidade, custo_unit))
        return cur.lastrowid

    def buscar_lote(self, lote_id):
        return self.conn.execute(
            "SELECT l.*, i.nome, i.unidade_base FROM lotes l JOIN insumos i ON i.id = l.insumo_id WHERE l.id = ?",
            (lote_id,)).fetchone()

    def lotes_com_saldo(self, insumo_id, hoje=None):
        """Lotes na ordem FEFO. Com `hoje`, só os que ainda podem ser usados (não vencidos)."""
        sql = "SELECT * FROM lotes WHERE insumo_id = ? AND saldo > 0"
        params = [insumo_id]
        if hoje:
            sql += " AND (validade IS NULL OR validade >= ?)"
            params.append(hoje)
        return self.conn.execute(sql + " ORDER BY " + self.ORDEM_FEFO, params).fetchall()

    def lotes_do_insumo(self, insumo_id, incluir_esgotados=False):
        sql = "SELECT * FROM lotes WHERE insumo_id = ?" + ("" if incluir_esgotados else " AND saldo > 0")
        return self.conn.execute(sql + " ORDER BY " + self.ORDEM_FEFO, (insumo_id,)).fetchall()

    def atualizar_saldo_lote(self, lote_id, saldo):
        self.conn.execute("UPDATE lotes SET saldo = ? WHERE id = ?", (saldo, lote_id))

    def lotes_em_alerta(self, hoje, limite):
        """Lotes com saldo vencidos ou que vencem até `limite` (inclusive)."""
        return self.conn.execute(
            """SELECT l.*, i.nome, i.unidade_base FROM lotes l JOIN insumos i ON i.id = l.insumo_id
               WHERE i.ativo = 1 AND l.saldo > 0 AND l.validade IS NOT NULL AND l.validade <= ?
               ORDER BY l.validade, i.nome""", (limite,)).fetchall()

    def resumo_lotes(self, insumo_id, hoje):
        """Saldo total (com vencidos) e o valor/saldo só dos lotes utilizáveis, para o custo médio."""
        return self.conn.execute(
            """SELECT COALESCE(SUM(saldo), 0) AS saldo,
                      COALESCE(SUM(CASE WHEN validade IS NULL OR validade >= :h THEN saldo END), 0) AS saldo_util,
                      COALESCE(SUM(CASE WHEN validade IS NULL OR validade >= :h THEN saldo * custo_unit_centavos END), 0)
                          AS valor_util
               FROM lotes WHERE insumo_id = :i AND saldo > 0""", {"i": insumo_id, "h": hoje}).fetchone()
