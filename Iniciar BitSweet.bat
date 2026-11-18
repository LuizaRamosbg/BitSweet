@echo off
rem Launcher do BitSweet para Windows: de dois cliques neste arquivo.
title BitSweet
cd /d "%~dp0"

where py >nul 2>nul
if %errorlevel%==0 (
    py -3 launcher.py
    goto fim
)
where python >nul 2>nul
if %errorlevel%==0 (
    python launcher.py
    goto fim
)

echo.
echo  Python nao encontrado.
echo  Instale o Python 3 em https://www.python.org/downloads/
echo  (marque a opcao "Add Python to PATH") e tente de novo.
echo.

:fim
if errorlevel 1 pause
