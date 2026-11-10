"""BitSweet - versão web (Flask). Execute: python app.py  →  http://127.0.0.1:5000"""
import datetime
import os

from flask import Flask, Response, abort, flash, g, redirect, render_template, request, url_for

from database.conexao import conectar
from database.inicializador import inicializar
from services.encomenda_service import (FORMAS_PAGAMENTO, STATUS, STATUS_TODOS, ClienteService,
                                        EncomendaService)
from services.estoque_service import TIPOS_INSUMO, EstoqueService
from services.receita_service import ReceitaService
from services.relatorio_service import RelatorioService, periodo_padrao
from services.util import (UNIDADES, UNIDADES_BASE, RegraNegocio, centavos, data_valida, hoje, numero, reais,
                           unidades_compativeis)

app = Flask(__name__)
app.config["SECRET_KEY"] = "bitsweet-dev"   # troque em produção


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


def dias_ate(validade):
    if not validade:
        return None
    return (datetime.date.fromisoformat(validade[:10]) - datetime.date.today()).days


app.jinja_env.globals["dias_ate"] = dias_ate


@app.context_processor
def contexto_global():
    if request.endpoint == "static":
        return {}
    est = EstoqueService(db())
    return {"alertas_estoque": est.alertas(),                  # RF-05
            "alertas_validade": est.alertas_validade(),        # lotes vencidos / vencendo
            "hoje": hoje(), "STATUS": STATUS}


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
                           lucro=RelatorioService(db()).lucratividade(ini, fim))


# ----------------------------------------------------------------- Módulo 1: estoque
@app.route("/insumos")
def insumos():
    mostrar_inativos = request.args.get("inativos") == "1"
    return render_template("insumos.html", insumos=EstoqueService(db()).listar(mostrar_inativos),
                           tipos=TIPOS_INSUMO, unidades=UNIDADES_BASE, mostrar_inativos=mostrar_inativos)


@app.post("/insumos")
def insumo_criar():                                                   # RF-01
    f = request.form
    iid = EstoqueService(db()).cadastrar(f.get("nome"), f.get("tipo"), f.get("unidade_base"),
                                         numero(f.get("estoque_minimo") or "0", "estoque mínimo", 0))
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
                                numero(f.get("estoque_minimo") or "0", "estoque mínimo", 0))
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
    flash(f"Lote descartado: {lote['saldo']:g} {lote['unidade_base']} de {lote['nome']} saíram do estoque.", "success")
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
                                 (f.get("fornecedor") or "").strip() or None,
                                 validade=data_valida(f.get("validade"), "validade", permitir_vazio=True),
                                 codigo_lote=f.get("codigo_lote"))
        i = est.obter(int(f["insumo_id"]))
        flash(f"Compra registrada: +{r['quantidade_base']:g} {i['unidade_base']} de {i['nome']}. "
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
        eid = EncomendaService(db()).criar(int(f["cliente_id"]), form_itens("item_receita", "item_qtd"),
                                           f.get("data_entrega"), f.get("observacoes"), f.get("data_pedido"))
        flash("Encomenda registrada.", "success")
        return redirect(url_for("encomenda_detalhe", eid=eid))
    receitas_calc = ReceitaService(db()).listar()
    return render_template("encomenda_form.html", clientes=ClienteService(db()).listar(),
                           receitas=receitas_calc, cliente_sel=request.args.get("cliente", type=int))


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
    app.run(host=os.environ.get("HOST", "127.0.0.1"), debug=os.environ.get("FLASK_DEBUG", "1") != "0")
