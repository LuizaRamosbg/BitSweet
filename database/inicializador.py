"""Cria o esquema e, na primeira execução, importa os dados do banco antigo (doceria.db)."""
import datetime
import sqlite3
from pathlib import Path

from database.conexao import RAIZ, conectar

SCHEMA = Path(__file__).with_name("schema.sql")
BANCO_ANTIGO = RAIZ / "doceria.db"

# Itens que o app desktop antigo tratava com tamanho de embalagem fixo (já gravados na unidade base)
_CONVERSOES_ANTIGAS = {"Leite Condensado": "g", "Creme De Leite": "g", "Leite": "ml",
                       "Manteiga": "g", "Chocolate Em Po": "g"}
_UNIDADES_ANTIGAS = {"g": ("g", 1), "ml": ("ml", 1), "Un": ("un", 1), "Kg": ("g", 1000), "L": ("ml", 1000)}


def inicializar(caminho=None):
    conn = conectar(caminho)
    _adicionar_coluna_lote(conn)          # bancos criados antes dos lotes
    _migrar_marca_insumos(conn)           # bancos criados antes da marca (nome deixa de ser único)
    _garantir_coluna(conn, "clientes", "aniversario_dia",
                     "INTEGER CHECK (aniversario_dia BETWEEN 1 AND 31)")
    _garantir_coluna(conn, "clientes", "aniversario_mes",
                     "INTEGER CHECK (aniversario_mes BETWEEN 1 AND 12)")
    conn.executescript(SCHEMA.read_text(encoding="utf-8"))
    conn.commit()
    vazio = conn.execute("SELECT COUNT(*) FROM insumos").fetchone()[0] == 0
    if vazio and caminho is None and BANCO_ANTIGO.exists():
        migrar_banco_antigo(conn, BANCO_ANTIGO)
    criar_lotes_iniciais(conn)
    conn.close()


def _adicionar_coluna_lote(conn):
    tabela = conn.execute("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'movimentacoes'").fetchone()
    if not tabela:
        return
    colunas = [c[1] for c in conn.execute("PRAGMA table_info(movimentacoes)")]
    if "lote_id" not in colunas:
        conn.execute("ALTER TABLE movimentacoes ADD COLUMN lote_id INTEGER REFERENCES lotes(id)")
        conn.commit()


def _colunas(conn, tabela):
    return [c[1] for c in conn.execute(f"PRAGMA table_info({tabela})")]


def _garantir_coluna(conn, tabela, coluna, ddl):
    colunas = _colunas(conn, tabela)
    if colunas and coluna not in colunas:
        conn.execute(f"ALTER TABLE {tabela} ADD COLUMN {coluna} {ddl}")
        conn.commit()


def _migrar_marca_insumos(conn):
    """Recria a tabela de insumos com a coluna `marca` e sem o UNIQUE no nome (SQLite não remove
    restrições com ALTER TABLE). A marca é preenchida com o fornecedor da compra mais recente."""
    colunas = _colunas(conn, "insumos")
    if not colunas or "marca" in colunas:
        return
    conn.commit()
    conn.execute("PRAGMA foreign_keys = OFF")
    try:
        conn.executescript("""
            BEGIN;
            CREATE TABLE insumos_nova (
                id                   INTEGER PRIMARY KEY AUTOINCREMENT,
                nome                 TEXT    NOT NULL COLLATE NOCASE,
                marca                TEXT    NOT NULL DEFAULT '' COLLATE NOCASE,
                tipo                 TEXT    NOT NULL DEFAULT 'ingrediente'
                                     CHECK (tipo IN ('ingrediente', 'embalagem', 'outro')),
                unidade_base         TEXT    NOT NULL CHECK (unidade_base IN ('g', 'ml', 'un')),
                saldo                REAL    NOT NULL DEFAULT 0 CHECK (saldo >= 0),
                custo_medio_centavos REAL    NOT NULL DEFAULT 0,
                estoque_minimo       REAL    NOT NULL DEFAULT 0 CHECK (estoque_minimo >= 0),
                ativo                INTEGER NOT NULL DEFAULT 1
            );
            INSERT INTO insumos_nova (id, nome, tipo, unidade_base, saldo, custo_medio_centavos, estoque_minimo, ativo)
                SELECT id, nome, tipo, unidade_base, saldo, custo_medio_centavos, estoque_minimo, ativo FROM insumos;
            DROP TABLE insumos;
            ALTER TABLE insumos_nova RENAME TO insumos;
            COMMIT;
        """)
        tem_compras = conn.execute(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'compras'").fetchone()
        if tem_compras:
            conn.execute("""
                UPDATE insumos SET marca = COALESCE((
                    SELECT TRIM(c.fornecedor) FROM compras c
                    WHERE c.insumo_id = insumos.id AND TRIM(COALESCE(c.fornecedor, '')) <> ''
                    ORDER BY c.data DESC, c.id DESC LIMIT 1), '')""")
            conn.commit()
    finally:
        conn.execute("PRAGMA foreign_keys = ON")


def criar_lotes_iniciais(conn):
    """Saldo de insumos que ainda não tem lotes (dados anteriores ao controle de validade)
    vira um lote 'sem validade', para que nenhum estoque se perca."""
    hoje = datetime.date.today().isoformat()
    pendentes = conn.execute(
        """SELECT i.id, i.saldo, i.custo_medio_centavos FROM insumos i
           WHERE i.saldo > 0 AND NOT EXISTS (SELECT 1 FROM lotes l WHERE l.insumo_id = i.id)""").fetchall()
    for iid, saldo, custo in pendentes:
        conn.execute(
            """INSERT INTO lotes (insumo_id, codigo, data_entrada, validade, quantidade_inicial, saldo,
                                  custo_unit_centavos) VALUES (?, 'Saldo anterior', ?, NULL, ?, ?, ?)""",
            (iid, hoje, saldo, saldo, custo))
    conn.commit()


def migrar_banco_antigo(conn, origem):
    """Importa insumos e receitas do app desktop, normalizando as unidades para g/ml/un."""
    antigo = sqlite3.connect(origem)
    hoje = datetime.date.today().isoformat()
    fatores = {}
    try:
        produtos = antigo.execute("SELECT nome, preco, quantidade, unidade FROM produtos").fetchall()
        for nome, preco, qtd, unidade in produtos:
            if nome in _CONVERSOES_ANTIGAS:
                base, fator = _CONVERSOES_ANTIGAS[nome], 1
            else:
                base, fator = _UNIDADES_ANTIGAS.get(unidade, ("un", 1))
            fatores[nome] = fator
            custo_centavos = (preco or 0) * 100 / fator
            cur = conn.execute(
                "INSERT INTO insumos (nome, unidade_base, saldo, custo_medio_centavos) VALUES (?, ?, ?, ?)",
                (nome, base, qtd * fator, custo_centavos))
            conn.execute(
                "INSERT INTO movimentacoes (insumo_id, data, quantidade, custo_unit_centavos, origem, observacao)"
                " VALUES (?, ?, ?, ?, 'ajuste', 'Saldo importado do sistema antigo')",
                (cur.lastrowid, hoje, qtd * fator, custo_centavos))

        for rid, nome in antigo.execute("SELECT id, nome FROM receitas").fetchall():
            cur = conn.execute("INSERT INTO receitas (nome, rendimento) VALUES (?, 1)", (nome,))
            itens = antigo.execute(
                "SELECT nome_insumo, quantidade FROM receita_itens WHERE id_receita = ?", (rid,)).fetchall()
            for nome_insumo, qtd in itens:
                ins = conn.execute("SELECT id FROM insumos WHERE nome = ?", (nome_insumo,)).fetchone()
                if ins:
                    conn.execute("INSERT OR IGNORE INTO receita_itens VALUES (?, ?, ?)",
                                 (cur.lastrowid, ins[0], qtd * fatores.get(nome_insumo, 1)))
        conn.commit()
    except sqlite3.Error:
        conn.rollback()
    finally:
        antigo.close()
