"""Módulo 2 - Gestão de Receitas (RF-06 a RF-09) e Módulo 3 - Precificação e Custos (RF-10 a RF-12)."""
from repositories.receita_repository import ReceitaRepository
from services.estoque_service import EstoqueService
from services.util import RegraNegocio, hoje


class ReceitaService:
    def __init__(self, conn):
        self.conn = conn
        self.repo = ReceitaRepository(conn)
        self.estoque = EstoqueService(conn)

    # RF-06 / RF-07 / RF-10 - criação da ficha técnica
    def salvar(self, dados, itens, extras, receita_id=None):
        """
        dados:  nome, rendimento, unidade_rendimento, margem_lucro, cmv_alvo, preco_venda_centavos (opcional)
        itens:  [(insumo_id, quantidade_em_unidade_base), ...]   (ingredientes e embalagens do estoque)
        extras: [(descricao, valor_centavos_por_lote), ...]      (custos fora do estoque)
        """
        d = dict(dados)
        d["nome"] = (d.get("nome") or "").strip()
        if not d["nome"]:
            raise RegraNegocio("Informe o nome da receita.")
        if not d.get("rendimento") or d["rendimento"] <= 0:
            raise RegraNegocio("O rendimento deve ser maior que zero (ex.: 1 bolo, 20 fatias).")
        d["unidade_rendimento"] = (d.get("unidade_rendimento") or "porção").strip()
        d.setdefault("margem_lucro", 100)
        d.setdefault("cmv_alvo", 35)
        d.setdefault("preco_venda_centavos", None)
        if d["margem_lucro"] < 0:
            raise RegraNegocio("A margem de lucro não pode ser negativa.")
        if not 0 < d["cmv_alvo"] < 100:
            raise RegraNegocio("O CMV alvo deve estar entre 0 e 100%.")

        vistos = {}
        for insumo_id, qtd in itens:
            self.estoque.obter(insumo_id)
            if qtd <= 0:
                raise RegraNegocio("As quantidades dos insumos devem ser maiores que zero.")
            vistos[insumo_id] = vistos.get(insumo_id, 0) + qtd      # junta insumos repetidos
        if not vistos:
            raise RegraNegocio("Adicione ao menos um insumo à receita.")
        extras = [(desc.strip(), v) for desc, v in extras if desc and desc.strip()]

        existente = self.repo.buscar_por_nome(d["nome"])
        if existente and existente["id"] != receita_id:
            raise RegraNegocio(f"Já existe uma receita chamada '{existente['nome']}'.")

        with self.conn:
            if receita_id:
                self.obter(receita_id)
                self.repo.atualizar(receita_id, d)
            else:
                receita_id = self.repo.inserir(d)
            self.repo.substituir_itens(receita_id, list(vistos.items()))
            self.repo.substituir_extras(receita_id, extras)
        return receita_id

    def obter(self, receita_id):
        r = self.repo.buscar(receita_id)
        if not r:
            raise RegraNegocio("Receita não encontrada.")
        return r

    def listar(self):
        return [self.calcular(r["id"]) for r in self.repo.listar()]

    def excluir(self, receita_id):
        self.obter(receita_id)
        with self.conn:
            if self.repo.em_uso(receita_id):
                self.repo.definir_ativo(receita_id, False)
                return "desativada"
            self.repo.excluir(receita_id)
        return "excluida"

    # RF-08 / RF-09 / RF-10 / RF-11 - custos e preço
    def calcular(self, receita_id):
        r = self.obter(receita_id)
        itens = []
        custo_ingredientes = custo_embalagens = 0.0
        for it in self.repo.itens(receita_id):
            custo = it["quantidade"] * it["custo_medio_centavos"]
            itens.append({**dict(it), "custo_centavos": custo})
            if it["tipo"] == "embalagem":
                custo_embalagens += custo
            else:
                custo_ingredientes += custo
        extras = [dict(e) for e in self.repo.extras(receita_id)]
        custo_extras = sum(e["valor_centavos"] for e in extras)

        custo_total = custo_ingredientes + custo_embalagens + custo_extras       # RF-08 + RF-10
        rendimento = r["rendimento"]
        custo_porcao = custo_total / rendimento                                    # RF-09

        preco_margem = custo_porcao * (1 + r["margem_lucro"] / 100)               # RF-11 (markup)
        preco_cmv = custo_porcao / (r["cmv_alvo"] / 100)                           # RF-11 (CMV)
        preco_venda = r["preco_venda_centavos"] if r["preco_venda_centavos"] is not None else round(preco_margem)
        lucro_porcao = preco_venda - custo_porcao
        return {
            "receita": dict(r),
            "itens": itens,
            "extras": extras,
            "custo_ingredientes": custo_ingredientes,
            "custo_embalagens": custo_embalagens,
            "custo_extras": custo_extras,
            "custo_total": custo_total,
            "custo_porcao": custo_porcao,
            "preco_sugerido_margem": preco_margem,
            "preco_sugerido_cmv": preco_cmv,
            "preco_venda": preco_venda,
            "preco_definido_manual": r["preco_venda_centavos"] is not None,
            "lucro_porcao": lucro_porcao,
            "margem_real": (lucro_porcao / preco_venda * 100) if preco_venda else 0,
            "cmv_real": (custo_porcao / preco_venda * 100) if preco_venda else 0,
            "lotes_possiveis": self._lotes_possiveis(itens),
        }

    @staticmethod
    def _lotes_possiveis(itens):
        if not itens:
            return 0
        return min(it["disponivel"] / it["quantidade"] for it in itens)

    # RF-12 - Baixa automática
    def produzir(self, receita_id, lotes=None, porcoes=None, encomenda_id=None, data=None):
        """Baixa do estoque os insumos da ficha técnica. Informe lotes OU porções.
        Não faz commit se chamada com encomenda_id (faz parte da transação da encomenda)."""
        r = self.obter(receita_id)
        if lotes is None:
            if not porcoes or porcoes <= 0:
                raise RegraNegocio("Informe a quantidade a produzir.")
            lotes = porcoes / r["rendimento"]
        if lotes <= 0:
            raise RegraNegocio("A quantidade a produzir deve ser maior que zero.")
        itens = self.repo.itens(receita_id)
        if not itens:
            raise RegraNegocio(f"A receita {r['nome']} não tem insumos cadastrados.")

        faltas = [f"{it['nome']} (precisa {it['quantidade'] * lotes:g} {it['unidade_base']}, "
                  f"há {it['disponivel']:g} dentro da validade)" for it in itens
                  if it["disponivel"] + 1e-9 < it["quantidade"] * lotes]
        if faltas:
            raise RegraNegocio(f"Estoque insuficiente para {r['nome']}: " + "; ".join(faltas))

        calc = self.calcular(receita_id)
        custo = round(calc["custo_total"] * lotes)
        data = data or hoje()

        def executar():
            pid = self.repo.inserir_producao(receita_id, data, lotes, lotes * r["rendimento"], custo, encomenda_id)
            for it in itens:
                self.estoque.baixar(it["insumo_id"], it["quantidade"] * lotes, "producao", pid, data)
            return pid

        if encomenda_id is not None:
            return executar()
        with self.conn:
            return executar()

    def producoes_recentes(self):
        return self.repo.producoes_recentes()
