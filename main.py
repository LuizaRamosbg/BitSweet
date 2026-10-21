"""Atalho: inicia a versão web do BitSweet (python main.py)."""
import os
from app import app

if __name__ == "__main__":
    app.run(host=os.environ.get("HOST", "127.0.0.1"), debug=os.environ.get("FLASK_DEBUG", "1") != "0")
