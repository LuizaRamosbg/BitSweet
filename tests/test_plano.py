"""Execução automatizada do Plano de Testes do BitSweet.
Cada teste leva o ID do caso no nome (ct01, ct02, ...). Rode com:  python -m pytest -v tests/
"""
import csv
import datetime
import io
import sqlite3

import pytest

import database.conexao as conexao
from database import inicializador


# ------------------------------------------------------------------ infraestrutura
@pytest.fixture()
def app(tmp_path, monkeypatch):
    monkeypatch.setattr(conexao, "DB_PATH", tmp_path / "teste.db")
    monkeypatch.setattr(inicializador, "BANCO_ANTIGO", tmp_path / "nao_existe.db")
    inicializador.inicializar()
    import app as modulo
    modulo.app.config.update(TESTING=True)
    return modulo


@pytest.fixture()
def cli(app):
    return app.app.test_client()


@pytest.fixture()
def db(app):
    conn = conexao.conectar()
    yield conn
    conn.close()


def svc(app, db):
    from services.encomenda_service import ClienteService, EncomendaService
    from services.estoque_service import EstoqueService
    from services.receita_service import ReceitaService
    from services.relatorio_service import RelatorioService
    return (EstoqueService(db), ReceitaService(db), ClienteService(db), EncomendaService(db),
            RelatorioService(db))


def flash(resp):
    return resp.get_data(as_text=True)


def massa_base(app, db):
    """Dados de teste do plano: 5 insumos, receita Bolo simples e cliente Ana Souza."""
    est, rec, cli_s, enc, rel = svc(app, db)
    ids = {}
    for nome, tipo, un, minimo, compra in [
        ("Farinha de trigo", "ingrediente", "g", 500, (2, 1, "kg", 1000)),
        ("Leite condensado", "ingrediente", "g", 0, (4, 395, "g", 2760)),
        ("Leite", "ingrediente", "ml", 0, (6, 1, "l", 3000)),
        ("Ovo", "ingrediente", "un", 0, (2, 1, "dz", 2400)),
        ("Caixa de bolo", "embalagem", "un", 0, (10, 1, "un", 2000)),
    ]:
        ids[nome] = est.cadastrar(nome, tipo, un, minimo)
        est.registrar_compra(ids[nome], *compra)
    rid = rec.salvar({"nome": "Bolo simples", "rendimento": 20, "unidade_rendimento": "fatia"},
                     [(ids["Farinha de trigo"], 500), (ids["Leite condensado"], 395), (ids["Leite"], 200),
                      (ids["Ovo"], 4), (ids["Caixa de bolo"], 1)],
                     [("Fita", 50)])
    cid = cli_s.cadastrar({"nome": "Ana Souza", "telefone": "(11) 99999-0000", "email": "ana@exemplo.com"})
    return ids, rid, cid


def saldo(db, iid):
    return db.execute("SELECT saldo FROM insumos WHERE id = ?", (iid,)).fetchone()[0]


def custo(db, iid):
    return db.execute("SELECT custo_medio_centavos FROM insumos WHERE id = ?", (iid,)).fetchone()[0]


# ------------------------------------------------------------------ Estoque
def test_ct01_cadastro_insumo(cli, db):
    r = cli.post("/insumos", data=dict(nome="Farinha de trigo", tipo="ingrediente", unidade_base="g",
                                       estoque_minimo="500"))
    assert r.status_code == 302 and "/compras" in r.location
    row = db.execute("SELECT * FROM insumos").fetchone()
    assert row["nome"] == "Farinha de trigo" and row["saldo"] == 0 and row["estoque_minimo"] == 500


def test_ct02_nome_duplicado(cli, db):
    cli.post("/insumos", data=dict(nome="Farinha de trigo", tipo="ingrediente", unidade_base="g"))
    r = cli.post("/insumos", data=dict(nome="farinha de trigo", tipo="ingrediente", unidade_base="g"),
                 follow_redirects=True)
    assert "Já existe um insumo chamado" in flash(r)
    assert db.execute("SELECT COUNT(*) FROM insumos").fetchone()[0] == 1


def test_ct03_nome_em_branco(cli, db):
    r = cli.post("/insumos", data=dict(nome="  ", tipo="ingrediente", unidade_base="g"), follow_redirects=True)
    assert "Informe o nome do insumo" in flash(r)
    assert db.execute("SELECT COUNT(*) FROM insumos").fetchone()[0] == 0


def test_ct04_embalagem(app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Caixa de bolo", "embalagem", "un")
    assert est.obter(iid)["tipo"] == "embalagem"


def test_ct05_editar(app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Farinha", "ingrediente", "g", 0)
    est.editar(iid, "Farinha de trigo", "ingrediente", "g", 800)
    i = est.obter(iid)
    assert i["nome"] == "Farinha de trigo" and i["estoque_minimo"] == 800


def test_ct06_trocar_unidade_com_compra(app, db):
    from services.util import RegraNegocio
    est = svc(app, db)[0]
    iid = est.cadastrar("Farinha", "ingrediente", "g")
    est.registrar_compra(iid, 1, 1, "kg", 500)
    with pytest.raises(RegraNegocio, match="mudar a unidade"):
        est.editar(iid, "Farinha", "ingrediente", "ml", 0)


def test_ct07_excluir_e_desativar(app, db):
    ids, rid, _ = massa_base(app, db)
    est = svc(app, db)[0]
    livre = est.cadastrar("Sem uso", "outro", "un")
    assert est.excluir(livre) == "excluido"
    assert est.excluir(ids["Farinha de trigo"]) == "desativado"
    nomes_ativos = [i["nome"] for i in est.listar()]
    nomes_todos = [i["nome"] for i in est.listar(incluir_inativos=True)]
    assert "Farinha de trigo" not in nomes_ativos and "Farinha de trigo" in nomes_todos


def test_ct08_compra_custo(app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Farinha de trigo", "ingrediente", "g")
    est.registrar_compra(iid, 2, 1, "kg", 1000)
    assert saldo(db, iid) == 2000 and custo(db, iid) == pytest.approx(0.5)   # 0,5 centavo/g = R$ 5,00/kg
    assert len(est.compras(iid)) == 1


def test_ct09_custo_medio_ponderado(app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Farinha de trigo", "ingrediente", "g")
    est.registrar_compra(iid, 2, 1, "kg", 1000)
    est.registrar_compra(iid, 1, 1, "kg", 800)
    assert saldo(db, iid) == 3000 and custo(db, iid) * 1000 == pytest.approx(600)  # R$ 6,00/kg


def test_ct10_leite_condensado(app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Leite condensado", "ingrediente", "g")
    est.registrar_compra(iid, 4, 395, "g", 2760)
    assert custo(db, iid) * 1000 / 100 == pytest.approx(17.47, abs=0.005)


def test_ct11_duzia(app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Ovo", "ingrediente", "un")
    est.registrar_compra(iid, 2, 1, "dz", 2400)
    assert saldo(db, iid) == 24 and custo(db, iid) == pytest.approx(100)


def test_ct12_unidade_incompativel(cli, app, db):
    iid = svc(app, db)[0].cadastrar("Farinha", "ingrediente", "g")
    r = cli.post("/compras", data=dict(insumo_id=iid, qtd_embalagens="1", tamanho_embalagem="1",
                                       unidade_embalagem="ml", preco_total="5"), follow_redirects=True)
    assert "não é compatível" in flash(r) and saldo(db, iid) == 0


def test_ct13_formatos_de_preco(cli, app, db):
    iid = svc(app, db)[0].cadastrar("Farinha", "ingrediente", "g")
    for preco in ("15,90", "1.234,56"):
        cli.post("/compras", data=dict(insumo_id=iid, qtd_embalagens="1", tamanho_embalagem="1",
                                       unidade_embalagem="kg", preco_total=preco))
    precos = [r[0] for r in db.execute("SELECT preco_total_centavos FROM compras ORDER BY id")]
    assert precos == [1590, 123456]
    r = cli.post("/compras", data=dict(insumo_id=iid, qtd_embalagens="1", tamanho_embalagem="1",
                                       unidade_embalagem="kg", preco_total="abc"), follow_redirects=True)
    assert "Valor inválido" in flash(r)


def test_ct14_quantidade_invalida(cli, app, db):
    iid = svc(app, db)[0].cadastrar("Farinha", "ingrediente", "g")
    for q in ("0", "-2"):
        cli.post("/compras", data=dict(insumo_id=iid, qtd_embalagens=q, tamanho_embalagem="1",
                                       unidade_embalagem="kg", preco_total="5"))
    assert saldo(db, iid) == 0


def test_ct15_ajuste(app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Farinha", "ingrediente", "g")
    est.registrar_compra(iid, 3, 1, "kg", 1800)
    est.ajustar_saldo(iid, 1800)
    assert saldo(db, iid) == 1800
    mov = est.movimentacoes(iid)[0]
    assert mov["origem"] == "ajuste" and mov["quantidade"] == -1200


def test_ct16_ajuste_negativo(app, db):
    from services.util import RegraNegocio
    est = svc(app, db)[0]
    iid = est.cadastrar("Farinha", "ingrediente", "g")
    with pytest.raises(RegraNegocio, match="negativo"):
        est.ajustar_saldo(iid, -5)


def test_ct17_ct18_alerta_minimo(cli, app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Farinha de trigo", "ingrediente", "g", 500)
    est.registrar_compra(iid, 400, 1, "g", 200)
    for pagina in ("/", "/insumos", "/receitas", "/relatorios"):
        html = cli.get(pagina).get_data(as_text=True)
        assert "stock-alert-banner" in html and "Farinha de trigo" in html
    assert "abaixo do mínimo" in cli.get("/insumos").get_data(as_text=True)
    est.registrar_compra(iid, 1, 1, "kg", 500)
    assert "stock-alert-banner" not in cli.get("/").get_data(as_text=True)


def test_ct19_minimo_zero_sem_alerta(cli, app, db):
    svc(app, db)[0].cadastrar("Sal", "ingrediente", "g", 0)
    assert "stock-alert-banner" not in cli.get("/").get_data(as_text=True)


# ------------------------------------------------------------------ Receitas e precificação
def test_ct20_criar_receita(cli, app, db):
    _, rid, _ = massa_base(app, db)
    html = cli.get(f"/receitas/{rid}").get_data(as_text=True)
    assert "Bolo simples" in html and "rende 20 fatia" in html


@pytest.mark.parametrize("rend", ["0", "", "-3"])
def test_ct21_rendimento_invalido(cli, app, db, rend):
    ids, _, _ = massa_base(app, db)
    r = cli.post("/receitas/nova", data={"nome": "X", "rendimento": rend, "item_insumo": [ids["Ovo"]],
                                        "item_qtd": ["1"]}, follow_redirects=True)
    assert "rendimento" in flash(r)
    assert db.execute("SELECT COUNT(*) FROM receitas WHERE nome = 'X'").fetchone()[0] == 0


def test_ct22_receita_duplicada(cli, app, db):
    ids, _, _ = massa_base(app, db)
    r = cli.post("/receitas/nova", data={"nome": "bolo simples", "rendimento": "1",
                                        "item_insumo": [ids["Ovo"]], "item_qtd": ["1"]}, follow_redirects=True)
    assert "Já existe uma receita chamada" in flash(r)


def test_ct23_linhas_dinamicas(cli, app, db):
    ids, _, _ = massa_base(app, db)
    html = cli.get("/receitas/nova").get_data(as_text=True)
    assert html.count('class="ingrediente-row"') == 1 and "+ Adicionar insumo" in html
    valores = list(ids.values())
    r = cli.post("/receitas/nova", data={"nome": "Torta", "rendimento": "8",
                                        "item_insumo": valores + [""], "item_qtd": ["1", "2", "3", "4", "5", ""]})
    rid = int(r.location.rsplit("/", 1)[1])
    assert db.execute("SELECT COUNT(*) FROM receita_itens WHERE receita_id = ?", (rid,)).fetchone()[0] == 5


def test_ct24_sem_insumos(cli, app, db):
    r = cli.post("/receitas/nova", data={"nome": "Vazia", "rendimento": "1"}, follow_redirects=True)
    assert "Adicione ao menos um insumo" in flash(r)


def test_ct25_insumo_repetido(app, db):
    ids, _, _ = massa_base(app, db)
    rec = svc(app, db)[1]
    rid = rec.salvar({"nome": "Pão", "rendimento": 1}, [(ids["Farinha de trigo"], 200),
                                                         (ids["Farinha de trigo"], 300)], [])
    itens = rec.calcular(rid)["itens"]
    assert len(itens) == 1 and itens[0]["quantidade"] == 500


def test_ct26_custo_por_item(app, db):
    _, rid, _ = massa_base(app, db)
    c = {i["nome"]: round(i["custo_centavos"]) for i in svc(app, db)[1].calcular(rid)["itens"]}
    assert c == {"Farinha de trigo": 250, "Leite condensado": 690, "Leite": 100, "Ovo": 400, "Caixa de bolo": 200}


def test_ct27_custo_extra_e_total(app, db):
    _, rid, _ = massa_base(app, db)
    calc = svc(app, db)[1].calcular(rid)
    assert calc["custo_extras"] == 50 and round(calc["custo_total"]) == 1690


def test_ct28_custo_porcao_exibido(cli, app, db):
    _, rid, _ = massa_base(app, db)
    assert svc(app, db)[1].calcular(rid)["custo_porcao"] == pytest.approx(84.5)
    html = cli.get(f"/receitas/{rid}").get_data(as_text=True)
    assert "R$ 0,85" in html, "R$ 0,845 deveria aparecer como R$ 0,85 (arredondamento comercial)"


def test_ct29_sugestoes_de_preco(app, db):
    _, rid, _ = massa_base(app, db)
    c = svc(app, db)[1].calcular(rid)
    assert round(c["preco_sugerido_margem"]) == 169 and round(c["preco_sugerido_cmv"]) == 241
    assert c["preco_venda"] == 169 and not c["preco_definido_manual"]


def _definir_preco(app, db, rid, centavos):
    rec = svc(app, db)[1]
    c = rec.calcular(rid)
    r = dict(c["receita"], preco_venda_centavos=centavos)
    rec.salvar(r, [(i["insumo_id"], i["quantidade"]) for i in c["itens"]],
               [(e["descricao"], e["valor_centavos"]) for e in c["extras"]], rid)


def test_ct30_preco_manual(app, db):
    _, rid, _ = massa_base(app, db)
    _definir_preco(app, db, rid, 250)
    c = svc(app, db)[1].calcular(rid)
    assert c["preco_definido_manual"] and round(c["margem_real"], 1) == 66.2 and round(c["cmv_real"], 1) == 33.8


@pytest.mark.parametrize("cmv", [0, 100])
def test_ct31_cmv_invalido(app, db, cmv):
    from services.util import RegraNegocio
    ids, _, _ = massa_base(app, db)
    with pytest.raises(RegraNegocio, match="CMV"):
        svc(app, db)[1].salvar({"nome": "Y", "rendimento": 1, "cmv_alvo": cmv}, [(ids["Ovo"], 1)], [])


def test_ct32_custo_acompanha_compra(app, db):
    ids, rid, _ = massa_base(app, db)
    est, rec = svc(app, db)[:2]
    antes = rec.calcular(rid)["custo_total"]
    est.registrar_compra(ids["Farinha de trigo"], 2, 1, "kg", 3000)    # bem mais cara
    assert rec.calcular(rid)["custo_total"] > antes


def test_ct33_produzir_lote(app, db):
    ids, rid, _ = massa_base(app, db)
    antes = {n: saldo(db, i) for n, i in ids.items()}
    svc(app, db)[1].produzir(rid, lotes=1)
    gasto = {n: antes[n] - saldo(db, i) for n, i in ids.items()}
    assert gasto == {"Farinha de trigo": 500, "Leite condensado": 395, "Leite": 200, "Ovo": 4, "Caixa de bolo": 1}
    assert db.execute("SELECT custo_centavos FROM producoes").fetchone()[0] == 1690


def test_ct34_produzir_porcoes(app, db):
    ids, rid, _ = massa_base(app, db)
    svc(app, db)[1].produzir(rid, porcoes=10)
    assert saldo(db, ids["Farinha de trigo"]) == 1750 and saldo(db, ids["Ovo"]) == 22


def test_ct35_falta_nao_baixa_nada(app, db):
    from services.util import RegraNegocio
    ids, rid, _ = massa_base(app, db)
    antes = {i: saldo(db, i) for i in ids.values()}
    with pytest.raises(RegraNegocio, match="Estoque insuficiente"):
        svc(app, db)[1].produzir(rid, lotes=5)              # leite condensado só tem 4 latas
    assert {i: saldo(db, i) for i in ids.values()} == antes
    assert db.execute("SELECT COUNT(*) FROM producoes").fetchone()[0] == 0


def test_ct36_editar_mantem_historico(app, db):
    ids, rid, _ = massa_base(app, db)
    rec = svc(app, db)[1]
    rec.produzir(rid, lotes=1)
    rec.salvar({"nome": "Bolo simples", "rendimento": 20}, [(ids["Farinha de trigo"], 600)], [], rid)
    assert len(rec.calcular(rid)["itens"]) == 1
    assert db.execute("SELECT custo_centavos FROM producoes").fetchone()[0] == 1690


def test_ct37_excluir_receita(app, db):
    ids, rid, _ = massa_base(app, db)
    rec = svc(app, db)[1]
    outra = rec.salvar({"nome": "Rascunho", "rendimento": 1}, [(ids["Ovo"], 1)], [])
    assert rec.excluir(outra) == "excluida"
    rec.produzir(rid, lotes=1)
    assert rec.excluir(rid) == "desativada"


# ------------------------------------------------------------------ Encomendas
def test_ct38_cadastrar_cliente(cli, app, db):
    _, _, cid = massa_base(app, db)
    html = cli.get(f"/clientes/{cid}").get_data(as_text=True)
    assert "Ana Souza" in html and "Sem encomendas" in html


def test_ct39_cliente_invalido(cli):
    assert "Informe o nome" in flash(cli.post("/clientes/novo", data={"nome": ""}, follow_redirects=True))
    r = cli.post("/clientes/novo", data={"nome": "X", "email": "ana.exemplo.com"}, follow_redirects=True)
    assert "E-mail inválido" in flash(r)


def test_ct40_editar_cliente(app, db):
    _, _, cid = massa_base(app, db)
    c = svc(app, db)[2]
    c.editar(cid, {"nome": "Ana Souza", "telefone": "(11) 98888-1111", "endereco": "Rua A, 10"})
    assert c.obter(cid)["telefone"] == "(11) 98888-1111" and c.obter(cid)["endereco"] == "Rua A, 10"


@pytest.mark.parametrize("termo", ["Sou", "99999", "exemplo.com"])
def test_ct41_busca(cli, app, db, termo):
    massa_base(app, db)
    assert "Ana Souza" in cli.get(f"/clientes?q={termo}").get_data(as_text=True)


def test_ct42_excluir_cliente(app, db):
    from services.util import RegraNegocio
    _, rid, cid = massa_base(app, db)
    c, e = svc(app, db)[2], svc(app, db)[3]
    livre = c.cadastrar({"nome": "Bruno"})
    c.excluir(livre)
    e.criar(cid, [(rid, 1)])
    with pytest.raises(RegraNegocio, match="não pode ser excluído"):
        c.excluir(cid)


def _encomenda_base(app, db, preco=250, entrega_dias=3):
    ids, rid, cid = massa_base(app, db)
    _definir_preco(app, db, rid, preco)
    entrega = (datetime.date.today() + datetime.timedelta(days=entrega_dias)).isoformat()
    eid = svc(app, db)[3].criar(cid, [(rid, 10)], entrega)
    return ids, rid, cid, eid


def test_ct43_nova_encomenda(app, db):
    _, _, _, eid = _encomenda_base(app, db)
    e = svc(app, db)[3].obter(eid)
    assert e["valor_total_centavos"] == 2500 and e["status"] == "Pendente" and e["status_pagamento"] == "Pendente"


def test_ct44_varios_produtos(cli, app, db):
    ids, rid, cid = massa_base(app, db)
    outra = svc(app, db)[1].salvar({"nome": "Brigadeiro", "rendimento": 30, "preco_venda_centavos": 200},
                                   [(ids["Leite condensado"], 395)], [])
    html = cli.get("/encomendas/nova").get_data(as_text=True)
    assert html.count('class="item-encomenda"') == 1 and "+ Adicionar produto" in html
    r = cli.post("/encomendas/nova", data={"cliente_id": cid, "item_receita": [rid, outra],
                                           "item_qtd": ["10", "5"]})
    eid = int(r.location.rsplit("/", 1)[1])
    d = svc(app, db)[3].detalhes(eid)
    assert len(d["itens"]) == 2
    assert d["encomenda"]["valor_total_centavos"] == sum(i["subtotal"] for i in d["itens"])


def test_ct45_preco_congelado(app, db):
    _, rid, _, eid = _encomenda_base(app, db)
    _definir_preco(app, db, rid, 400)
    assert svc(app, db)[3].obter(eid)["valor_total_centavos"] == 2500


def test_ct46_entrega_antes_do_pedido_e_sem_itens(app, db):
    from services.util import RegraNegocio
    _, rid, cid = massa_base(app, db)
    e = svc(app, db)[3]
    with pytest.raises(RegraNegocio, match="anterior"):
        e.criar(cid, [(rid, 1)], "2020-01-01")
    with pytest.raises(RegraNegocio, match="ao menos um produto"):
        e.criar(cid, [])


def test_ct47_em_producao_baixa(app, db):
    ids, _, _, eid = _encomenda_base(app, db)
    svc(app, db)[3].atualizar_status(eid, "Em Produção")
    assert saldo(db, ids["Farinha de trigo"]) == 1750 and saldo(db, ids["Ovo"]) == 22
    assert svc(app, db)[3].obter(eid)["estoque_baixado"] == 1
    assert db.execute("SELECT encomenda_id FROM producoes").fetchone()[0] == eid


def test_ct48_sem_baixa_dupla(app, db):
    ids, _, _, eid = _encomenda_base(app, db)
    e = svc(app, db)[3]
    for s in ("Em Produção", "Pronto", "Entregue"):
        e.atualizar_status(eid, s)
    assert saldo(db, ids["Farinha de trigo"]) == 1750
    assert e.obter(eid)["data_entregue"] == datetime.date.today().isoformat()


def test_ct49_pendente_direto_entregue(app, db):
    ids, _, _, eid = _encomenda_base(app, db)
    svc(app, db)[3].atualizar_status(eid, "Entregue")
    assert saldo(db, ids["Farinha de trigo"]) == 1750
    assert db.execute("SELECT COUNT(*) FROM producoes").fetchone()[0] == 1


def test_ct50_falta_em_producao(app, db):
    from services.util import RegraNegocio
    ids, rid, cid = massa_base(app, db)
    e = svc(app, db)[3]
    eid = e.criar(cid, [(rid, 200)])                 # 10 lotes
    antes = saldo(db, ids["Farinha de trigo"])
    with pytest.raises(RegraNegocio, match="Estoque insuficiente"):
        e.atualizar_status(eid, "Em Produção")
    assert e.obter(eid)["status"] == "Pendente" and saldo(db, ids["Farinha de trigo"]) == antes


def test_ct51_status_final_bloqueado(app, db):
    from services.util import RegraNegocio
    _, _, _, eid = _encomenda_base(app, db)
    e = svc(app, db)[3]
    e.atualizar_status(eid, "Entregue")
    with pytest.raises(RegraNegocio):
        e.atualizar_status(eid, "Pendente")


def test_ct52_cancelar_apos_baixa(app, db):
    from services.util import RegraNegocio
    _, _, _, eid = _encomenda_base(app, db)
    e = svc(app, db)[3]
    e.atualizar_status(eid, "Em Produção")
    with pytest.raises(RegraNegocio, match="ajuste de estoque"):
        e.atualizar_status(eid, "Cancelado")


def test_ct53_ct54_pagamentos(app, db):
    _, _, _, eid = _encomenda_base(app, db)
    e = svc(app, db)[3]
    e.registrar_pagamento(eid, 1000, "Pix")
    d = e.detalhes(eid)
    assert d["encomenda"]["status_pagamento"] == "Parcial" and d["saldo_devedor"] == 1500
    e.registrar_pagamento(eid, 1500, "Dinheiro")
    assert e.obter(eid)["status_pagamento"] == "Total"


@pytest.mark.parametrize("valor", [2600, 0])
def test_ct55_pagamento_invalido(app, db, valor):
    from services.util import RegraNegocio
    _, _, _, eid = _encomenda_base(app, db)
    with pytest.raises(RegraNegocio):
        svc(app, db)[3].registrar_pagamento(eid, valor)


def test_ct56_status_pago_quita(app, db):
    _, _, _, eid = _encomenda_base(app, db)
    e = svc(app, db)[3]
    e.registrar_pagamento(eid, 1000, "Pix")
    e.atualizar_status(eid, "Pago")
    x = e.obter(eid)
    assert x["status_pagamento"] == "Total" and x["valor_pago_centavos"] == 2500


def test_ct57_historico(app, db):
    _, rid, cid, eid = _encomenda_base(app, db)
    e = svc(app, db)[3]
    e.criar(cid, [(rid, 5)])
    cancelada = e.criar(cid, [(rid, 100)])
    e.atualizar_status(cancelada, "Cancelado")
    h = e.historico_cliente(cid)
    assert len(h["encomendas"]) == 3 and h["total_gasto"] == 2500 + 1250
    assert h["ticket_medio"] == pytest.approx(1875) and h["produtos_preferidos"][0]["qtd"] == 15


def test_ct58_filtro_status(cli, app, db):
    _, rid, cid, eid = _encomenda_base(app, db)
    svc(app, db)[3].atualizar_status(eid, "Pronto")
    assert f"/encomendas/{eid}" in cli.get("/encomendas?status=Pronto").get_data(as_text=True)
    assert f"/encomendas/{eid}" not in cli.get("/encomendas?status=Pendente").get_data(as_text=True)


# ------------------------------------------------------------------ Relatórios
def test_ct59_lucratividade(app, db):
    _encomenda_base(app, db)
    rel = svc(app, db)[4]
    hoje = datetime.date.today().isoformat()
    d = rel.lucratividade(hoje, hoje)
    assert d["receita"] == 2500
    assert d["custo"] == 845, "custo deveria ser R$ 8,45 (10 × R$ 0,845); arredondamento leva a R$ 8,40"


def test_ct60_grafico_presente(cli, app, db):
    _encomenda_base(app, db)
    html = cli.get("/relatorios").get_data(as_text=True)
    assert 'id="lucratividadeChart"' in html and "Bolo simples" in html


def test_ct61_cancelada_fora(app, db):
    _, rid, cid, eid = _encomenda_base(app, db)
    e, rel = svc(app, db)[3], svc(app, db)[4]
    x = e.criar(cid, [(rid, 40)])
    e.atualizar_status(x, "Cancelado")
    hoje = datetime.date.today().isoformat()
    assert rel.lucratividade(hoje, hoje)["receita"] == 2500


def test_ct62_periodo_vazio_e_invertido(cli, app, db):
    html = cli.get("/relatorios?inicio=2000-01-01&fim=2000-01-31").get_data(as_text=True)
    assert "Nenhuma venda" in html or "Sem vendas" in html
    assert 'id="lucratividadeChart"' not in html
    r = cli.get("/relatorios?inicio=2026-12-01&fim=2026-01-01", follow_redirects=True)
    assert "data inicial deve ser anterior" in r.get_data(as_text=True)


def test_ct63_consumo(app, db):
    ids, rid, _, eid = _encomenda_base(app, db)
    svc(app, db)[1].produzir(rid, lotes=1)
    svc(app, db)[3].atualizar_status(eid, "Em Produção")
    hoje = datetime.date.today().isoformat()
    c = {i["nome"]: i for i in svc(app, db)[4].consumo(hoje, hoje)["insumos"]}
    assert c["Farinha de trigo"]["consumido"] == 750
    assert c["Farinha de trigo"]["custo_consumido"] == pytest.approx(375)


def test_ct64_cobertura_e_sugestao(app, db):
    ids, rid, _ = massa_base(app, db)
    svc(app, db)[1].produzir(rid, lotes=1)
    hoje = datetime.date.today()
    ini = (hoje - datetime.timedelta(days=9)).isoformat()
    f = {i["nome"]: i for i in svc(app, db)[4].consumo(ini, hoje.isoformat())["insumos"]}["Farinha de trigo"]
    assert f["media_diaria"] == pytest.approx(50)                         # 500 g em 10 dias
    assert f["dias_cobertura"] == pytest.approx(1500 / 50)
    assert f["sugestao_compra"] == pytest.approx(max(50 * 30 + 500 - 1500, 0))


def test_ct65_compra_nao_e_consumo(app, db):
    massa_base(app, db)
    hoje = datetime.date.today().isoformat()
    assert all(i["consumido"] == 0 for i in svc(app, db)[4].consumo(hoje, hoje)["insumos"])


def test_ct66_kpis(app, db):
    ids, rid, cid = massa_base(app, db)
    e, rel = svc(app, db)[3], svc(app, db)[4]
    hoje = datetime.date.today()
    no_prazo = e.criar(cid, [(rid, 2)], (hoje + datetime.timedelta(days=2)).isoformat())
    atrasada = e.criar(cid, [(rid, 2)], hoje.isoformat())
    e.criar(cid, [(rid, 2)])
    cancel = e.criar(cid, [(rid, 2)])
    e.atualizar_status(no_prazo, "Entregue")
    e.atualizar_status(atrasada, "Entregue")
    db.execute("UPDATE encomendas SET data_entregue = ? WHERE id = ?",
               ((hoje + datetime.timedelta(days=1)).isoformat(), atrasada))
    db.commit()
    e.atualizar_status(cancel, "Cancelado")
    k = rel.kpis(hoje.isoformat(), hoje.isoformat())
    assert round(k["taxa_conclusao"]) == 67 and k["taxa_cancelamento"] == 25 and k["pontualidade"] == 50
    assert k["mais_vendidos"][0]["produto"] == "Bolo simples"


@pytest.mark.parametrize("tipo", ["lucratividade", "consumo", "kpis"])
def test_ct67_csv(cli, app, db, tipo):
    _encomenda_base(app, db)
    r = cli.get(f"/relatorios/{tipo}.csv")
    texto = r.data.decode("utf-8")
    assert r.mimetype == "text/csv" and texto.startswith("﻿")
    linhas = list(csv.reader(io.StringIO(texto.lstrip("﻿")), delimiter=";"))
    assert len(linhas) >= 3 and len(linhas[1]) >= 2
    if tipo == "lucratividade":
        assert any("Bolo simples" in l[0] and "R$ 25,00" in l for l in linhas)


@pytest.mark.parametrize("tipo", ["lucratividade", "consumo", "kpis"])
def test_ct68_pdf(cli, app, db, tipo):
    _encomenda_base(app, db)
    r = cli.get(f"/relatorios/{tipo}.pdf")
    assert r.mimetype == "application/pdf" and r.data[:4] == b"%PDF" and len(r.data) > 1000


def test_ct69_exportar_vazio(cli):
    r = cli.get("/relatorios/lucratividade.csv?inicio=2000-01-01&fim=2000-01-02")
    assert r.status_code == 200 and "Produto;" in r.data.decode("utf-8")


def test_ct70_tela_inicial(cli, app, db):
    _encomenda_base(app, db)
    html = cli.get("/").get_data(as_text=True)
    assert "R$ 25,00" in html and "Encomendas em aberto" in html


# ------------------------------------------------------------------ Não funcionais
def test_nf01_atomicidade(app, db, monkeypatch):
    ids, rid, _ = massa_base(app, db)
    from services.estoque_service import EstoqueService
    original, chamadas = EstoqueService.baixar, {"n": 0}

    def baixar_com_falha(self, *a, **k):
        chamadas["n"] += 1
        if chamadas["n"] == 3:
            raise RuntimeError("falha simulada")
        return original(self, *a, **k)

    monkeypatch.setattr(EstoqueService, "baixar", baixar_com_falha)
    antes = {i: saldo(db, i) for i in ids.values()}
    with pytest.raises(RuntimeError):
        svc(app, db)[1].produzir(rid, lotes=1)
    assert {i: saldo(db, i) for i in ids.values()} == antes
    assert db.execute("SELECT COUNT(*) FROM producoes").fetchone()[0] == 0


def test_nf02_chave_estrangeira(app, db):
    ids, _, _ = massa_base(app, db)
    with pytest.raises(sqlite3.IntegrityError):
        db.execute("DELETE FROM insumos WHERE id = ?", (ids["Farinha de trigo"],))


def test_nf03_centavos(app, db):
    est = svc(app, db)[0]
    iid = est.cadastrar("Bala", "ingrediente", "un")
    for _ in range(3):
        est.registrar_compra(iid, 1, 1, "un", 10)
    assert db.execute("SELECT SUM(preco_total_centavos) FROM compras").fetchone()[0] == 30


def test_nf04_migracao(tmp_path, monkeypatch):
    antigo = tmp_path / "doceria.db"
    c = sqlite3.connect(antigo)
    c.executescript("""
        CREATE TABLE produtos (id INTEGER PRIMARY KEY, nome TEXT, preco REAL, quantidade REAL, unidade TEXT);
        CREATE TABLE receitas (id INTEGER PRIMARY KEY, nome TEXT);
        CREATE TABLE receita_itens (id INTEGER PRIMARY KEY, id_receita INTEGER, nome_insumo TEXT, quantidade REAL);
        INSERT INTO produtos VALUES (1, 'Chocolate Em Pó', 31.46, 4.9, 'Kg'), (2, 'Leite', 0.003, 6400, 'L');
        INSERT INTO receitas VALUES (1, 'Brigadeiro');
        INSERT INTO receita_itens VALUES (1, 1, 'Chocolate Em Pó', 0.05);
    """)
    c.commit(); c.close()
    monkeypatch.setattr(conexao, "DB_PATH", tmp_path / "novo.db")
    monkeypatch.setattr(inicializador, "BANCO_ANTIGO", antigo)
    inicializador.inicializar()
    n = conexao.conectar()
    choc = n.execute("SELECT * FROM insumos WHERE nome = 'Chocolate Em Pó'").fetchone()
    leite = n.execute("SELECT * FROM insumos WHERE nome = 'Leite'").fetchone()
    assert choc["saldo"] == 4900 and choc["unidade_base"] == "g"
    assert leite["saldo"] == 6400 and leite["unidade_base"] == "ml"
    assert n.execute("SELECT quantidade FROM receita_itens").fetchone()[0] == pytest.approx(50)


def test_nf06_escape_html(cli, app, db):
    cli.post("/clientes/novo", data={"nome": "<script>alert(1)</script>"})
    html = cli.get("/clientes").get_data(as_text=True)
    assert "<script>alert(1)</script>" not in html and "&lt;script&gt;" in html
