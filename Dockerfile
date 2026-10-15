FROM python:3.11-slim

WORKDIR /app

RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

# Instalar dependencias Python
COPY checker/requirements.txt ./checker/
RUN pip install --no-cache-dir -r ./checker/requirements.txt

# Copiar código
COPY checker/ ./checker/

# Ejecutar Python
CMD ["python", "checker/checker.py"]