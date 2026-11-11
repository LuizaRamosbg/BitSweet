"""BitSweet - versão web (Flask). Execute: python app.py  →  http://127.0.0.1:5000"""
import datetime
import os
import secrets

from flask import Flask, Response, abort, flash, g, jsonify, redirect, render_template, request, session, url_for

from database import conexao
from database.conexao import conectar
from database.inicializador import inicializar
from services.encomenda_service import (FORMAS_PAGAMENTO, MESES, STATUS, STATUS_TODOS, ClienteService,
                                        EncomendaService)
from services.estoque_service import TIPOS_INSUMO, EstoqueService
from services.receita_service import ReceitaService
from services.relatorio_service import RelatorioService, periodo_padrao
from services.usuario_service import UsuarioService
from services.util import (UNIDADES, UNIDADES_BASE, RegraNegocio, centavos, data_valida, hoje, numero, reais,
                           rotulo_insumo, unidades_compativeis)


def chave_secreta():
    """Chave que assina o cookie de login: variável BITSWEET_SECRET_KEY ou um arquivo gerado ao lado do banco."""
    if os.environ.get("BITSWEET_SECRET_KEY"):
        return os.environ["BITSWEET_SECRET_KEY"]
    arquivo = conexao.DB_PATH.parent / ".bitsweet_secret_key"
    try:
        return arquivo.read_text(encoding="utf-8").strip()
    except FileNotFoundError:
        chave = secrets.token_hex(32)
        arquivo.parent.mkdir(parents=True, exist_ok=True)
        arquivo.write_text(chave, encoding="utf-8")
        return chave


app = Flask(__name__)
app.config.update(SECRET_KEY=chave_secreta(), SESSION_COOKIE_HTTPONLY=True, SESSION_COOKIE_SAMESITE="Lax",
                  PERMANENT_SESSION_LIFETIME=datetime.timedelta(hours=12))


# ----------------------------------------------------------------- infraestrutura
def db():
    if "db" not in g:
        g.db = conectar()
    return g.db


@app.teardown_appcontext
def fechar_db(_exc):
    conn = g.pop("db", None)
    if conn is not None:
        conn.close()


# ----------------------------------------------------------------- login
ROTAS_PUBLICAS = {"static", "login", "primeiro_acesso"}


@app.before_request
def exigir_login():
    """Todas as telas exigem login. Sem nenhum usuário cadastrado, abre o primeiro acesso."""
    if request.endpoint in ROTAS_PUBLICAS:
        return None
    usuarios = UsuarioService(db())
    if not usuarios.existe_algum():
        return redirect(url_for("primeiro_acesso"))
    uid = session.get("usuario_id")
    usuario = usuarios.repo.buscar(uid) if uid else None
    if not usuario:
        session.clear()
        if request.path.startswith("/api/"):
            return jsonify(erro="Sessão expirada. Entre novamente."), 401
        return redirect(url_for("login", proximo=request.full_path if request.method == "GET" else None))
    g.usuario = usuario
    return None


def _destino_seguro(proximo):
    """Só redireciona para caminhos internos (evita mandar o usuário para outro site após o login)."""
    if proximo and proximo.startswith("/") and not proximo.startswith("//") and "\\" not in proximo:
        return proximo.rstrip("?")
    return url_for("inicio")


def _entrar(usuario):
    session.clear()
    session.permanent = True
    session["usuario_id"] = usuario["id"]


@app.route("/login", methods=["GET", "POST"])
def login():
    usuarios = UsuarioService(db())
    if not usuarios.existe_algum():
        return redirect(url_for("primeiro_acesso"))
    proximo = request.values.get("proximo")
    if request.method == "POST":
        u = usuarios.autenticar(request.form.get("usuario"), request.form.get("senha"))
        if u:
            _entrar(u)
            return redirect(_destino_seguro(proximo))
        flash("Usuário ou senha incorretos.", "error")
        return render_template("login.html", proximo=proximo, usuario=request.form.get("usuario", "")), 401
    return render_template("login.html", proximo=proximo, usuario="")


@app.route("/primeiro-acesso", methods=["GET", "POST"])
def primeiro_acesso():
    usuarios = UsuarioService(db())
    if usuarios.existe_algum():
        return redirect(url_for("login"))
    if request.method == "POST":
        f = request.form
        try:
            uid = usuarios.criar(f.get("usuario"), f.get("senha"), f.get("confirmacao"))
        except RegraNegocio as e:
            flash(str(e), "error")
            return render_template("login.html", primeiro=True, usuario=f.get("usuario", ""))
        _entrar(usuarios.obter(uid))
        flash("Usuário criado. Bem-vindo ao BitSweet!", "success")
        return redirect(url_for("inicio"))
    return render_template("login.html", primeiro=True, usuario="")


@app.post("/logout")
def logout():
    session.clear()
    flash("Você saiu do sistema.", "success")
    return redirect(url_for("login"))


@app.route("/usuarios", methods=["GET", "POST"])
def usuarios():
    svc = UsuarioService(db())
    if request.method == "POST":
        f = request.form
        svc.criar(f.get("usuario"), f.get("senha"), f.get("confirmacao"))
        flash(f"Usuário '{f.get('usuario', '').strip()}' criado.", "success")
        return redirect(url_for("usuarios"))
    return render_template("usuarios.html", usuarios=svc.listar())


@app.post("/usuarios/senha")
def usuario_senha():
    f = request.form
    UsuarioService(db()).alterar_senha(g.usuario["id"], f.get("senha_atual"), f.get("senha"), f.get("confirmacao"))
    flash("Senha alterada.", "success")
    return redirect(url_for("usuarios"))


@app.post("/usuarios/<int:uid>/excluir")
def usuario_excluir(uid):
    UsuarioService(db()).excluir(uid, g.usuario["id"])
    flash("Usuário excluído.", "success")
    return redirect(url_for("usuarios"))


@app.errorhandler(RegraNegocio)
def erro_regra(e):
    flash(str(e), "error")
    return redirect(request.referrer or url_for("inicio"))


app.jinja_env.filters["reais"] = reais
app.jinja_env.filters["num"] = lambda v, casas=2: "—" if v is None else (
    f"{v:,.{casas}f}".replace(",", "X").replace(".", ",").replace("X", ".").rstrip("0").rstrip(",")
    if casas else f"{v:,.0f}".replace(",", "."))
app.jinja_env.filters["data"] = lambda s: (datetime.date.fromisoformat(s[:10]).strftime("%d/%m/%Y") if s else "—")


def custo_unit_legivel(custo_centavos, unidade):
    """RF-03: mostra o custo por unidade num formato amigável (R$/kg, R$/L, R$/un)."""
    if unidade == "g":
        return f"{reais(custo_centavos * 1000)}/kg"
    if unidade == "ml":
        return f"{reais(custo_centavos * 1000)}/L"
    return f"{reais(custo_centavos)}/un"


app.jinja_env.globals["custo_unit"] = custo_unit_legivel
app.jinja_env.globals["rotulo_insumo"] = rotulo_insumo
app.jinja_env.globals["aniversario"] = ClienteService.aniversario_texto
app.jinja_env.globals["MESES"] = MESES


def dias_ate(validade):
    if not validade:
        return None
    return (datetime.date.fromisoformat(validade[:10]) - datetime.date.today()).days


app.jinja_env.globals["dias_ate"] = dias_ate


@app.context_processor
def contexto_global():
    if request.endpoint in ROTAS_PUBLICAS or "usuario" not in g:
        return {}
    est = EstoqueService(db())
    return {"alertas_estoque": est.alertas(),                  # RF-05
            "alertas_validade": est.alertas_validade(),        # lotes vencidos / vencendo
            "hoje": hoje(), "STATUS": STATUS, "usuario_logado": g.usuario}


def form_itens(prefixo_id, prefixo_qtd):
    ids, qtds = request.form.getlist(prefixo_id), request.form.getlist(prefixo_qtd)
    itens = []
    for i, q in zip(ids, qtds):
        if i and q.strip():
            itens.append((int(i), numero(q, "quantidade", minimo=0)))
    return itens


# ----------------------------------------------------------------- início
@app.route("/")
def inicio():
    enc = EncomendaService(db())
    abertas = [e for e in enc.listar() if e["status"] not in ("Entregue", "Cancelado")]
    abertas.sort(key=lambda e: e["data_entrega_prevista"] or "9999")
    ini, fim = periodo_padrao(dias=30)
    return render_template("inicio.html", abertas=abertas[:10], kpis=RelatorioService(db()).kpis(ini, fim),
                           lucro=RelatorioService(db()).lucratividade(ini, fim),
                           aniversariantes=ClienteService(db()).aniversariantes(dias=7))


# ----------------------------------------------------------------- Módulo 1: estoque
@app.route("/insumos")
def insumos():
    mostrar_inativos = request.args.get("inativos") == "1"
    est = EstoqueService(db())
    return render_template("insumos.html", insumos=est.listar(mostrar_inativos), catalogo=est.catalogo(),
                           tipos=TIPOS_INSUMO, unidades=UNIDADES_BASE, mostrar_inativos=mostrar_inativos)


@app.post("/insumos")
def insumo_criar():                                                   # RF-01
    f = request.form
    iid = EstoqueService(db()).cadastrar(f.get("nome"), f.get("tipo"), f.get("unidade_base"),
                                         numero(f.get("estoque_minimo") or "0", "estoque mínimo", 0),
                                         marca=f.get("marca"))
    flash("Insumo cadastrado. Registre uma compra para dar entrada no estoque.", "success")
    return redirect(url_for("compras", insumo=iid))


@app.route("/insumos/<int:iid>")
def insumo_detalhe(iid):
    est = EstoqueService(db())
    todos = request.args.get("esgotados") == "1"
    return render_template("insumo.html", i=est.obter(iid), compras=est.compras(iid),
                           movs=est.movimentacoes(iid), tipos=TIPOS_INSUMO, unidades=UNIDADES_BASE,
                           lotes=est.lotes(iid, incluir_esgotados=todos), mostrar_esgotados=todos,
                           disponivel=est.disponivel(iid))


@app.post("/insumos/<int:iid>/editar")
def insumo_editar(iid):
    f = request.form
    EstoqueService(db()).editar(iid, f.get("nome"), f.get("tipo"), f.get("unidade_base"),
                                numero(f.get("estoque_minimo") or "0", "estoque mínimo", 0), marca=f.get("marca"))
    flash("Insumo atualizado.", "success")
    return redirect(url_for("insumo_detalhe", iid=iid))


@app.post("/insumos/<int:iid>/ajuste")
def insumo_ajuste(iid):
    EstoqueService(db()).ajustar_saldo(iid, numero(request.form.get("saldo"), "saldo", 0),
                                       request.form.get("motivo") or "Ajuste de inventário")
    flash("Saldo ajustado.", "success")
    return redirect(url_for("insumo_detalhe", iid=iid))


@app.post("/insumos/<int:iid>/excluir")
def insumo_excluir(iid):
    est = EstoqueService(db())
    nome = est.obter(iid)["nome"]
    if est.excluir(iid) == "excluido":
        flash(f"Insumo \"{nome}\" excluído com sucesso.", "success")
    else:
        flash(f"O insumo \"{nome}\" já foi usado em compras ou receitas, por isso não foi apagado: ele foi "
              f"desativado e saiu das listas, mas o histórico foi mantido. Use \"Mostrar inativos\" para "
              f"vê-lo ou reativá-lo.", "success")
    return redirect(url_for("insumos"))


@app.post("/lotes/<int:lid>/descartar")
def lote_descartar(lid):
    lote = EstoqueService(db()).descartar_lote(lid, request.form.get("motivo") or "Descarte de lote vencido")
    flash(f"Lote descartado: {lote['saldo']:g} {lote['unidade_base']} de {rotulo_insumo(lote)} saíram do estoque.",
          "success")
    return redirect(request.referrer or url_for("insumo_detalhe", iid=lote["insumo_id"]))


@app.post("/insumos/<int:iid>/reativar")
def insumo_reativar(iid):
    EstoqueService(db()).reativar(iid)
    return redirect(url_for("insumo_detalhe", iid=iid))


@app.route("/compras", methods=["GET", "POST"])
def compras():                                                        # RF-02 / RF-03
    est = EstoqueService(db())
    if request.method == "POST":
        f = request.form
        r = est.registrar_compra(int(f["insumo_id"]), numero(f.get("qtd_embalagens"), "quantidade de embalagens", 0),
                                 numero(f.get("tamanho_embalagem"), "quantidade em cada embalagem", 0),
                                 f.get("unidade_embalagem"), centavos(f.get("preco_total"), "preço total"),
                                 data_valida(f.get("data"), "data", permitir_vazio=True),
                                 (f.get("fornecedor") or "").strip() or None,   # campo antigo (opcional)
                                 validade=data_valida(f.get("validade"), "validade", permitir_vazio=True),
                                 codigo_lote=f.get("codigo_lote"))
        i = est.obter(int(f["insumo_id"]))
        flash(f"Compra registrada: +{r['quantidade_base']:g} {i['unidade_base']} de {rotulo_insumo(i)}. "
              f"Custo desta compra: {custo_unit_legivel(r['custo_unitario_centavos'], i['unidade_base'])} · "
              f"novo custo médio: {custo_unit_legivel(r['custo_medio_centavos'], i['unidade_base'])}.", "success")
        return redirect(url_for("compras"))
    lista = est.listar()
    unid = {i["id"]: unidades_compativeis(i["unidade_base"]) for i in lista}
    return render_template("compras.html", insumos=lista, unidades_por_insumo=unid,
                           recentes=est.compras_recentes(), selecionado=request.args.get("insumo", type=int))


# ----------------------------------------------------------------- Módulos 2 e 3: receitas e preços
@app.route("/receitas")
def receitas():
    return render_template("receitas.html", receitas=ReceitaService(db()).listar())


def _form_receita(receita_id=None):
    svc = ReceitaService(db())
    if request.method == "POST":
        f = request.form
        dados = {"nome": f.get("nome"),
                 "rendimento": numero(f.get("rendimento"), "rendimento", 0),
                 "unidade_rendimento": f.get("unidade_rendimento"),
                 "margem_lucro": numero(f.get("margem_lucro") or "100", "margem de lucro", 0),
                 "cmv_alvo": numero(f.get("cmv_alvo") or "35", "CMV alvo", 0),
                 "preco_venda_centavos": centavos(f.get("preco_venda"), "preço de venda", permitir_vazio=True)}
        extras = [(d, centavos(v, "valor do custo extra"))
                  for d, v in zip(f.getlist("extra_desc"), f.getlist("extra_valor")) if d.strip() and v.strip()]
        rid = svc.salvar(dados, form_itens("item_insumo", "item_qtd"), extras, receita_id)
        flash("Receita salva.", "success")
        return redirect(url_for("receita_detalhe", rid=rid))
    calc = svc.calcular(receita_id) if receita_id else None
    return render_template("receita_form.html", calc=calc, insumos=EstoqueService(db()).listar())


@app.route("/receitas/nova", methods=["GET", "POST"])
def receita_nova():                                                   # RF-06 / RF-07 / RF-10
    return _form_receita()


@app.route("/receitas/<int:rid>/editar", methods=["GET", "POST"])
def receita_editar(rid):
    return _form_receita(rid)


@app.route("/receitas/<int:rid>")
def receita_detalhe(rid):                                             # RF-08 / RF-09 / RF-11
    return render_template("receita.html", c=ReceitaService(db()).calcular(rid))


@app.post("/receitas/<int:rid>/produzir")
def receita_produzir(rid):                                            # RF-12
    svc = ReceitaService(db())
    modo, qtd = request.form.get("modo"), numero(request.form.get("quantidade"), "quantidade", 0)
    svc.produzir(rid, lotes=qtd if modo == "lotes" else None, porcoes=qtd if modo == "porcoes" else None)
    flash("Produção registrada e insumos baixados do estoque.", "success")
    return redirect(url_for("receita_detalhe", rid=rid))


@app.post("/receitas/<int:rid>/excluir")
def receita_excluir(rid):
    svc = ReceitaService(db())
    nome = svc.obter(rid)["nome"]
    if svc.excluir(rid) == "excluida":
        flash(f"Receita \"{nome}\" excluída com sucesso.", "success")
    else:
        flash(f"A receita \"{nome}\" já tem produções ou encomendas, por isso não foi apagada: ela foi "
              f"desativada e saiu da lista, mas o histórico foi mantido.", "success")
    return redirect(url_for("receitas"))


@app.route("/producoes")
def producoes():
    return render_template("producoes.html", producoes=ReceitaService(db()).producoes_recentes())


# ----------------------------------------------------------------- Módulo 4: clientes e encomendas
@app.route("/clientes")
def clientes():
    busca = request.args.get("q", "").strip()
    return render_template("clientes.html", clientes=ClienteService(db()).listar(busca), busca=busca)


@app.route("/clientes/novo", methods=["GET", "POST"])
@app.route("/clientes/<int:cid>/editar", methods=["GET", "POST"])
def cliente_form(cid=None):                                           # RF-13
    svc = ClienteService(db())
    if request.method == "POST":
        if cid:
            svc.editar(cid, request.form)
        else:
            cid = svc.cadastrar(request.form)
        flash("Cliente salvo.", "success")
        return redirect(request.args.get("voltar") or url_for("cliente_detalhe", cid=cid))
    return render_template("cliente_form.html", c=svc.obter(cid) if cid else None)


@app.route("/clientes/<int:cid>")
def cliente_detalhe(cid):                                             # RF-17
    return render_template("cliente.html", h=EncomendaService(db()).historico_cliente(cid))


@app.post("/clientes/<int:cid>/excluir")
def cliente_excluir(cid):
    ClienteService(db()).excluir(cid)
    flash("Cliente excluído.", "success")
    return redirect(url_for("clientes"))


@app.route("/encomendas")
def encomendas():
    status = request.args.get("status") or None
    return render_template("encomendas.html", encomendas=EncomendaService(db()).listar(status),
                           status_sel=status, status_todos=STATUS_TODOS)


@app.route("/encomendas/nova", methods=["GET", "POST"])
def encomenda_nova():                                                 # RF-14
    if request.method == "POST":
        f = request.form
        svc = EncomendaService(db())
        itens = form_itens("item_receita", "item_qtd")
        eid = svc.criar(int(f["cliente_id"]), itens, f.get("data_entrega"), f.get("observacoes"), f.get("data_pedido"))
        flash("Encomenda registrada.", "success")
        faltas = svc.faltas_estoque(itens, svc.obter(eid)["data_entrega_prevista"], ignorar_id=eid)
        if faltas:
            flash("Atenção: o estoque não é suficiente para produzir esta encomenda. Compre antes de colocá-la "
                  "em produção:\n• " + svc.texto_faltas(faltas), "warning")
        return redirect(url_for("encomenda_detalhe", eid=eid))
    receitas_calc = ReceitaService(db()).listar()
    return render_template("encomenda_form.html", clientes=ClienteService(db()).listar(),
                           receitas=receitas_calc, cliente_sel=request.args.get("cliente", type=int))


@app.post("/api/encomendas/faltas-estoque")
def encomenda_faltas_estoque():
    """Alerta ao vivo na tela de nova encomenda: insumos que faltam para os itens já preenchidos."""
    itens = []
    for rid, qtd in zip(request.form.getlist("item_receita"), request.form.getlist("item_qtd")):
        try:
            itens.append((int(rid), numero(qtd, "quantidade", minimo=0)))
        except (ValueError, RegraNegocio):
            continue                                   # linha incompleta: ignora até ser preenchida
    try:
        data_entrega = data_valida(request.form.get("data_entrega"), permitir_vazio=True)
    except RegraNegocio:
        data_entrega = None
    try:
        faltas = EncomendaService(db()).faltas_estoque(itens, data_entrega)
    except RegraNegocio as e:
        return jsonify(erro=str(e)), 400
    return jsonify(faltas=faltas)


@app.route("/encomendas/<int:eid>")
def encomenda_detalhe(eid):
    return render_template("encomenda.html", d=EncomendaService(db()).detalhes(eid),
                           status_todos=STATUS_TODOS, formas=FORMAS_PAGAMENTO)


@app.post("/encomendas/<int:eid>/status")
def encomenda_status(eid):                                            # RF-15 (+ RF-12)
    EncomendaService(db()).atualizar_status(eid, request.form.get("status"))
    flash(f"Status atualizado para {request.form.get('status')}.", "success")
    return redirect(url_for("encomenda_detalhe", eid=eid))


@app.post("/encomendas/<int:eid>/pagamento")
def encomenda_pagamento(eid):                                         # RF-16
    f = request.form
    EncomendaService(db()).registrar_pagamento(eid, centavos(f.get("valor"), "valor"), f.get("forma"),
                                               f.get("data") or None)
    flash("Pagamento registrado.", "success")
    return redirect(url_for("encomenda_detalhe", eid=eid))


# ----------------------------------------------------------------- Módulo 5: relatórios
@app.route("/relatorios")
def relatorios():                                                     # RF-18 / RF-19 / RF-20
    ini, fim = periodo_padrao(request.args.get("inicio"), request.args.get("fim"))
    rel = RelatorioService(db())
    return render_template("relatorios.html", inicio=ini, fim=fim, lucro=rel.lucratividade(ini, fim),
                           consumo=rel.consumo(ini, fim), kpis=rel.kpis(ini, fim))


@app.route("/relatorios/<tipo>.<fmt>")
def relatorio_exportar(tipo, fmt):                                    # RF-21
    if tipo not in ("lucratividade", "consumo", "kpis") or fmt not in ("csv", "pdf"):
        abort(404)
    ini, fim = periodo_padrao(request.args.get("inicio"), request.args.get("fim"))
    rel = RelatorioService(db())
    nome = f"bitsweet_{tipo}_{ini}_{fim}.{fmt}"
    if fmt == "csv":
        return Response(rel.exportar_csv(tipo, ini, fim), mimetype="text/csv",
                        headers={"Content-Disposition": f"attachment; filename={nome}"})
    return Response(rel.exportar_pdf(tipo, ini, fim), mimetype="application/pdf",
                    headers={"Content-Disposition": f"attachment; filename={nome}"})


inicializar()

if __name__ == "__main__":
    app.run(host=os.environ.get("HOST", "127.0.0.1"), port=int(os.environ.get("PORT", "5000")), debug=os.environ.get("FLASK_DEBUG", "1") != "0")
