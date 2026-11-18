"""Backup do banco: copia o bitsweet.db para data/backups/ mantendo as cópias mais recentes.

Uso: python -m database.backup
O atualizador automático (docker-compose.cliente.yml) roda este comando antes de cada atualização.
"""
import sqlite3
import sys
from datetime import datetime

from database.conexao import DB_PATH

MANTER = 10  # quantos backups guardar


def fazer_backup(origem=DB_PATH, manter=MANTER):
    if not origem.exists():
        print(f"[backup] Banco não encontrado em {origem}; nada a fazer.")
        return None
    pasta = origem.parent / "backups"
    pasta.mkdir(parents=True, exist_ok=True)
    destino = pasta / f"{origem.stem}-{datetime.now():%Y%m%d-%H%M%S}.db"

    # A API de backup do SQLite gera uma cópia consistente mesmo com o sistema aberto.
    with sqlite3.connect(origem) as src, sqlite3.connect(destino) as dst:
        src.backup(dst)
    print(f"[backup] Cópia salva em {destino}")

    antigos = sorted(pasta.glob(f"{origem.stem}-*.db"))[:-manter]
    for arquivo in antigos:
        arquivo.unlink()
    return destino


if __name__ == "__main__":
    try:
        fazer_backup()
    except Exception as erro:  # o atualizador registra a falha no log
        print(f"[backup] Falhou: {erro}", file=sys.stderr)
        sys.exit(1)
