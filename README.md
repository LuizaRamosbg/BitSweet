# PHP_confeitaria

Sistema de gestão para confeitaria desenvolvido em PHP com MySQL.

## O que foi ajustado

- Corrigido fluxo de criação de receitas para permitir criação e posterior adição de ingredientes.
- Adicionado formulário de ingredientes na interface de receitas.
- Melhorada a validação de criação de receita no backend.
- Incluído arquivo de migração compatível com phpMyAdmin: `database/migration_simple.sql`.
- Ajustada a lógica de cálculo de custo total e preço de venda da receita.

## Estrutura principal

- `api/` — endpoints REST para receitas, insumos, compras, validação, alarmes e demais recursos.
- `controllers/` — controladores que tratam as requisições e chamam os modelos.
- `models/` — classes de domínio para receitas, insumos, encomendas, etc.
- `views/` — telas e formulários do sistema, incluindo `gerenciar_receitas.php`.
- `database/` — esquemas SQL e migrações.

## Como usar

1. Configure o banco de dados em `config/database.php`.
2. Importe o schema base em `database/schema.sql` se ainda não estiver criado.
3. Se precisar rodar as atualizações de schema diretamente no phpMyAdmin, use `database/migration_simple.sql`.
4. Inicie o servidor PHP ou o XAMPP apontando para a pasta do projeto.
5. Acesse a aplicação e use a tela de `Gerenciar Receitas` para cadastrar novas receitas.

## Receitas

- Crie uma receita preenchendo nome, rendimento, tempo de preparo e margem de lucro.
- Após criar a receita, o sistema abre automaticamente o formulário para adicionar ingredientes.
- Cada receita deve ter pelo menos um ingrediente para calcular custo total corretamente.

## Migração

- `database/migration.sql` contém instruções condicionais para ambientes MySQL/MariaDB que suportam procedimentos.
- `database/migration_simple.sql` é uma versão sem `DELIMITER` e sem procedimentos, adequada para importar em phpMyAdmin.

## Observações

- Se a coluna antiga `unidade_medida` existir no seu banco, ajuste-a para `unidade_compra`/`unidade_uso` conforme o schema atual.
- O arquivo `database/migration_simple.sql` contém comentários com orientações para alterações manuais não idempotentes.
