#!/usr/bin/env sh
# Launcher do BitSweet para Linux/macOS: ./iniciar_bitsweet.sh
cd "$(dirname "$0")" || exit 1
if command -v python3 >/dev/null 2>&1; then
    exec python3 launcher.py
elif command -v python >/dev/null 2>&1; then
    exec python launcher.py
fi
echo "Python 3 não encontrado. Instale em https://www.python.org/downloads/"
exit 1
