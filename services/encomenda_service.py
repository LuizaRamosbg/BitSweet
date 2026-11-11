"""Módulo 4 - Gestão de Encomendas (RF-13 a RF-17)."""
import datetime

from repositories.encomenda_repository import ClienteRepository, EncomendaRepository
from services.receita_service import ReceitaService
from services.util import RegraNegocio, data_valida, hoje, rotulo_insumo

# RF-15: fluxo conforme o requisito. "Cancelado" é um estado extra para pedidos desistidos.
STATUS = ("Pendente", "Em Produção", "Pago", "Pronto", "Entregue")
STATUS_TODOS = STATUS + ("Cancelado",)
STATUS_QUE_BAIXAM_ESTOQUE = ("Em Produção", "Pronto", "Entregue")
STATUS_PAGAMENTO = ("Pendente", "Parcial", "Total")
MESES = ("janeiro", "fevereiro", "março", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro",
         "novembro", "dezembro")
FORMAS_PAGAMENTO = ("Pix", "Dinheiro", "Cartão de crédito", "Cartão de débito", "Transferência", "Outro")


class ClienteService:
    """RF-13 - Cadastro, edição e consulta de clientes."""

    def __init__(self, conn):
        self.conn = conn
        self.repo = ClienteRepository(conn)

    @staticmethod
    def _limpar(d):
        dados = {k: (d.get(k) or "").strip() or None
                 for k in ("nome", "telefone", "email", "endereco", "observacoes")}
        if not dados["nome"]:
            raise RegraNegocio("Informe o nome do cliente.")
        if dados["email"] and "@" not in dados["email"]:
            raise RegraNegocio("E-mail inválido.")
        dados["aniversario_dia"], dados["aniversario_mes"] = ClienteService._aniversario(
            d.get("aniversario_dia"), d.get("aniversario_mes"))
        return dados

    @staticmethod
    def _aniversario(dia, mes):
        """Aniversário opcional (só dia e mês). Informe os dois ou nenhum."""
        dia, mes = str(dia or "").strip(), str(mes or "").strip()
        if not dia and not mes:
            return None, None
        if not dia or not mes:
            raise RegraNegocio("Para o aniversário, informe o dia e o mês (ou deixe os dois em branco).")
        try:
            dia, mes = int(dia), int(mes)
            datetime.date(2000, mes, dia)                    # 2000 é bissexto: aceita 29/02
        except ValueError:
            raise RegraNegocio("Data de aniversário inválida.")
        return dia, mes

    @staticmethod
    def aniversario_texto(c):
        if not c["aniversario_dia"] or not c["aniversario_mes"]:
            return None
        return f"{c['aniversario_dia']} de {MESES[c['aniversario_mes'] - 1]}"

    def aniversariantes(self, dias=7, data=None):
        """Clientes que fazem aniversário de hoje até `dias` dias à frente (29/02 vira 28/02 fora do bissexto)."""
        base = datetime.date.fromisoformat(data or hoje())
        lista = []
        for c in self.repo.com_aniversario():
            for ano in (base.year, base.year + 1):
                dia = c["aniversario_dia"]
                if c["aniversario_mes"] == 2 and dia == 29:
                    try:
                        datetime.date(ano, 2, 29)
                    except ValueError:
                        dia = 28
                quando = datetime.date(ano, c["aniversario_mes"], dia)
                if 0 <= (quando - base).days <= dias:
                    lista.append({**dict(c), "data": quando.isoformat(), "em_dias": (quando - base).days})
                    break
        return sorted(lista, key=lambda c: (c["em_dias"], c["nome"]))

    def cadastrar(self, d):
        cid = self.repo.inserir(self._limpar(d))
        self.conn.commit()
        return cid

    def editar(self, cliente_id, d):
        self.obter(cliente_id)
        self.repo.atualizar(cliente_id, self._limpar(d))
        self.conn.commit()

    def excluir(self, cliente_id):
        self.obter(cliente_id)
        if self.repo.tem_encomendas(cliente_id):
            raise RegraNegocio("Cliente com encomendas não pode ser excluído (o histórico seria perdido).")
        self.repo.excluir(cliente_id)
        self.conn.commit()

    def obter(self, cliente_id):
        c = self.repo.buscar(cliente_id)
        if not c:
            raise RegraNegocio("Cliente não encontrado.")
        return c

    def listar(self, busca=None):
        return self.repo.listar(busca)


class EncomendaService:
    def __init__(self, conn):
        self.conn = conn
        self.repo = EncomendaRepository(conn)
        self.clientes = ClienteService(conn)
        self.receitas = ReceitaService(conn)

    # RF-14 - Registro de encomendas com cálculo automático do valor
    def criar(self, cliente_id, itens, data_entrega=None, observacoes=None, data_pedido=None):
        """itens: [(receita_id, quantidade_em_porcoes), ...]"""
        self.clientes.obter(cliente_id)
        data_pedido = data_valida(data_pedido or hoje(), "data do pedido")
        data_entrega = data_valida(data_entrega, "data de entrega", permitir_vazio=True)
        if data_entrega and data_entrega < data_pedido:
            raise RegraNegocio("A data de entrega não pode ser anterior à data do pedido.")

        agrupado = {}
        for receita_id, qtd in itens:
            if qtd <= 0:
                raise RegraNegocio("As quantidades devem ser maiores que zero.")
            agrupado[receita_id] = agrupado.get(receita_id, 0) + qtd
        if not agrupado:
            raise RegraNegocio("Adicione ao menos um produto à encomenda.")

        linhas, total = [], 0
        for receita_id, qtd in agrupado.items():
            calc = self.receitas.calcular(receita_id)
            preco = int(calc["preco_venda"])
            custo = int(round(calc["custo_porcao"]))
            linhas.append((receita_id, qtd, preco, custo))
            total += round(qtd * preco)

        with self.conn:
            eid = self.repo.inserir(cliente_id, data_pedido, data_entrega, total, (observacoes or "").strip() or None)
            for receita_id, qtd, preco, custo in linhas:
                self.repo.inserir_item(eid, receita_id, qtd, preco, custo)
        return eid

    # Alerta de estoque: insumos que não dão para produzir a encomenda
    def _necessidade(self, itens):
        """{insumo_id: quantidade na unidade base} para produzir os itens [(receita_id, porções), ...]."""
        total = {}
        for receita_id, porcoes in itens:
            if not receita_id or not porcoes or porcoes <= 0:
                continue
            r = self.receitas.obter(receita_id)
            for it in self.receitas.repo.itens(receita_id):
                total[it["insumo_id"]] = total.get(it["insumo_id"], 0) + it["quantidade"] * porcoes / r["rendimento"]
        return total

    def faltas_estoque(self, itens, data_entrega=None, ignorar_id=None):
        """Compara o necessário para os itens com o estoque livre: disponível (lotes que ainda estarão na
        validade na data de entrega) menos o que outras encomendas em aberto, ainda não produzidas, vão usar."""
        necessario = self._necessidade(itens)
        if not necessario:
            return []
        comprometido = self._necessidade([(i["receita_id"], i["quantidade"])
                                          for i in self.repo.itens_sem_baixa(ignorar_id)])
        data = max(hoje(), data_entrega or hoje())
        estoque = self.receitas.estoque
        faltas = []
        for insumo_id, qtd in necessario.items():
            insumo = estoque.obter(insumo_id)
            disponivel = estoque.disponivel(insumo_id, data)
            reservado = comprometido.get(insumo_id, 0)
            livre = max(disponivel - reservado, 0)
            if livre + 1e-9 < qtd:
                faltas.append({"insumo_id": insumo_id, "nome": rotulo_insumo(insumo),
                               "unidade": insumo["unidade_base"], "necessario": qtd, "disponivel": disponivel,
                               "comprometido": reservado, "falta": qtd - livre})
        return sorted(faltas, key=lambda f: f["nome"])

    @staticmethod
    def texto_faltas(faltas):
        linhas = []
        for f in faltas:
            reserva = (f", {f['comprometido']:g} já reservado(s) para outras encomendas"
                       if f["comprometido"] > 1e-9 else "")
            linhas.append(f"{f['nome']}: precisa {f['necessario']:g} {f['unidade']}, há {f['disponivel']:g}"
                          f"{reserva}. Faltam {f['falta']:g} {f['unidade']}")
        return "\n• ".join(linhas)

    def obter(self, encomenda_id):
        e = self.repo.buscar(encomenda_id)
        if not e:
            raise RegraNegocio("Encomenda não encontrada.")
        return e

    def detalhes(self, encomenda_id):
        e = self.obter(encomenda_id)
        itens = self.repo.itens(encomenda_id)
        faltas = []
        if not e["estoque_baixado"] and e["status"] not in ("Cancelado", "Entregue"):
            faltas = self.faltas_estoque([(i["receita_id"], i["quantidade"]) for i in itens],
                                         e["data_entrega_prevista"], ignorar_id=encomenda_id)
        return {"encomenda": e, "itens": itens, "faltas": faltas,
                "pagamentos": self.repo.pagamentos(encomenda_id),
                "saldo_devedor": e["valor_total_centavos"] - e["valor_pago_centavos"]}

    def listar(self, status=None, cliente_id=None):
        return self.repo.listar(status, cliente_id)

    # RF-15 - Atualização de status (+ RF-12 baixa automática ao entrar em produção)
    def atualizar_status(self, encomenda_id, novo_status):
        e = self.obter(encomenda_id)
        if novo_status not in STATUS_TODOS:
            raise RegraNegocio("Status inválido.")
        if e["status"] in ("Entregue", "Cancelado") and novo_status != e["status"]:
            raise RegraNegocio(f"A encomenda já está {e['status'].lower()} e não pode mudar de status.")
        if novo_status == "Cancelado" and e["estoque_baixado"]:
            raise RegraNegocio("A produção desta encomenda já baixou o estoque; ela não pode ser cancelada. "
                               "Faça um ajuste de estoque se os insumos voltaram.")

        with self.conn:
            if novo_status in STATUS_QUE_BAIXAM_ESTOQUE and not e["estoque_baixado"]:
                for item in self.repo.itens(encomenda_id):
                    self.receitas.produzir(item["receita_id"], porcoes=item["quantidade"], encomenda_id=encomenda_id)
                self.repo.marcar_estoque_baixado(encomenda_id)
            if novo_status == "Pago" and e["valor_pago_centavos"] < e["valor_total_centavos"]:
                restante = e["valor_total_centavos"] - e["valor_pago_centavos"]
                self._lancar_pagamento(e, restante, "Não informado", hoje())
            self.repo.atualizar_status(encomenda_id, novo_status,
                                       hoje() if novo_status == "Entregue" else e["data_entregue"])

    # RF-16 - Controle de pagamento
    def registrar_pagamento(self, encomenda_id, valor_centavos, forma=None, data=None):
        e = self.obter(encomenda_id)
        if e["status"] == "Cancelado":
            raise RegraNegocio("Não é possível registrar pagamento de encomenda cancelada.")
        if valor_centavos <= 0:
            raise RegraNegocio("O valor do pagamento deve ser maior que zero.")
        restante = e["valor_total_centavos"] - e["valor_pago_centavos"]
        if valor_centavos > restante:
            raise RegraNegocio(f"O valor excede o saldo devedor ({restante / 100:.2f}).")
        with self.conn:
            self._lancar_pagamento(e, valor_centavos, forma, data_valida(data or hoje()))

    def _lancar_pagamento(self, e, valor, forma, data):
        self.repo.inserir_pagamento(e["id"], data, valor, forma)
        pago = e["valor_pago_centavos"] + valor
        self.repo.atualizar_pagamento(e["id"], pago, self.classificar_pagamento(pago, e["valor_total_centavos"]))

    @staticmethod
    def classificar_pagamento(pago, total):
        if pago <= 0:
            return "Pendente"
        return "Total" if pago >= total else "Parcial"

    # RF-17 - Histórico por cliente
    def historico_cliente(self, cliente_id):
        cliente = self.clientes.obter(cliente_id)
        encomendas = self.repo.listar(cliente_id=cliente_id)
        validas = [e for e in encomendas if e["status"] != "Cancelado"]
        total = sum(e["valor_total_centavos"] for e in validas)
        produtos = self.conn.execute(
            """SELECT r.nome, r.unidade_rendimento, SUM(ei.quantidade) AS qtd,
                      SUM(ei.quantidade * ei.preco_unit_centavos) AS valor
               FROM encomenda_itens ei JOIN encomendas e ON e.id = ei.encomenda_id
               JOIN receitas r ON r.id = ei.receita_id
               WHERE e.cliente_id = ? AND e.status <> 'Cancelado'
               GROUP BY r.id ORDER BY qtd DESC""", (cliente_id,)).fetchall()
        return {
            "cliente": cliente,
            "encomendas": encomendas,
            "total_gasto": total,
            "ticket_medio": total / len(validas) if validas else 0,
            "em_aberto": sum(e["valor_total_centavos"] - e["valor_pago_centavos"] for e in validas),
            "produtos_preferidos": produtos,
            "primeira": min((e["data_pedido"] for e in encomendas), default=None),
            "ultima": max((e["data_pedido"] for e in encomendas), default=None),
        }
