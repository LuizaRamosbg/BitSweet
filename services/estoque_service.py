"""Módulo 1 - Gestão de Estoque (RF-01 a RF-05)."""
import datetime

from repositories.insumo_repository import InsumoRepository
from services.util import (RegraNegocio, UNIDADES_BASE, chave_nome, hoje, para_base, rotulo_insumo, validar_marca,
                           validar_nome)

TIPOS_INSUMO = ("ingrediente", "embalagem", "outro")
DIAS_ALERTA_VALIDADE = 7          # avisar lotes que vencem nesta janela


class EstoqueService:
    def __init__(self, conn):
        self.conn = conn
        self.repo = InsumoRepository(conn)

    # RF-01 - Cadastro de insumos
    def _validar_cadastro(self, nome, tipo, unidade_base, estoque_minimo, marca, ignorar_id=None):
        nome = validar_nome(nome, "do insumo")
        marca = validar_marca(marca)
        if tipo not in TIPOS_INSUMO:
            raise RegraNegocio("Tipo de insumo inválido.")
        if unidade_base not in UNIDADES_BASE:
            raise RegraNegocio("A unidade base deve ser g, ml ou un.")
        if estoque_minimo < 0:
            raise RegraNegocio("O estoque mínimo não pode ser negativo.")
        # mesmo nome só é permitido com marca diferente ('Açúcar'/'Acucar' e 'União'/'uniao' são iguais)
        chave = (chave_nome(nome), chave_nome(marca))
        for existente in self.repo.nomes():
            if existente["id"] != ignorar_id and (chave_nome(existente["nome"]),
                                                  chave_nome(existente["marca"])) == chave:
                inativo = " (está inativo: reative-o na lista de inativos)" if not existente["ativo"] else ""
                raise RegraNegocio(f"Já existe um insumo chamado '{existente['nome']}' da marca "
                                   f"'{existente['marca']}'{inativo}. Para cadastrar outro com o mesmo nome, "
                                   f"informe uma marca diferente.")
        return nome, marca

    def cadastrar(self, nome, tipo, unidade_base, estoque_minimo=0, marca=None):
        nome, marca = self._validar_cadastro(nome, tipo, unidade_base, estoque_minimo, marca)
        insumo_id = self.repo.inserir(nome, tipo, unidade_base, estoque_minimo, marca)
        self.conn.commit()
        return insumo_id

    def editar(self, insumo_id, nome, tipo, unidade_base, estoque_minimo, marca=None):
        atual = self.obter(insumo_id)
        nome, marca = self._validar_cadastro(nome, tipo, unidade_base, estoque_minimo, marca, ignorar_id=insumo_id)
        if unidade_base != atual["unidade_base"] and self.repo.em_uso(insumo_id):
            raise RegraNegocio("Não é possível mudar a unidade de um insumo que já tem movimentações ou receitas.")
        self.repo.atualizar_cadastro(insumo_id, nome, tipo, unidade_base, estoque_minimo, marca)
        self.conn.commit()

    def catalogo(self):
        """Todos os insumos (inclusive inativos) com nome e marca, para checar duplicidade na tela de cadastro."""
        return [{"id": i["id"], "nome": i["nome"], "marca": i["marca"], "tipo": i["tipo"],
                 "unidade_base": i["unidade_base"], "ativo": bool(i["ativo"]), "chave": chave_nome(i["nome"])}
                for i in self.repo.nomes()]

    def excluir(self, insumo_id):
        """Exclui se nunca foi usado; caso contrário apenas desativa (preserva o histórico)."""
        self.obter(insumo_id)
        if self.repo.em_uso(insumo_id):
            self.repo.definir_ativo(insumo_id, False)
            self.conn.commit()
            return "desativado"
        self.repo.excluir(insumo_id)
        self.conn.commit()
        return "excluido"

    def reativar(self, insumo_id):
        self.repo.definir_ativo(insumo_id, True)
        self.conn.commit()

    def obter(self, insumo_id):
        insumo = self.repo.buscar(insumo_id)
        if not insumo:
            raise RegraNegocio("Insumo não encontrado.")
        return insumo

    # RF-04 - Saldo
    def listar(self, incluir_inativos=False):
        return self.repo.listar(hoje(), incluir_inativos)

    def disponivel(self, insumo_id, data=None):
        """Saldo que pode ser usado: soma dos lotes não vencidos na data."""
        return self.repo.disponivel(insumo_id, data or hoje())

    def _recalcular(self, insumo_id):
        """Saldo total e custo médio a partir dos lotes (média ponderada só dos lotes dentro da validade)."""
        insumo = self.obter(insumo_id)
        r = self.repo.resumo_lotes(insumo_id, hoje())
        custo = r["valor_util"] / r["saldo_util"] if r["saldo_util"] > 1e-9 else insumo["custo_medio_centavos"]
        self.repo.atualizar_saldo_custo(insumo_id, max(r["saldo"], 0), custo)

    # RF-02 / RF-03 - Compra e custo unitário (cada compra vira um lote)
    def registrar_compra(self, insumo_id, qtd_embalagens, tamanho_embalagem, unidade_embalagem,
                         preco_total_centavos, data=None, fornecedor=None, validade=None, codigo_lote=None):
        insumo = self.obter(insumo_id)
        if qtd_embalagens <= 0 or tamanho_embalagem <= 0:
            raise RegraNegocio("A quantidade de embalagens e a quantidade em cada embalagem devem ser maiores que zero.")
        if preco_total_centavos < 0:
            raise RegraNegocio("O preço total não pode ser negativo.")
        _, tamanho_base = para_base(tamanho_embalagem, unidade_embalagem, insumo["unidade_base"])
        qtd_base = qtd_embalagens * tamanho_base
        custo_compra = preco_total_centavos / qtd_base            # RF-03
        data = data or hoje()
        if validade and validade < data:
            raise RegraNegocio("A validade é anterior à data da compra: esse lote já chega vencido.")

        with self.conn:
            compra_id = self.repo.inserir_compra(insumo_id, data, qtd_embalagens, tamanho_embalagem,
                                                 unidade_embalagem, qtd_base, preco_total_centavos, fornecedor)
            lote_id = self.repo.inserir_lote(insumo_id, compra_id, (codigo_lote or "").strip() or None, data,
                                             validade, qtd_base, custo_compra)
            self.repo.inserir_movimentacao(insumo_id, data, qtd_base, custo_compra, "compra", compra_id,
                                           lote_id=lote_id)
            self._recalcular(insumo_id)
        insumo = self.obter(insumo_id)
        return {"compra_id": compra_id, "lote_id": lote_id, "quantidade_base": qtd_base,
                "custo_unitario_centavos": custo_compra, "custo_medio_centavos": insumo["custo_medio_centavos"]}

    def _consumir(self, insumo_id, quantidade, lotes, origem, referencia_id, data, observacao=None):
        """Retira `quantidade` dos lotes na ordem recebida (FEFO), registrando uma movimentação por lote."""
        restante = quantidade
        for lote in lotes:
            if restante <= 1e-9:
                break
            usar = min(lote["saldo"], restante)
            self.repo.atualizar_saldo_lote(lote["id"], lote["saldo"] - usar)
            self.repo.inserir_movimentacao(insumo_id, data, -usar, lote["custo_unit_centavos"], origem,
                                           referencia_id, observacao, lote_id=lote["id"])
            restante -= usar
        self._recalcular(insumo_id)

    def ajustar_saldo(self, insumo_id, novo_saldo, observacao="Ajuste de inventário"):
        """Corrige o saldo total após contagem física. Sobra vira um lote sem validade;
        falta sai dos lotes na ordem FEFO (os vencidos primeiro)."""
        insumo = self.obter(insumo_id)
        if novo_saldo < 0:
            raise RegraNegocio("O saldo não pode ser negativo.")
        diferenca = novo_saldo - insumo["saldo"]
        if abs(diferenca) < 1e-9:
            return
        with self.conn:
            if diferenca > 0:
                lote_id = self.repo.inserir_lote(insumo_id, None, "Ajuste de inventário", hoje(), None, diferenca,
                                                 insumo["custo_medio_centavos"])
                self.repo.inserir_movimentacao(insumo_id, hoje(), diferenca, insumo["custo_medio_centavos"],
                                               "ajuste", None, observacao, lote_id=lote_id)
                self._recalcular(insumo_id)
            else:
                self._consumir(insumo_id, -diferenca, self.repo.lotes_com_saldo(insumo_id), "ajuste", None, hoje(),
                               observacao)

    def baixar(self, insumo_id, quantidade, origem, referencia_id=None, data=None):
        """Saída de estoque pela regra FEFO: consome primeiro o lote que vence antes; lotes vencidos
        não são usados. NÃO faz commit: deve ser chamada dentro da transação de quem a usa."""
        insumo = self.obter(insumo_id)
        data = data or hoje()
        lotes = self.repo.lotes_com_saldo(insumo_id, data)
        disponivel = sum(l["saldo"] for l in lotes)
        if disponivel + 1e-9 < quantidade:
            vencido = insumo["saldo"] - disponivel
            extra = f" ({vencido:g} {insumo['unidade_base']} em lotes vencidos)" if vencido > 1e-9 else ""
            raise RegraNegocio(
                f"Estoque insuficiente de {rotulo_insumo(insumo)}: precisa {quantidade:g} {insumo['unidade_base']}, "
                f"há {disponivel:g} {insumo['unidade_base']} dentro da validade{extra}.")
        self._consumir(insumo_id, quantidade, lotes, origem, referencia_id, data)

    # Lotes e validade
    def lotes(self, insumo_id, incluir_esgotados=False):
        return self.repo.lotes_do_insumo(insumo_id, incluir_esgotados)

    def descartar_lote(self, lote_id, motivo="Descarte de lote vencido"):
        lote = self.repo.buscar_lote(lote_id)
        if not lote:
            raise RegraNegocio("Lote não encontrado.")
        if lote["saldo"] <= 1e-9:
            raise RegraNegocio("Este lote já está zerado.")
        with self.conn:
            self._consumir(lote["insumo_id"], lote["saldo"], [lote], "ajuste", None, hoje(), motivo)
        return lote

    def alertas_validade(self, dias=DIAS_ALERTA_VALIDADE):
        """Lotes com saldo vencidos ou que vencem nos próximos `dias` dias."""
        h = datetime.date.today()
        limite = (h + datetime.timedelta(days=dias)).isoformat()
        lotes = []
        for l in self.repo.lotes_em_alerta(h.isoformat(), limite):
            d = dict(l)
            d["dias"] = (datetime.date.fromisoformat(l["validade"]) - h).days
            lotes.append(d)
        return lotes

    # RF-05 - Alerta de estoque mínimo (considera só o saldo dentro da validade)
    def alertas(self):
        return self.repo.abaixo_do_minimo(hoje())

    def compras(self, insumo_id):
        return self.repo.compras_do_insumo(insumo_id)

    def compras_recentes(self):
        return self.repo.compras_recentes()

    def movimentacoes(self, insumo_id):
        return self.repo.movimentacoes_do_insumo(insumo_id)
