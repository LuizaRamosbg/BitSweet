FROM python:3.11-slim

# Definir diretório de trabalho
WORKDIR /app

# Copiar requirements e instalar dependências
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copiar arquivos da aplicação
COPY . .

# Criar diretório para o banco de dados
RUN mkdir -p /app/data

# Expor porta 5000
EXPOSE 5000

# Comando para rodar a aplicação
CMD ["python", "app.py"]
