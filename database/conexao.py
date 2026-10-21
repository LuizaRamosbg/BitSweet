"""Conexão com o SQLite: uma conexão por unidade de trabalho, com FKs ativas e transação explícita."""
import os
import sqlite3
from contextlib import contextmanager
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
# Caminho do banco: variável BITSWEET_DB_PATH (usada no Docker) ou bitsweet.db na raiz do projeto
DB_PATH = Path(os.environ.get("BITSWEET_DB_PATH") or RAIZ / "bitsweet.db")


def conectar(caminho=None):
    conn = sqlite3.connect(caminho or DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn


@contextmanager
def transacao(conn):
    """Executa um bloco de forma atômica: commit se tudo der certo, rollback se algo falhar."""
    try:
        yield conn
        conn.commit()
    except Exception:
        conn.rollback()
        raise
