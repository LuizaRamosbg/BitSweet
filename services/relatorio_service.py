"""Módulo 5 - Relatórios e BI (RF-18 a RF-21)."""
import csv
import datetime
import io

from services.util import RegraNegocio, reais

STATUS_VENDA = "e.status <> 'Cancelado'"


def periodo_padrao(inicio=None, fim=None, dias=90):
    hoje = datetime.date.today()
    fim = fim or hoje.isoformat()
    inicio = inicio or (hoje - datetime.timedelta(days=dias)).isoformat()
    if inicio > fim:
        raise RegraNegocio("A data inicial deve ser anterior à final.")
    return inicio, fim


def _dias(inicio, fim):
    return (datetime.date.fromisoformat(fim) - datetime.date.fromisoformat(inicio)).days + 1


class RelatorioService:
    def __init__(self, conn):
        self.conn = conn

    # RF-18 - Lucratividade
    def lucratividade(self, inicio, fim):
        por_produto = [dict(r) for r in self.conn.execute(
            f"""SELECT r.nome AS produto, SUM(ei.quantidade) AS quantidade,
                       SUM(ei.quantidade * ei.preco_unit_centavos) AS receita,
                       SUM(ei.quantidade * ei.custo_unit_centavos) AS custo
                FROM encomenda_itens ei JOIN encomendas e ON e.id = ei.encomenda_id
                JOIN receitas r ON r.id = ei.receita_id
                WHERE {STATUS_VENDA} AND e.data_pedido BETWEEN ? AND ?
                GROUP BY r.id ORDER BY receita DESC""", (inicio, fim))]
        for p in por_produto:
            p["lucro"] = p["receita"] - p["custo"]
            p["margem"] = p["lucro"] / p["receita"] * 100 if p["receita"] else 0

        por_mes = [dict(r) for r in self.conn.execute(
            f"""SELECT substr(e.data_pedido, 1, 7) AS mes,
                       SUM(ei.quantidade * ei.preco_unit_centavos) AS receita,
                       SUM(ei.quantidade * ei.custo_unit_centavos) AS custo
                FROM encomenda_itens ei JOIN encomendas e ON e.id = ei.encomenda_id
                WHERE {STATUS_VENDA} AND e.data_pedido BETWEEN ? AND ?
                GROUP BY mes ORDER BY mes""", (inicio, fim))]
        for m in por_mes:
            m["lucro"] = m["receita"] - m["custo"]

        receita = sum(p["receita"] for p in por_produto)
        custo = sum(p["custo"] for p in por_produto)
        recebido = self.conn.execute(
            "SELECT COALESCE(SUM(valor_centavos), 0) FROM pagamentos WHERE data BETWEEN ? AND ?",
            (inicio, fim)).fetchone()[0]
        return {"por_produto": por_produto, "por_mes": por_mes, "receita": receita, "custo": custo,
                "lucro": receita - custo, "margem": (receita - custo) / receita * 100 if receita else 0,
                "recebido": recebido}

    # RF-19 - Consumo de insumos
    def consumo(self, inicio, fim):
        dias = _dias(inicio, fim)
        linhas = [dict(r) for r in self.conn.execute(
            """SELECT i.id, i.nome, i.tipo, i.unidade_base, i.saldo, i.estoque_minimo,
                      COALESCE(SUM(CASE WHEN m.origem = 'producao' THEN -m.quantidade END), 0) AS consumido,
                      COALESCE(SUM(CASE WHEN m.origem = 'producao' THEN -m.quantidade * m.custo_unit_centavos END), 0)
                          AS custo_consumido,
                      COALESCE(SUM(CASE WHEN m.origem = 'compra' THEN m.quantidade END), 0) AS comprado
               FROM insumos i
               LEFT JOIN movimentacoes m ON m.insumo_id = i.id AND m.data BETWEEN ? AND ?
               WHERE i.ativo = 1
               GROUP BY i.id ORDER BY custo_consumido DESC, i.nome""", (inicio, fim))]
        for l in linhas:
            l["media_diaria"] = l["consumido"] / dias
            l["dias_cobertura"] = l["saldo"] / l["media_diaria"] if l["media_diaria"] > 0 else None
            # sugestão: repor para 30 dias de consumo + estoque mínimo
            alvo = l["media_diaria"] * 30 + l["estoque_minimo"]
            l["sugestao_compra"] = max(alvo - l["saldo"], 0)
        por_semana = [dict(r) for r in self.conn.execute(
            """SELECT strftime('%Y-%W', m.data) AS semana, MIN(m.data) AS inicio_semana,
                      SUM(-m.quantidade * m.custo_unit_centavos) AS custo
               FROM movimentacoes m WHERE m.origem = 'producao' AND m.data BETWEEN ? AND ?
               GROUP BY semana ORDER BY semana""", (inicio, fim))]
        return {"insumos": linhas, "por_semana": por_semana, "dias": dias,
                "custo_total": sum(l["custo_consumido"] for l in linhas)}

    # RF-20 - KPIs de produtividade
    def kpis(self, inicio, fim):
        dias = _dias(inicio, fim)
        mais_vendidos = [dict(r) for r in self.conn.execute(
            f"""SELECT r.nome AS produto, r.unidade_rendimento AS unidade, SUM(ei.quantidade) AS quantidade,
                       COUNT(DISTINCT e.id) AS pedidos
                FROM encomenda_itens ei JOIN encomendas e ON e.id = ei.encomenda_id
                JOIN receitas r ON r.id = ei.receita_id
                WHERE {STATUS_VENDA} AND e.data_pedido BETWEEN ? AND ?
                GROUP BY r.id ORDER BY quantidade DESC LIMIT 10""", (inicio, fim))]

        enc = self.conn.execute(
            """SELECT COUNT(*) AS total,
                      SUM(status = 'Entregue') AS entregues,
                      SUM(status = 'Cancelado') AS canceladas,
                      SUM(status NOT IN ('Entregue', 'Cancelado')) AS em_aberto,
                      SUM(status = 'Entregue' AND data_entrega_prevista IS NOT NULL
                          AND data_entregue <= data_entrega_prevista) AS no_prazo,
                      SUM(status = 'Entregue' AND data_entrega_prevista IS NOT NULL) AS com_prazo,
                      AVG(CASE WHEN status = 'Entregue'
                               THEN julianday(data_entregue) - julianday(data_pedido) END) AS lead_time,
                      COALESCE(SUM(CASE WHEN status <> 'Cancelado' THEN valor_total_centavos END), 0) AS faturamento
               FROM encomendas WHERE data_pedido BETWEEN ? AND ?""", (inicio, fim)).fetchone()
        prod = self.conn.execute(
            """SELECT COUNT(*) AS producoes, COALESCE(SUM(porcoes), 0) AS porcoes,
                      COALESCE(SUM(custo_centavos), 0) AS custo
               FROM producoes WHERE data BETWEEN ? AND ?""", (inicio, fim)).fetchone()

        total = enc["total"] or 0
        validas = total - (enc["canceladas"] or 0)
        return {
            "mais_vendidos": mais_vendidos,
            "encomendas": total,
            "entregues": enc["entregues"] or 0,
            "em_aberto": enc["em_aberto"] or 0,
            "taxa_conclusao": (enc["entregues"] or 0) / validas * 100 if validas else 0,
            "taxa_cancelamento": (enc["canceladas"] or 0) / total * 100 if total else 0,
            "pontualidade": (enc["no_prazo"] or 0) / enc["com_prazo"] * 100 if enc["com_prazo"] else None,
            "lead_time_dias": enc["lead_time"],
            "ticket_medio": enc["faturamento"] / validas if validas else 0,
            "producoes": prod["producoes"],
            "porcoes_produzidas": prod["porcoes"],
            "porcoes_por_dia": prod["porcoes"] / dias,
            "custo_medio_porcao": prod["custo"] / prod["porcoes"] if prod["porcoes"] else 0,
        }

    # RF-21 - Exportação (CSV / PDF)
    def tabela(self, tipo, inicio, fim):
        """Retorna (titulo, cabecalho, linhas) já formatadas para exportação."""
        if tipo == "lucratividade":
            d = self.lucratividade(inicio, fim)
            linhas = [[p["produto"], f"{p['quantidade']:g}", reais(p["receita"]), reais(p["custo"]),
                       reais(p["lucro"]), f"{p['margem']:.1f}%"] for p in d["por_produto"]]
            linhas.append(["TOTAL", "", reais(d["receita"]), reais(d["custo"]), reais(d["lucro"]),
                           f"{d['margem']:.1f}%"])
            return ("Relatório de Lucratividade",
                    ["Produto", "Qtd vendida", "Receita", "Custo", "Lucro", "Margem"], linhas)
        if tipo == "consumo":
            d = self.consumo(inicio, fim)
            linhas = [[i["nome"], i["unidade_base"], f"{i['consumido']:g}", reais(i["custo_consumido"]),
                       f"{i['saldo']:g}",
                       "—" if i["dias_cobertura"] is None else f"{i['dias_cobertura']:.0f}",
                       f"{i['sugestao_compra']:g}"] for i in d["insumos"]]
            return ("Relatório de Consumo de Insumos",
                    ["Insumo", "Unid.", "Consumido", "Custo consumido", "Saldo", "Dias de cobertura",
                     "Sugestão de compra"], linhas)
        if tipo == "kpis":
            d = self.kpis(inicio, fim)
            fmt = lambda v, s="": "—" if v is None else f"{v:.1f}{s}"
            linhas = [["Encomendas no período", str(d["encomendas"])],
                      ["Entregues", str(d["entregues"])],
                      ["Em aberto", str(d["em_aberto"])],
                      ["Taxa de conclusão", fmt(d["taxa_conclusao"], "%")],
                      ["Taxa de cancelamento", fmt(d["taxa_cancelamento"], "%")],
                      ["Entregas no prazo", fmt(d["pontualidade"], "%")],
                      ["Tempo médio pedido → entrega (dias)", fmt(d["lead_time_dias"])],
                      ["Ticket médio", reais(d["ticket_medio"])],
                      ["Produções realizadas", str(d["producoes"])],
                      ["Porções produzidas", f"{d['porcoes_produzidas']:g}"],
                      ["Porções produzidas por dia", fmt(d["porcoes_por_dia"])],
                      ["Custo médio por porção", reais(d["custo_medio_porcao"])]]
            linhas += [[f"Mais vendido #{n}: {p['produto']}", f"{p['quantidade']:g} {p['unidade']}"]
                       for n, p in enumerate(d["mais_vendidos"], 1)]
            return "KPIs de Produtividade", ["Indicador", "Valor"], linhas
        raise RegraNegocio("Relatório desconhecido.")

    def exportar_csv(self, tipo, inicio, fim):
        titulo, cab, linhas = self.tabela(tipo, inicio, fim)
        buf = io.StringIO()
        w = csv.writer(buf, delimiter=";")          # ';' abre corretamente no Excel em português
        w.writerow([titulo, f"Período: {inicio} a {fim}"])
        w.writerow(cab)
        w.writerows(linhas)
        return ("﻿" + buf.getvalue()).encode("utf-8")

    def exportar_pdf(self, tipo, inicio, fim):
        from reportlab.lib import colors
        from reportlab.lib.pagesizes import A4, landscape
        from reportlab.lib.styles import getSampleStyleSheet
        from reportlab.platypus import Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle

        titulo, cab, linhas = self.tabela(tipo, inicio, fim)
        buf = io.BytesIO()
        doc = SimpleDocTemplate(buf, pagesize=landscape(A4) if len(cab) > 4 else A4,
                                title=titulo, leftMargin=36, rightMargin=36, topMargin=36, bottomMargin=36)
        estilos = getSampleStyleSheet()
        tabela = Table([cab] + linhas, repeatRows=1)
        tabela.setStyle(TableStyle([
            ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#7a3e5c")),
            ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
            ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
            ("FONTSIZE", (0, 0), (-1, -1), 9),
            ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#f6eef2")]),
            ("GRID", (0, 0), (-1, -1), 0.25, colors.HexColor("#cccccc")),
            ("ALIGN", (1, 1), (-1, -1), "RIGHT"),
        ]))
        doc.build([Paragraph(f"BitSweet — {titulo}", estilos["Title"]),
                   Paragraph(f"Período: {inicio} a {fim} · Gerado em "
                             f"{datetime.datetime.now():%d/%m/%Y %H:%M}", estilos["Normal"]),
                   Spacer(1, 12), tabela])
        return buf.getvalue()
