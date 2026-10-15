FROM python:3.11-slim

WORKDIR /app

# --- CORRECCIÓN: Crear la carpeta donde va el archivo SQLite ---
RUN mkdir -p /app/data
# --------------------------------------------------------------

RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

COPY checker/requirements.txt ./checker/
RUN pip install --no-cache-dir -r ./checker/requirements.txt

COPY checker/ ./checker/

CMD ["python", "checker/checker.py"]