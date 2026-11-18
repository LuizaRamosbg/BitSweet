"""Launcher do BitSweet: prepara o ambiente, inicia o servidor e abre o navegador.

Uso: dê dois cliques em "Iniciar BitSweet.bat" (Windows) ou rode `python launcher.py`.

O que ele faz:
1. Cria o ambiente virtual `.venv` na primeira execução.
2. Instala/atualiza as dependências quando o requirements.txt muda.
3. Se o BitSweet já estiver aberto, só abre o navegador.
4. Senão, escolhe uma porta livre (PORT ou 5000), inicia o servidor e abre o navegador.
Para encerrar, feche a janela ou pressione Ctrl+C.
"""
import hashlib
import os
import socket
import subprocess
import sys
import time
import urllib.request
import venv
import webbrowser
from pathlib import Path

RAIZ = Path(__file__).resolve().parent
VENV = RAIZ / ".venv"
REQUISITOS = RAIZ / "requirements.txt"
CARIMBO = VENV / ".requisitos.sha256"
HOST = "127.0.0.1"


def log(msg):
    print(f"[BitSweet] {msg}", flush=True)


def python_do_venv():
    if os.name == "nt":
        return VENV / "Scripts" / "python.exe"
    return VENV / "bin" / "python"


def preparar_ambiente():
    py = python_do_venv()
    if not py.exists():
        log("Primeira execução: criando o ambiente virtual (.venv)...")
        venv.EnvBuilder(with_pip=True).create(VENV)

    hash_atual = hashlib.sha256(REQUISITOS.read_bytes()).hexdigest()
    if not CARIMBO.exists() or CARIMBO.read_text().strip() != hash_atual:
        log("Instalando dependências (só na primeira vez ou quando mudarem)...")
        subprocess.run([str(py), "-m", "pip", "install", "--disable-pip-version-check", "-q",
                        "-r", str(REQUISITOS)], check=True)
        CARIMBO.write_text(hash_atual)
    return py


def porta_ocupada(porta):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.settimeout(0.5)
        return s.connect_ex((HOST, porta)) == 0


def e_bitsweet(porta):
    """True se quem responde na porta é o BitSweet (a tela de login ou a inicial)."""
    try:
        with urllib.request.urlopen(f"http://{HOST}:{porta}/", timeout=2) as r:
            return "BitSweet" in r.read().decode("utf-8", "ignore")
    except Exception:
        return False


def escolher_porta(inicial):
    for porta in range(inicial, inicial + 20):
        if not porta_ocupada(porta):
            return porta, False
        if e_bitsweet(porta):
            return porta, True
    raise SystemExit(f"Nenhuma porta livre entre {inicial} e {inicial + 19}.")


def aguardar_servidor(proc, porta, limite=60):
    fim = time.time() + limite
    while time.time() < fim:
        if proc.poll() is not None:
            return False
        if porta_ocupada(porta):
            return True
        time.sleep(0.3)
    return False


def main():
    os.chdir(RAIZ)
    porta, ja_aberto = escolher_porta(int(os.environ.get("PORT", "5000")))
    url = f"http://{HOST}:{porta}/"

    if ja_aberto:
        log(f"O BitSweet já está rodando em {url} — abrindo o navegador.")
        webbrowser.open(url)
        return

    py = preparar_ambiente()
    env = dict(os.environ, HOST=HOST, PORT=str(porta), FLASK_DEBUG="0")
    log(f"Iniciando o servidor em {url} ...")
    proc = subprocess.Popen([str(py), "app.py"], cwd=RAIZ, env=env)

    try:
        if not aguardar_servidor(proc, porta):
            log("O servidor não iniciou. Veja as mensagens acima.")
            proc.terminate()
            sys.exit(1)
        log("Pronto! Abrindo o navegador. Para encerrar, feche esta janela ou pressione Ctrl+C.")
        webbrowser.open(url)
        proc.wait()
    except KeyboardInterrupt:
        log("Encerrando...")
    finally:
        if proc.poll() is None:
            proc.terminate()
            try:
                proc.wait(timeout=5)
            except subprocess.TimeoutExpired:
                proc.kill()


if __name__ == "__main__":
    main()
