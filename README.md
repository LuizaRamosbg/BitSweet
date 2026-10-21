# BitSweet — versão web

Sistema de gestão para doceria: estoque, fichas técnicas, precificação, encomendas e relatórios.

## Como rodar
```bash
pip install -r requirements.txt
python app.py        # ou: python main.py
```
Abra http://127.0.0.1:5000. Na primeira execução é criado o `bitsweet.db`; se existir o `doceria.db` do app desktop antigo, os insumos e receitas são importados automaticamente (unidades convertidas para g/ml/un).

## Docker
```bash
docker compose up --build
```
O banco fica em `./data/bitsweet.db` (variável `BITSWEET_DB_PATH`).

## Testes
```bash
pip install -r requirements-dev.txt
python -m pytest -v tests/
```
`tests/test_lotes.py` cobre lotes e FEFO; `tests/test_plano.py` executa o plano de testes (um teste por caso, CT-01 a CT-70 e NF-01 a NF-06).

## Estrutura
```
app.py                 rotas Flask (camada web)
database/              conexão, schema.sql e inicialização/migração
repositories/          SQL por entidade
services/              regras de negócio (um serviço por módulo)
templates/             telas (Jinja)
static/css/style.css   identidade visual
tests/                 testes automatizados (pytest)
```

## Requisitos implementados
| RF | Onde |
|---|---|
| 01 Cadastro de insumos | Insumos (ingrediente/embalagem/outro, editar, excluir/desativar) |
| 02 Registro de compras | Compras (qtd × tamanho da embalagem, preço total, fornecedor, histórico) |
| 03 Custo unitário | Calculado na compra; custo médio ponderado (R$/kg, R$/L, R$/un) |
| 04 Saldo | Lista de insumos + histórico de movimentações e ajuste de inventário |
| — Lotes e validade | Cada compra vira um lote com validade; consumo FEFO (vence antes, sai antes); vencidos fora do saldo disponível, alerta de 7 dias e descarte |
| 05 Estoque mínimo | Faixa de alerta em todas as telas |
| 06 Receita + rendimento | Receitas (ex.: 20 fatias) |
| 07 Insumos da receita | Ficha técnica com quantidades na unidade base |
| 08 Custo da receita | Soma qtd × custo médio de cada insumo |
| 09 Custo por porção | Custo total ÷ rendimento |
| 10 Custos extras | Embalagens do estoque + custos avulsos por lote (fita, etiqueta, gás) |
| 11 Preço de venda | Sugestão por markup e por CMV alvo; preço manual opcional |
| 12 Baixa automática | Ao produzir uma receita ou quando a encomenda entra em produção (atômico) |
| 13 Clientes | Cadastro, edição, busca, exclusão |
| 14 Encomendas | Cliente + produtos, valor calculado, preço/custo congelados no pedido |
| 15 Status | Pendente → Em Produção → Pago → Pronto → Entregue (+ Cancelado) |
| 16 Pagamento | Pagamentos parciais; status Pendente/Parcial/Total automático |
| 17 Histórico | Página do cliente: pedidos, total, ticket médio, produtos preferidos |
| 18 Lucratividade | Receita, custo, lucro e margem por produto e por mês |
| 19 Consumo | Consumo por período, dias de cobertura e sugestão de compra |
| 20 KPIs | Mais vendidos, conclusão, pontualidade, lead time, porções/dia |
| 21 Exportação | CSV (abre no Excel) e PDF de cada relatório |

Valores monetários são guardados em centavos; quantidades na unidade base.
