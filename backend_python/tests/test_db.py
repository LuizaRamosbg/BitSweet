import sys;

from sqlalchemy import text;
from sqlalchemy.exc import SQLAlchemyError;

from app.config.database import SessionLocal, engine;


def test_db_connection():
    """Valida a conexão entre o FastAPI e o banco de dados MySQL."""
    session = None;
    try:
        print("------------------------------------------------------------")
        print("       Tentando conectar ao banco de dados MySQL");
        session = SessionLocal();

        result = session.execute(text("SELECT 1"));
        value = result.scalar();

        host = engine.url.host or "N/A";
        port = engine.url.port or 3306;
        database = engine.url.database or "N/A";
        driver = engine.url.drivername or "N/A";

        print("------------------------------------------------------------");
        print("✅ Conexão com o banco de dados estabelecida com sucesso!");
        print(f"   Driver:      {driver}");
        print(f"   Host:        {host}");
        print(f"   Porta:       {port}");
        print(f"   Banco:       {database}");
        print(f"   Resultado:   SELECT 1 = {value}");
        print("------------------------------------------------------------");

    except SQLAlchemyError as error:
        print(f"❌ Erro ao conectar ao banco de dados: {error}", file=sys.stderr);
        sys.exit(1);
    except Exception as error:
        print(f"❌ Erro inesperado: {error}", file=sys.stderr);
        sys.exit(1);
    finally:
        if session is not None:
            session.close();
            print("🔒 Conexão com o banco de dados fechada.");
            print("------------------------------------------------------------");


if __name__ == "__main__":
    test_db_connection();