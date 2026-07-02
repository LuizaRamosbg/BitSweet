
from sqlalchemy import create_engine;
from sqlalchemy.orm import DeclarativeBase, sessionmaker;

# Use mysql+pymysql para MySQL ou postgresql+psycopg2 para PostgreSQL
SQLALCHEMY_DATABASE_URL = "mysql+pymysql://root:@localhost:3306/bitsweet_db";

engine = create_engine(SQLALCHEMY_DATABASE_URL);

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine);


class Base(DeclarativeBase):
    pass;


def get_db():
    db = SessionLocal();
    try:
        yield db;
    finally:
        db.close();