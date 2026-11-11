class UsuarioRepository:
    """Acesso a dados dos usuários que podem entrar no sistema."""

    def __init__(self, conn):
        self.conn = conn

    def contar(self):
        return self.conn.execute("SELECT COUNT(*) FROM usuarios").fetchone()[0]

    def listar(self):
        return self.conn.execute("SELECT id, usuario, criado_em FROM usuarios ORDER BY usuario").fetchall()

    def buscar(self, usuario_id):
        return self.conn.execute("SELECT * FROM usuarios WHERE id = ?", (usuario_id,)).fetchone()

    def buscar_por_nome(self, usuario):
        return self.conn.execute("SELECT * FROM usuarios WHERE usuario = ?", (usuario,)).fetchone()

    def inserir(self, usuario, senha_hash, criado_em):
        cur = self.conn.execute("INSERT INTO usuarios (usuario, senha_hash, criado_em) VALUES (?, ?, ?)",
                                (usuario, senha_hash, criado_em))
        return cur.lastrowid

    def atualizar_senha(self, usuario_id, senha_hash):
        self.conn.execute("UPDATE usuarios SET senha_hash = ? WHERE id = ?", (senha_hash, usuario_id))

    def excluir(self, usuario_id):
        self.conn.execute("DELETE FROM usuarios WHERE id = ?", (usuario_id,))
