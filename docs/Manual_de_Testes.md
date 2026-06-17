# Manual de Testes do Sistema PHP_Confeitaria

## 1. Objetivo
Este manual de testes descreve as funcionalidades do sistema de gestão da confeitaria e define casos de teste para validar cada requisito. O objetivo é medir e controlar a qualidade, garantir que o sistema atende ao escopo funcional e permitir regressão segura.

## 2. Escopo
São cobertas as áreas principais do sistema:
- Clientes
- Insumos e compras
- Receitas e ingredientes
- Encomendas e itens de encomenda
- Estoque e alertas
- Validade de lotes
- Cálculo de custos

## 3. Ambiente de Teste
Pré-requisitos:
- Servidor PHP/XAMPP configurado
- Banco de dados MySQL/MariaDB importado com o schema atual
- `config/database.php` apontando para o banco correto
- A aplicação acessível pelo navegador na pasta do projeto

Dados de teste:
- Usar clientes de exemplo com telefone e e-mail válidos
- Usar insumos com estoque atual e estoque mínimo definidos
- Usar receitas com ingredientes cadastrados

## 4. Requisitos Funcionais Principais
1. Gerenciar clientes: listar, criar, editar, excluir
2. Gerenciar insumos: cadastrar, editar, excluir
3. Registrar compras de insumos e atualizar estoque
4. Gerenciar receitas: criar receita, adicionar ingredientes, calcular custos
5. Gerenciar encomendas: criar/editar/excluir, associar cliente, usar `items` de encomenda
6. Calcular alerta de estoque com base na quantidade disponível atual
7. Controlar validade de lotes e gerar alertas de validade
8. Exibir estatísticas de encomendas e estoque

## 5. Matriz de Casos de Teste

### 5.1 Clientes
| ID | Funcionalidade | Passos | Resultado Esperado | Status |
|---|---|---|---|---|
| C1 | Listar clientes | Abrir `clientes.php` | Lista de clientes exibida | | ✅
| C2 | Criar cliente | Preencher formulário e salvar | Cliente aparece na lista | |✅
| C3 | Editar cliente | Selecionar cliente, alterar nome e salvar | Dados atualizados | |✅
| C4 | Excluir cliente | Excluir cliente existente | Cliente removido da lista | |✅

### 5.2 Insumos
| ID | Funcionalidade | Passos | Resultado Esperado | Status |✅
| I1 | Listar insumos | Abrir `gerenciar_insumos.php` | Lista de insumos exibida | |✅
| I2 | Criar insumo | Preencher nome, unidade e estoque | Insumo salvo e listado | |✅
| I3 | Editar insumo | Atualizar estoque e custo | Alterações refletidas | |✅
| I4 | Excluir insumo | Excluir insumo | Insumo removido | |✅
| I5 | Ver alertas de estoque | Abrir alertas ou verificar `alertas.php?verificar_alertas=1` | Exibe alertas de estoque mínimo/zerado | |✅

### 5.3 Compras
| ID | Funcionalidade | Passos | Resultado Esperado | Status |
| K1 | Registrar compra | Abrir `registrar_compras.php`, selecionar insumo, preencher quantidade e preço | Compra salva e estoque atualizado | | ❌
| K2 | Verificar estoque pós-compra | Conferir insumo modificado | `estoque_atual` incrementado | |

### 5.4 Receitas
| ID | Funcionalidade | Passos | Resultado Esperado | Status |
| R1 | Criar receita | Abrir `gerenciar_receitas.php`, preencher dados e salvar | Receita criada | | ✅
| R2 | Adicionar ingredientes | Selecionar receita, adicionar insumos e quantidades | Ingredientes vinculados à receita | | ✅
| R3 | Calcular custo | Usar cálculo automático ou manual | `custo_total` e `preco_venda_sugerido` corretos | | ✅
| R4 | Editar receita | Alterar dados de receita | Atualizações salvas | | ✅
| R5 | Excluir receita | Excluir receita | Receita removida | | ✅

### 5.5 Encomendas
| ID | Funcionalidade | Passos | Resultado Esperado | Status |
| E1 | Criar encomenda com cliente cadastrado | Abrir `gerenciar_encomendas.php`, selecionar cliente, adicionar item e salvar | Encomenda criada com `cliente_id` e item persistido | | ✅
| E2 | Criar encomenda com cliente manual | Preencher nome e telefone manualmente sem `cliente_id` | Encomenda criada com dados de cliente salvos na encomenda | |  ❌ Impossível pelas regras de negócio 
| E3 | Adicionar múltiplos itens | Criar encomenda com 2 ou mais linhas de item | Todos os itens são gravados em `item_encomenda` | | ❌
| E4 | Editar encomenda | Alterar status, item, quantidade ou cliente | Alterações persistem corretamente | | ✅
| E5 | Excluir encomenda | Excluir pedido existente | Registro removido e itens associados também | | ✅
| E6 | Listar encomendas por status | Filtrar por `pendente`, `em_producao`, `pronta`, `entregue`, `cancelada` | Lista filtrada corretamente | |✅
| E7 | Ver estatísticas | Clicar em estatísticas | Totais de encomendas e valores exibidos | |✅
| E8 | Detectar falta de insumos | Criar encomenda com receita cujo estoque não atende | Sistema retorna `missing_insumos` com itens faltantes | | ✅

### 5.6 Validade e alertas
| ID | Funcionalidade | Passos | Resultado Esperado | Status |
| V1 | Registrar lote de validade | Criar lote em `gerenciar_receitas.php` / validação | Lote salvo com datas e quantidade | | ❌
| V2 | Alertas de validade | Acessar alertas ou consultar API | Alertas exibidos para lotes vencidos/proximos de vencer | |❌
| V3 | Atualizar status de lote | Alterar status e salvar | Status refletido no registro | | ❌

### 5.7 Cálculo de custos e receita
| ID | Funcionalidade | Passos | Resultado Esperado | Status |
| T1 | Calcular custo total de receita | Criar receita com ingredientes e custo | `custo_total` calculado corretamente | | ✅
| T2 | Calcular preço de venda | Executar cálculo de preço via API ou interface | `preco_venda_sugerido` atualizado conforme margem | | ✅
| T3 | Validar custo extra | Adicionar custo extra a receita | Custo extra incluído no cálculo de preço | |

## 6. Critérios de Aceitação
- Cada cadastro deve salvar os dados e exibir mensagem de sucesso
- Edições devem persistir e refletir imediatamente na listagem
- Exclusões removem os dados do banco e não deixam registros órfãos
- Encomendas devem preservar cliente cadastrado ou dados manuais
- A lógica de item de encomenda deve gravar itens em `item_encomenda`
- Alertas devem ser gerados com base no estoque atual, não somente em registros antigos
- Pacotes de validade devem ser alertados no prazo correto

## 7. Procedimento de Execução
1. Preparar banco de dados com uma cópia de teste.
2. Executar os casos de teste um a um, preenchendo o status na tabela.
3. Registrar anomalias em resultado/observação.
4. Repetir testes-chave após qualquer correção.

## 8. Controle de Resultado
- Usar colunas de `Status` e `Observações` para cada caso de teste.
- Marcar `Passou`, `Falhou` ou `Bloqueado`.
- Documentar dados usados, como IDs de cliente, insumo, receita e encomenda.
- Ao final, gerar resumo de cobertura por módulo.

## 9. Observações Adicionais
- Testes de regressão devem incluir sempre: criação e edição de receitas, geração de encomendas e alertas de estoque.
- Validar a consistência entre a interface e as APIs `api/*.php`.
- Em caso de falhas de integração, consultar os logs do Apache/PHP e os scripts na pasta `scripts/`.

## 10. Exemplo de Relatório de Teste
- Módulo: Encomendas
- Caso: E3 - Adicionar múltiplos itens
- Resultado: Passou
- Observação: Itens gravados em `item_encomenda`, valor total correto.

---

Este manual pode ser usado como base para testar manualmente o sistema e também para suportar uma futura automação de casos de teste.
