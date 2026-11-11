"""Login: usuários com senha (guardada só como hash, nunca em texto puro)."""
import re

from werkzeug.security import check_password_hash, generate_password_hash

from repositories.usuario_repository import UsuarioRepository
from services.util import RegraNegocio, hoje

SENHA_MINIMA = 6
_USUARIO_VALIDO = re.compile(r"^[A-Za-z0-9._-]{3,30}$")
# hash calculado uma vez para comparar quando o usuário não existe (tempo de resposta igual)
_HASH_FALSO = generate_password_hash("bitsweet-usuario-inexistente")


class UsuarioService:
    def __init__(self, conn):
        self.conn = conn
        self.repo = UsuarioRepository(conn)

    def existe_algum(self):
        return self.repo.contar() > 0

    def listar(self):
        return self.repo.listar()

    def obter(self, usuario_id):
        u = self.repo.buscar(usuario_id)
        if not u:
            raise RegraNegocio("Usuário não encontrado.")
        return u

    @staticmethod
    def _validar_senha(senha, confirmacao):
        if len(senha or "") < SENHA_MINIMA:
            raise RegraNegocio(f"A senha deve ter pelo menos {SENHA_MINIMA} caracteres.")
        if senha != confirmacao:
            raise RegraNegocio("A confirmação não confere com a senha.")

    def criar(self, usuario, senha, confirmacao):
        usuario = (usuario or "").strip()
        if not _USUARIO_VALIDO.match(usuario):
            raise RegraNegocio("O usuário deve ter de 3 a 30 caracteres: letras sem acento, números, ponto, "
                               "hífen ou sublinhado (ex.: maria.silva).")
        if self.repo.buscar_por_nome(usuario):
            raise RegraNegocio(f"Já existe o usuário '{usuario}'.")
        self._validar_senha(senha, confirmacao)
        uid = self.repo.inserir(usuario, generate_password_hash(senha), hoje())
        self.conn.commit()
        return uid

    def autenticar(self, usuario, senha):
        """Devolve o usuário se a senha conferir; senão None (mesma mensagem para usuário ou senha errados)."""
        u = self.repo.buscar_por_nome((usuario or "").strip())
        if not u:
            check_password_hash(_HASH_FALSO, senha or "")
            return None
        return u if check_password_hash(u["senha_hash"], senha or "") else None

    def alterar_senha(self, usuario_id, senha_atual, nova, confirmacao):
        u = self.obter(usuario_id)
        if not check_password_hash(u["senha_hash"], senha_atual or ""):
            raise RegraNegocio("A senha atual está incorreta.")
        self._validar_senha(nova, confirmacao)
        self.repo.atualizar_senha(usuario_id, generate_password_hash(nova))
        self.conn.commit()

    def excluir(self, usuario_id, usuario_logado_id):
        self.obter(usuario_id)
        if usuario_id == usuario_logado_id:
            raise RegraNegocio("Você não pode excluir o próprio usuário enquanto está conectado com ele.")
        if self.repo.contar() <= 1:
            raise RegraNegocio("O sistema precisa de pelo menos um usuário.")
        self.repo.excluir(usuario_id)
        self.conn.commit()
