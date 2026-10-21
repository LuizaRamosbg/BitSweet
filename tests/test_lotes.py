"""Lotes com validade e consumo FEFO (primeiro a vencer, primeiro a sair)."""
import datetime
import sqlite3

import pytest

import database.conexao as conexao
from database import inicializador
from services.util import RegraNegocio
from tests.test_plano import app, cli, db, svc  # noqa: F401  (fixtures compartilhadas)

HOJE = datetime.date.today()


def dia(n):
    return (HOJE + datetime.timedelta(days=n)).isoformat()


def farinha(app, db, minimo=0):
    est = svc(app, db)[0]
    return est, est.cadastrar("Farinha de trigo", "ingrediente", "g", minimo)


def compra(est, iid, gramas, preco_centavos, validade=None, data=None, codigo=None):
    return est.registrar_compra(iid, 1, gramas, "g", preco_centavos, data=data, validade=validade,
                                codigo_lote=codigo)["lote_id"]


def saldos_lotes(db, iid):
    return {r["id"]: r["saldo"] for r in db.execute("SELECT id, saldo FROM lotes WHERE insumo_id = ?", (iid,))}


def test_l01_compra_cria_lote(app, db):
    est, iid = farinha(app, db)
    lid = compra(est, iid, 1000, 500, validade=dia(30), codigo="L-2210")
    lote = db.execute("SELECT * FROM lotes WHERE id = ?", (lid,)).fetchone()
    assert lote["validade"] == dia(30) and lote["codigo"] == "L-2210"
    assert lote["saldo"] == lote["quantidade_inicial"] == 1000 and lote["custo_unit_centavos"] == 0.5
    assert est.obter(iid)["saldo"] == 1000


def test_l02_fefo_consome_quem_vence_antes(app, db):
    est, iid = farinha(app, db)
    tardio = compra(est, iid, 1000, 500, validade=dia(60))          # comprado primeiro, vence depois
    cedo = compra(est, iid, 1000, 500, validade=dia(10))            # comprado depois, vence antes
    with db:
        est.baixar(iid, 300, "producao")
    s = saldos_lotes(db, iid)
    assert s[cedo] == 700 and s[tardio] == 1000
    mov = db.execute("SELECT lote_id FROM movimentacoes WHERE origem = 'producao'").fetchall()
    assert [m["lote_id"] for m in mov] == [cedo]


def test_l03_consumo_atravessa_lotes_em_ordem(app, db):
    est, iid = farinha(app, db)
    a = compra(est, iid, 500, 250, validade=dia(5))
    b = compra(est, iid, 500, 250, validade=dia(20))
    c = compra(est, iid, 500, 250, validade=dia(40))
    with db:
        est.baixar(iid, 800, "producao")
    assert saldos_lotes(db, iid) == {a: 0, b: 200, c: 500}
    movs = db.execute("SELECT lote_id, quantidade FROM movimentacoes WHERE origem = 'producao' ORDER BY id").fetchall()
    assert [(m["lote_id"], m["quantidade"]) for m in movs] == [(a, -500), (b, -300)]


def test_l04_sem_validade_e_o_ultimo(app, db):
    est, iid = farinha(app, db)
    sem = compra(est, iid, 1000, 500)                               # sem validade
    com = compra(est, iid, 1000, 500, validade=dia(90))
    with db:
        est.baixar(iid, 1200, "producao")
    assert saldos_lotes(db, iid) == {com: 0, sem: 800}


def test_l05_lote_vencido_nao_e_usado(app, db):
    est, iid = farinha(app, db)
    vencido = compra(est, iid, 1000, 500, data=dia(-20), validade=dia(-1))
    bom = compra(est, iid, 300, 150, validade=dia(15))
    assert est.disponivel(iid) == 300 and est.obter(iid)["saldo"] == 1300
    with pytest.raises(RegraNegocio, match="1000 g em lotes vencidos"):
        with db:
            est.baixar(iid, 500, "producao")
    assert saldos_lotes(db, iid) == {vencido: 1000, bom: 300}       # nada foi baixado
    with db:
        est.baixar(iid, 300, "producao")
    assert saldos_lotes(db, iid) == {vencido: 1000, bom: 0}


def test_l06_validade_anterior_a_compra(app, db):
    est, iid = farinha(app, db)
    with pytest.raises(RegraNegocio, match="já chega vencido"):
        compra(est, iid, 1000, 500, validade=dia(-1))
    assert db.execute("SELECT COUNT(*) FROM lotes").fetchone()[0] == 0


def test_l07_descartar_lote(app, db):
    est, iid = farinha(app, db)
    vencido = compra(est, iid, 1000, 500, data=dia(-20), validade=dia(-1))
    compra(est, iid, 300, 150, validade=dia(15))
    est.descartar_lote(vencido)
    assert saldos_lotes(db, iid)[vencido] == 0 and est.obter(iid)["saldo"] == 300
    mov = db.execute("SELECT * FROM movimentacoes WHERE lote_id = ? AND quantidade < 0", (vencido,)).fetchone()
    assert mov["origem"] == "ajuste" and mov["quantidade"] == -1000 and "Descarte" in mov["observacao"]
    with pytest.raises(RegraNegocio, match="zerado"):
        est.descartar_lote(vencido)


def test_l08_alertas_de_validade(cli, app, db):
    est, iid = farinha(app, db)
    compra(est, iid, 100, 50, data=dia(-20), validade=dia(-2), codigo="VENCIDO")
    compra(est, iid, 100, 50, validade=dia(3))
    compra(est, iid, 100, 50, validade=dia(30))
    alertas = est.alertas_validade()
    assert [a["dias"] for a in alertas] == [-2, 3]
    for pagina in ("/", "/insumos", "/receitas"):
        html = cli.get(pagina).get_data(as_text=True)
        assert "validade-alert-banner" in html and "1 lote(s) vencido(s)" in html and "vence em 3 dia(s)" in html


def test_l09_ajuste_de_inventario(app, db):
    est, iid = farinha(app, db)
    vencido = compra(est, iid, 200, 100, data=dia(-20), validade=dia(-1))
    bom = compra(est, iid, 800, 400, validade=dia(30))
    est.ajustar_saldo(iid, 700)                                     # faltam 300: sai o vencido, depois o bom
    assert saldos_lotes(db, iid) == {vencido: 0, bom: 700}
    est.ajustar_saldo(iid, 900)                                     # sobram 200: vira lote sem validade
    novo = db.execute("SELECT * FROM lotes WHERE codigo = 'Ajuste de inventário'").fetchone()
    assert novo["saldo"] == 200 and novo["validade"] is None and est.obter(iid)["saldo"] == 900


def test_l10_custo_medio_acompanha_os_lotes(app, db):
    est, iid = farinha(app, db)
    compra(est, iid, 1000, 500, validade=dia(5))                    # R$ 5,00/kg, vence antes
    compra(est, iid, 1000, 1000, validade=dia(50))                  # R$ 10,00/kg
    assert est.obter(iid)["custo_medio_centavos"] == pytest.approx(0.75)
    with db:
        est.baixar(iid, 1000, "producao")
    assert est.obter(iid)["custo_medio_centavos"] == pytest.approx(1.0)
    custo_mov = db.execute("SELECT custo_unit_centavos FROM movimentacoes WHERE origem = 'producao'").fetchone()[0]
    assert custo_mov == pytest.approx(0.5)                          # consumo valorizado pelo lote usado


def test_l11_receita_e_minimo_ignoram_vencidos(cli, app, db):
    est, rec = svc(app, db)[:2]
    iid = est.cadastrar("Ovo", "ingrediente", "un", 6)
    est.registrar_compra(iid, 1, 12, "un", 1200, data=dia(-30), validade=dia(-1))
    est.registrar_compra(iid, 1, 4, "un", 400, validade=dia(10))
    rid = rec.salvar({"nome": "Omelete", "rendimento": 1}, [(iid, 2)], [])
    c = rec.calcular(rid)
    assert c["lotes_possiveis"] == 2 and c["itens"][0]["disponivel"] == 4
    with pytest.raises(RegraNegocio, match="dentro da validade"):
        rec.produzir(rid, lotes=3)
    assert [a["nome"] for a in est.alertas()] == ["Ovo"]           # 4 disponíveis <= mínimo 6


def test_l12_encomenda_em_producao_usa_fefo(app, db):
    est, rec, cli_s, enc, _ = svc(app, db)
    iid = est.cadastrar("Leite condensado", "ingrediente", "g")
    tardio = est.registrar_compra(iid, 2, 395, "g", 1400, validade=dia(120))["lote_id"]
    cedo = est.registrar_compra(iid, 2, 395, "g", 1400, validade=dia(20))["lote_id"]
    rid = rec.salvar({"nome": "Brigadeiro", "rendimento": 30, "preco_venda_centavos": 200}, [(iid, 395)], [])
    cid = cli_s.cadastrar({"nome": "Ana"})
    eid = enc.criar(cid, [(rid, 30)])
    enc.atualizar_status(eid, "Em Produção")
    assert saldos_lotes(db, iid) == {tardio: 790, cedo: 395}


def test_l13_tela_de_compra_e_lotes(cli, app, db):
    _, iid = farinha(app, db)
    cli.post("/compras", data=dict(insumo_id=iid, qtd_embalagens="1", tamanho_embalagem="1", unidade_embalagem="kg",
                                   preco_total="5", validade=dia(40), codigo_lote="ABC"))
    cli.post("/compras", data=dict(insumo_id=iid, qtd_embalagens="1", tamanho_embalagem="1", unidade_embalagem="kg",
                                   preco_total="5", validade=dia(10), codigo_lote="XYZ"))
    html = cli.get(f"/insumos/{iid}").get_data(as_text=True)
    assert html.index("XYZ") < html.index("ABC")                    # o que vence antes aparece em 1º
    assert "1º" in html and "2º" in html
    r = cli.post("/compras", data=dict(insumo_id=iid, qtd_embalagens="1", tamanho_embalagem="1",
                                       unidade_embalagem="kg", preco_total="5", validade="31/12/2026"),
                 follow_redirects=True)
    assert "Data inválida" in r.get_data(as_text=True)
    lote = db.execute("SELECT id FROM lotes WHERE codigo = 'XYZ'").fetchone()["id"]
    r = cli.post(f"/lotes/{lote}/descartar", follow_redirects=True)
    assert "Lote descartado" in r.get_data(as_text=True)


def test_l14_migracao_de_banco_sem_lotes(tmp_path, monkeypatch):
    """Banco criado antes desta versão: sem tabela de lotes e sem coluna lote_id."""
    caminho = tmp_path / "antigo.db"
    c = sqlite3.connect(caminho)
    c.executescript("""
        CREATE TABLE insumos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL UNIQUE COLLATE NOCASE,
            tipo TEXT NOT NULL DEFAULT 'ingrediente', unidade_base TEXT NOT NULL, saldo REAL NOT NULL DEFAULT 0,
            custo_medio_centavos REAL NOT NULL DEFAULT 0, estoque_minimo REAL NOT NULL DEFAULT 0,
            ativo INTEGER NOT NULL DEFAULT 1);
        CREATE TABLE movimentacoes (id INTEGER PRIMARY KEY AUTOINCREMENT, insumo_id INTEGER NOT NULL,
            data TEXT NOT NULL, quantidade REAL NOT NULL, custo_unit_centavos REAL NOT NULL DEFAULT 0,
            origem TEXT NOT NULL, referencia_id INTEGER, observacao TEXT);
        INSERT INTO insumos (nome, unidade_base, saldo, custo_medio_centavos) VALUES ('Açúcar', 'g', 2500, 0.4);
    """)
    c.commit(); c.close()
    monkeypatch.setattr(conexao, "DB_PATH", caminho)
    monkeypatch.setattr(inicializador, "BANCO_ANTIGO", tmp_path / "nao_existe.db")
    inicializador.inicializar()
    inicializador.inicializar()                                     # rodar de novo não duplica nada
    n = conexao.conectar()
    assert "lote_id" in [r[1] for r in n.execute("PRAGMA table_info(movimentacoes)")]
    lotes = n.execute("SELECT * FROM lotes").fetchall()
    assert len(lotes) == 1 and lotes[0]["saldo"] == 2500 and lotes[0]["validade"] is None
    assert lotes[0]["codigo"] == "Saldo anterior" and lotes[0]["custo_unit_centavos"] == 0.4


def test_l15_custo_medio_ignora_vencidos(app, db):
    est, iid = farinha(app, db)
    compra(est, iid, 1000, 500, data=dia(-20), validade=dia(-1))   # R$ 5,00/kg, vencido
    compra(est, iid, 1000, 1000, validade=dia(30))                 # R$ 10,00/kg
    assert est.obter(iid)["custo_medio_centavos"] == pytest.approx(1.0)
