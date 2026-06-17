# Sistema de Doceria - Arquitetura MVC Organizada

Sistema profissional para gerenciamento de doceria com arquitetura MVC (Model-View-Controller), Princípio de Responsabilidade Única e estrutura de pastas organizada.

## 📁 Estrutura do Projeto

```
Projeto-Extensao-main/
├── 📦 src/                     # Código fonte principal
│   ├── controllers/            # Controllers - Orquestração
│   ├── views/                  # Views - Interface gráfica
│   ├── models/                 # Models - Classes de domínio
│   ├── services/               # Services - Lógica de negócio
│   ├── repositories/           # Repositories - Acesso a dados
│   ├── database/               # Database - Configuração
│   └── __init__.py
├── ⚙️ config/                  # Configurações centralizadas
│   ├── settings.py             # Constantes e configurações
│   └── __init__.py
├── 📜 scripts/                 # Scripts de setup e automação
│   ├── setup.sh               # Setup completo
│   ├── install.sh             # Instalação detalhada
│   ├── dev.sh                 # Ambiente de desenvolvimento
│   ├── Makefile               # Build system
│   └── requirements.txt       # Dependências Python
├── 📚 docs/                    # Documentação
│   └── README.md              # Este arquivo
├── 🧪 tests/                   # Testes e demonstrações
├── 🚀 app.py                   # Ponto de entrada principal
├── 📄 main.py                  # Versão original (mantida)
├── 🗄️ doceria.db              # Banco de dados SQLite
└── 📋 setup-organized.sh       # Setup automático
```

## 🚀 Instalação Rápida

### Opção 1: Setup Automático (Recomendado)
```bash
./setup-organized.sh
./run.sh
```

### Opção 2: Manual
```bash
# 1. Instalar dependências do sistema
sudo apt update
sudo apt install python3 python3-pip python3-venv python3-tk -y

# 2. Criar ambiente virtual
python3 -m venv venv
source venv/bin/activate

# 3. Instalar dependências Python
pip install -r scripts/requirements.txt

# 4. Executar
python3 app.py
```

## 🖥️ Como Usar

### Executar Versão Organizada
```bash
./run.sh
# ou
python3 app.py
```

### Executar Versão Original
```bash
./run-old.sh
# ou
python3 main.py
```

### Ambiente de Desenvolvimento
```bash
./dev.sh help        # Ver comandos disponíveis
./dev.sh run        # Executar app
./dev.sh status     # Ver status
./dev.sh clean      # Limpar ambiente
```

## 🏗️ Arquitetura MVC

### Model (`src/models/`)
- **Produto**: Classe de domínio para produtos
- **Receita**: Classe de domínio para receitas

### View (`src/views/`)
- **EstoqueView**: Interface de entrada de insumos
- **DespensaView**: Visualização do estoque
- **MontarReceitaView**: Criação de fichas técnicas
- **VisualizarReceitasView**: Visualização e produção

### Controller (`src/controllers/`)
- **EstoqueController**: Orquestração de operações de estoque
- **ReceitaController**: Orquestração de operações de receitas
- **MainController**: Coordenação geral

### Service (`src/services/`)
- **EstoqueService**: Regras de negócio de estoque
- **ReceitaGestaoService**: Regras de negócio de receitas

### Repository (`src/repositories/`)
- **ProdutoRepository**: Acesso a dados de produtos
- **ReceitaRepository**: Acesso a dados de receitas

## ⚙️ Configurações

### Arquivo: `config/settings.py`
```python
# Configurações do banco de dados
DATABASE_CONFIG = {
    'type': 'sqlite',
    'database': 'doceria.db'
}

# Conversões de produtos
CONVERSOES = {
    "Leite Condensado": (395, "g"),
    "Creme De Leite": (200, "g"),
    # ...
}

# Configurações da interface
UI_CONFIG = {
    'title': "Doceria Pro v4.5 - Interface MVC",
    'geometry': "1000x800"
}

# Configurações de negócio
BUSINESS_CONFIG = {
    'markup_multiplier': 3.0,
    'precision_digits': 2
}
```

## ✨ Funcionalidades

### 📦 Gestão de Estoque
- Cadastro de insumos com conversões automáticas
- Cálculo de preços por unidade de medida
- Visualização em tempo real do estoque
- Baixa automática na produção

### 🍰 Gestão de Receitas
- Criação de fichas técnicas
- Cálculo automático de custos
- Sugestão de preço de venda
- Produção com baixa de estoque

### 📊 Relatórios
- Visualização detalhada de custos
- Sugestões de precificação
- Controle de insumos

## 🎯 Benefícios da Nova Estrutura

- ✅ **Organização Profissional**: Pastas temáticas e lógicas
- ✅ **Configuração Centralizada**: Todas as configurações em um lugar
- ✅ **Separação Clara**: src/, config/, scripts/, docs/, tests/
- ✅ **Manutenibilidade**: Fácil localizar e modificar componentes
- ✅ **Escalabilidade**: Estrutura pronta para crescer
- ✅ **Documentação Organizada**: docs/ dedicado
- ✅ **Scripts Centralizados**: scripts/ para automação
- ✅ **Testes Estruturados**: tests/ para testes unitários

---

