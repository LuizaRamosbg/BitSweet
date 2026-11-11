"""Login com senha, marca dos insumos, aniversário de clientes e alerta de estoque na encomenda."""
import datetime
import sqlite3

import pytest

import database.conexao as conexao
from database import inicializador
from services.util import RegraNegocio
from tests.test_plano import SENHA, USUARIO, app, cli, cli_anonimo, db, massa_base, svc  # noqa: F401


# ------------------------------------------------------------------ login
def test_login_primeiro_acesso_cria_usuario(cli_anonimo, db):
    r = cli_anonimo.get("/insumos")
    assert r.status_code == 302 and "/primeiro-acesso" in r.location
    r = cli_anonimo.post("/primeiro-acesso", data={"usuario": "dona.maria", "senha": "123456",
                                                   "confirmacao": "123456"})
    assert r.status_code == 302
    assert cli_anonimo.get("/insumos").status_code == 200                 # já entrou
    hash_ = db.execute("SELECT senha_hash FROM usuarios").fetchone()[0]
    assert "123456" not in hash_                                          # senha nunca em texto puro
    # depois do primeiro usuário, o primeiro acesso não cria mais ninguém
    assert "/login" in cli_anonimo.post("/primeiro-acesso", data={"usuario": "x", "senha": "abcdef",
                                                                  "confirmacao": "abcdef"}).location
    assert db.execute("SELECT COUNT(*) FROM usuarios").fetchone()[0] == 1


def test_login_exigido_e_senha_errada(cli, cli_anonimo):
    r = cli_anonimo.get("/encomendas?status=Pronto")
    assert r.status_code == 302 and "/login" in r.location
    r = cli_anonimo.post("/login", data={"usuario": USUARIO, "senha": "errada"})
    assert r.status_code == 401 and "Usuário ou senha incorretos" in r.get_data(as_text=True)
    assert cli_anonimo.get("/").status_code == 302
    r = cli_anonimo.post("/login", data={"usuario": USUARIO.upper(), "senha": SENHA, "proximo": "/clientes"})
    assert r.status_code == 302 and r.location.endswith("/clientes")


def test_login_nao_redireciona_para_fora(cli, cli_anonimo):
    for destino in ("https://exemplo.com", "//exemplo.com", "/\\exemplo.com"):
        r = cli_anonimo.post("/login", data={"usuario": USUARIO, "senha": SENHA, "proximo": destino})
        assert r.location.endswith("/"), destino


def test_logout(cli):
    assert cli.get("/").status_code == 200
    cli.post("/logout")
    assert "/login" in cli.get("/").location


def test_usuarios_criar_trocar_senha_excluir(cli, db):
    r = cli.post("/usuarios", data={"usuario": "ajudante", "senha": "abc", "confirmacao": "abc"},
                 follow_redirects=True)
    assert "pelo menos 6" in r.get_data(as_text=True)
    cli.post("/usuarios", data={"usuario": "ajudante", "senha": "abcdef", "confirmacao": "abcdef"})
    uid = db.execute("SELECT id FROM usuarios WHERE usuario = 'ajudante'").fetchone()[0]
    r = cli.post("/usuarios/senha", data={"senha_atual": "errada", "senha": "nova-senha", "confirmacao": "nova-senha"},
                 follow_redirects=True)
    assert "senha atual está incorreta" in r.get_data(as_text=True)
    cli.post("/usuarios/senha", data={"senha_atual": SENHA, "senha": "nova-senha", "confirmacao": "nova-senha"})
    from services.usuario_service import UsuarioService
    assert UsuarioService(db).autenticar(USUARIO, "nova-senha")
    cli.post(f"/usuarios/{uid}/excluir")
    assert db.execute("SELECT COUNT(*) FROM usuarios").fetchone()[0] == 1
    proprio = db.execute("SELECT id FROM usuarios").fetchone()[0]
    r = cli.post(f"/usuarios/{proprio}/excluir", follow_redirects=True)
    assert "não pode excluir o próprio" in r.get_data(as_text=True)


# ------------------------------------------------------------------ marca dos insumos
def test_marca_mesmo_nome_e_edicao(app, db):
    est = svc(app, db)[0]
    a = est.cadastrar("Leite condensado", "ingrediente", "g", marca="Moça")
    b = est.cadastrar("Leite condensado", "ingrediente", "g", marca="Italac")
    with pytest.raises(RegraNegocio, match="marca diferente"):
        est.cadastrar("Leite Condensado", "ingrediente", "g", marca="moca")      # acento/maiúscula não contam
    with pytest.raises(RegraNegocio, match="marca diferente"):
        est.editar(b, "Leite condensado", "ingrediente", "g", 0, marca="Moça")   # edição também valida
    with pytest.raises(RegraNegocio, match="Informe a marca"):
        est.editar(a, "Leite condensado", "ingrediente", "g", 0, marca="")
    assert {i["marca"] for i in est.catalogo()} == {"Moça", "Italac"}


def test_migracao_marca_vem_do_fornecedor(tmp_path, monkeypatch):
    """Banco antigo (nome UNIQUE, sem marca): a marca é preenchida com o fornecedor da última compra."""
    caminho = tmp_path / "antigo.db"
    c = sqlite3.connect(caminho)
    c.executescript("""
        CREATE TABLE insumos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL UNIQUE COLLATE NOCASE,
            tipo TEXT NOT NULL DEFAULT 'ingrediente', unidade_base TEXT NOT NULL, saldo REAL NOT NULL DEFAULT 0,
            custo_medio_centavos REAL NOT NULL DEFAULT 0, estoque_minimo REAL NOT NULL DEFAULT 0,
            ativo INTEGER NOT NULL DEFAULT 1);
        CREATE TABLE compras (id INTEGER PRIMARY KEY AUTOINCREMENT, insumo_id INTEGER NOT NULL REFERENCES insumos(id),
            data TEXT NOT NULL, qtd_embalagens REAL NOT NULL, tamanho_embalagem REAL NOT NULL,
            unidade_embalagem TEXT NOT NULL, quantidade_base REAL NOT NULL, preco_total_centavos INTEGER NOT NULL,
            fornecedor TEXT);
        CREATE TABLE clientes (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, telefone TEXT, email TEXT,
            endereco TEXT, observacoes TEXT);
        INSERT INTO insumos (nome, unidade_base) VALUES ('Farinha', 'g'), ('Sal', 'g');
        INSERT INTO compras (insumo_id, data, qtd_embalagens, tamanho_embalagem, unidade_embalagem, quantidade_base,
            preco_total_centavos, fornecedor) VALUES (1, '2026-01-01', 1, 1, 'kg', 1000, 500, 'Atacadão'),
                                                     (1, '2026-02-01', 1, 1, 'kg', 1000, 500, 'Dona Benta');
        INSERT INTO clientes (nome) VALUES ('Ana');
    """)
    c.commit(); c.close()
    monkeypatch.setattr(conexao, "DB_PATH", caminho)
    monkeypatch.setattr(inicializador, "BANCO_ANTIGO", tmp_path / "nao_existe.db")
    inicializador.inicializar()
    inicializador.inicializar()                                   # idempotente
    n = conexao.conectar()
    assert dict(n.execute("SELECT nome, marca FROM insumos").fetchall()) == {"Farinha": "Dona Benta", "Sal": ""}
    n.execute("INSERT INTO insumos (nome, marca, unidade_base) VALUES ('Farinha', 'Renata', 'g')")  # sem UNIQUE no nome
    assert n.execute("PRAGMA foreign_key_check").fetchall() == []
    assert "aniversario_dia" in [r[1] for r in n.execute("PRAGMA table_info(clientes)")]
    n.close()


# ------------------------------------------------------------------ aniversário de cliente
def test_aniversario_opcional_e_validado(cli, app, db):
    c = svc(app, db)[2]
    sem = c.cadastrar({"nome": "Sem data"})
    assert c.obter(sem)["aniversario_dia"] is None
    cid = c.cadastrar({"nome": "Bia", "aniversario_dia": "29", "aniversario_mes": "2"})
    assert (c.obter(cid)["aniversario_dia"], c.obter(cid)["aniversario_mes"]) == (29, 2)
    for dia, mes in (("31", "4"), ("10", ""), ("", "5"), ("0", "1")):
        with pytest.raises(RegraNegocio):
            c.cadastrar({"nome": "X", "aniversario_dia": dia, "aniversario_mes": mes})
    html = cli.get(f"/clientes/{cid}").get_data(as_text=True)
    assert "29 de fevereiro" in html
    assert "29/02" in cli.get("/clientes").get_data(as_text=True)
    cli.post(f"/clientes/{cid}/editar", data={"nome": "Bia", "aniversario_dia": "", "aniversario_mes": ""})
    assert c.obter(cid)["aniversario_dia"] is None


def test_aniversariantes_da_semana(cli, app, db):
    c = svc(app, db)[2]
    hoje = datetime.date.today()
    em3 = hoje + datetime.timedelta(days=3)
    c.cadastrar({"nome": "Carla", "aniversario_dia": str(em3.day), "aniversario_mes": str(em3.month)})
    longe = hoje + datetime.timedelta(days=40)
    c.cadastrar({"nome": "Duda", "aniversario_dia": str(longe.day), "aniversario_mes": str(longe.month)})
    nomes = [a["nome"] for a in c.aniversariantes(7)]
    assert nomes == ["Carla"]
    assert "Carla" in cli.get("/").get_data(as_text=True)
    # virada de ano e 29/02 fora de ano bissexto
    c.cadastrar({"nome": "Eva", "aniversario_dia": "2", "aniversario_mes": "1"})
    c.cadastrar({"nome": "Fê", "aniversario_dia": "29", "aniversario_mes": "2"})
    assert [a["nome"] for a in c.aniversariantes(7, data="2026-12-30")] == ["Eva"]
    assert [a["nome"] for a in c.aniversariantes(0, data="2027-02-28")] == ["Fê"]


# ------------------------------------------------------------------ alerta de estoque na encomenda
def test_alerta_estoque_ao_vivo(cli, app, db):
    ids, rid, cid = massa_base(app, db)          # Bolo simples: 20 fatias; 4 ovos por receita; 24 ovos em estoque
    r = cli.post("/api/encomendas/faltas-estoque", data={"item_receita": [rid], "item_qtd": ["20"]})
    assert r.get_json()["faltas"] == []
    r = cli.post("/api/encomendas/faltas-estoque", data={"item_receita": [rid, ""], "item_qtd": ["200", ""]})
    faltas = {f["nome"]: f for f in r.get_json()["faltas"]}
    assert faltas["Ovo · Marca Teste"]["necessario"] == pytest.approx(40)
    assert faltas["Ovo · Marca Teste"]["falta"] == pytest.approx(16)
    assert "Leite · Marca Teste" not in faltas                  # 2000 ml necessários, 6000 ml em estoque


def test_alerta_estoque_considera_outras_encomendas(cli, app, db):
    ids, rid, cid = massa_base(app, db)
    enc = svc(app, db)[3]
    enc.criar(cid, [(rid, 100)])                                # reserva 20 ovos de 24 (ainda não produzida)
    r = cli.post("/encomendas/nova", data={"cliente_id": cid, "item_receita": [rid], "item_qtd": ["40"]},
                 follow_redirects=True)
    html = r.get_data(as_text=True)
    assert "Encomenda registrada" in html                        # o alerta não impede o cadastro
    assert "flash-warning" in html and "Ovo · Marca Teste" in html and "reservado" in html
    eid = db.execute("SELECT MAX(id) FROM encomendas").fetchone()[0]
    assert "Estoque insuficiente para produzir" in cli.get(f"/encomendas/{eid}").get_data(as_text=True)


def test_alerta_estoque_some_quando_ha_estoque(cli, app, db):
    ids, rid, cid = massa_base(app, db)
    r = cli.post("/encomendas/nova", data={"cliente_id": cid, "item_receita": [rid], "item_qtd": ["10"]},
                 follow_redirects=True)
    assert "flash-warning" not in r.get_data(as_text=True)
    assert "Estoque insuficiente" not in r.get_data(as_text=True)


def test_api_exige_login(cli_anonimo, cli):
    r = cli_anonimo.post("/api/encomendas/faltas-estoque", data={})
    assert r.status_code == 401
