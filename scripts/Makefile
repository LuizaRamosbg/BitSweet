# Makefile para Sistema de Doceria - Arquitetura MVC
# Facilita o desenvolvimento e deploy

.PHONY: help install run run-old test clean reset status check

# Variáveis
PYTHON := python3
VENV := venv
ACTIVATE := source $(VENV)/bin/activate
MAIN_MVC := main_mvc.py
MAIN_OLD := main.py

help:	## Mostrar ajuda
	@echo "🏪 Sistema de Doceria - Makefile"
	@echo "==============================="
	@echo ""
	@echo "Comandos disponíveis:"
	@echo "  make install    - Instalar dependências"
	@echo "  make run        - Executar versão MVC"
	@echo "  make run-old    - Executar versão original"
	@echo "  make test       - Executar testes"
	@echo "  make clean      - Limpar ambiente virtual"
	@echo "  make reset      - Reset completo"
	@echo "  make status     - Mostrar status"
	@echo "  make check      - Verificar instalação"
	@echo "  make help       - Mostrar esta ajuda"

install:	## Instalar todas as dependências
	@echo "📦 Instalando dependências..."
	@sudo apt update && sudo apt install python3 python3-pip python3-venv python3-tk -y
	@if [ -d $(VENV) ]; then rm -rf $(VENV); fi
	@$(PYTHON) -m venv $(VENV)
	@$(ACTIVATE) && pip install --upgrade pip
	@$(ACTIVATE) && pip install -r requirements.txt
	@echo "✅ Instalação concluída"

run:	## Executar aplicação MVC
	@echo "🚀 Iniciando aplicação MVC..."
	@if [ ! -d $(VENV) ]; then echo "❌ Ambiente virtual não encontrado. Execute 'make install'"; exit 1; fi
	@$(ACTIVATE) && $(PYTHON) $(MAIN_MVC)

run-old:	## Executar aplicação original
	@echo "🚀 Iniciando aplicação original..."
	@if [ ! -d $(VENV) ]; then echo "❌ Ambiente virtual não encontrado. Execute 'make install'"; exit 1; fi
	@$(ACTIVATE) && $(PYTHON) $(MAIN_OLD)

test:	## Executar testes
	@echo "🧪 Executando testes..."
	@if [ ! -d $(VENV) ]; then echo "❌ Ambiente virtual não encontrado. Execute 'make install'"; exit 1; fi
	@$(ACTIVATE) && $(PYTHON) demo_uso.py

clean:	## Limpar ambiente virtual
	@echo "🗑️  Limpando ambiente virtual..."
	@rm -rf $(VENV)
	@echo "✅ Ambiente virtual removido"

reset: clean install	## Reset completo

status:	## Mostrar status do ambiente
	@echo "📊 Status do Ambiente:"
	@echo "===================="
	@echo "Python3: $(shell $(PYTHON) --version 2>/dev/null || echo 'Não encontrado')"
	@echo "pip3: $(shell pip3 --version 2>/dev/null || echo 'Não encontrado')"
	@if [ -d $(VENV) ]; then \
		echo "Ambiente virtual: ✅ existe"; \
		if $(ACTIVATE) && python -c "import customtkinter" 2>/dev/null; then \
			echo "customtkinter: ✅ instalado"; \
		else \
			echo "customtkinter: ❌ não instalado"; \
		fi; \
	else \
		echo "Ambiente virtual: ❌ não existe"; \
	fi
	@echo ""
	@echo "📁 Arquivos principais:"
	@for file in $(MAIN_MVC) $(MAIN_OLD) requirements.txt; do \
		if [ -f $$file ]; then echo "✅ $$file"; else echo "❌ $$file"; fi; \
	done
	@echo ""
	@echo "📁 Estrutura MVC:"
	@for dir in controllers views models services repositories; do \
		if [ -d $$dir ]; then echo "✅ $$dir/"; else echo "❌ $$dir/"; fi; \
	done

check:	## Verificar instalação completa
	@echo "🔍 Verificando instalação completa..."
	@echo ""
	@echo "1️⃣ Verificando Python3..."
	@$(PYTHON) --version || (echo "❌ Python3 não encontrado" && exit 1)
	@echo "✅ Python3 OK"
	@echo ""
	@echo "2️⃣ Verificando ambiente virtual..."
	@if [ ! -d $(VENV) ]; then echo "❌ Ambiente virtual não encontrado"; exit 1; fi
	@echo "✅ Ambiente virtual OK"
	@echo ""
	@echo "3️⃣ Verificando dependências..."
	@$(ACTIVATE) && python -c "import customtkinter, tkinter" || (echo "❌ Dependências Python não encontradas" && exit 1)
	@echo "✅ Dependências Python OK"
	@echo ""
	@echo "4️⃣ Verificando módulos do sistema..."
	@$(ACTIVATE) && python -c "from database.conexao import ConexaoSQLite; from controllers.main_controller import MainController; from views.main_view import MainView; print('✅ Módulos do sistema OK')" || (echo "❌ Módulos do sistema com problemas" && exit 1)
	@echo ""
	@echo "🎉 INSTALAÇÃO 100% FUNCIONAL!"
	@echo ""
	@echo "🚀 Para usar:"
	@echo "   make run      # Versão MVC"
	@echo "   make run-old  # Versão original"
