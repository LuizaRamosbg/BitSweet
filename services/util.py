"""Utilitários compartilhados: erros de regra de negócio, unidades e dinheiro."""
import datetime


class RegraNegocio(Exception):
    """Erro de validação/regra de negócio, com mensagem pronta para o usuário."""


# unidade informada -> (unidade base, fator de conversão)
UNIDADES = {
    "g": ("g", 1), "kg": ("g", 1000),
    "ml": ("ml", 1), "l": ("ml", 1000),
    "un": ("un", 1), "dz": ("un", 12),
}
UNIDADES_BASE = ("g", "ml", "un")


def para_base(quantidade, unidade, unidade_base_esperada=None):
    chave = (unidade or "").strip().lower()
    if chave not in UNIDADES:
        raise RegraNegocio(f"Unidade desconhecida: {unidade}")
    base, fator = UNIDADES[chave]
    if unidade_base_esperada and base != unidade_base_esperada:
        raise RegraNegocio(f"A unidade {unidade} não é compatível com {unidade_base_esperada}.")
    return base, quantidade * fator


def unidades_compativeis(unidade_base):
    return [u for u, (b, _) in UNIDADES.items() if b == unidade_base]


def numero(valor, campo, minimo=None, permitir_vazio=False):
    """Converte texto do formulário ('1.234,56' ou '1234.56') em float."""
    texto = str(valor if valor is not None else "").strip()
    if texto == "":
        if permitir_vazio:
            return None
        raise RegraNegocio(f"Informe {campo}.")
    if "," in texto:
        texto = texto.replace(".", "").replace(",", ".")
    try:
        n = float(texto)
    except ValueError:
        raise RegraNegocio(f"Valor inválido em {campo}: {valor}")
    if minimo is not None and n < minimo:
        raise RegraNegocio(f"{campo.capitalize()} deve ser no mínimo {minimo}.")
    return n


def centavos(valor, campo="valor", permitir_vazio=False):
    n = numero(valor, campo, minimo=0, permitir_vazio=permitir_vazio)
    return None if n is None else int(round(n * 100))


def reais(centavos_valor):
    if centavos_valor is None:
        return "—"
    v = centavos_valor / 100
    s = f"{v:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
    return f"R$ {s}"


def hoje():
    return datetime.date.today().isoformat()


def data_valida(texto, campo="data", permitir_vazio=False):
    texto = (texto or "").strip()
    if not texto:
        if permitir_vazio:
            return None
        raise RegraNegocio(f"Informe {campo}.")
    try:
        return datetime.date.fromisoformat(texto).isoformat()
    except ValueError:
        raise RegraNegocio(f"Data inválida: {texto}")
