FROM python:3.11-slim

WORKDIR /app

# Instalar dependencias de sistema
RUN apt-get update && apt-get install -y \
    gnupg2 \
    wget \
    && rm -rf /var/lib/apt/lists/*

# Instalar dependencias de Python
COPY checker/requirements.txt ./checker/requirements.txt
RUN pip install --no-cache-dir -r ./checker/requirements.txt

# Copiar el código del checker
COPY checker/ ./checker/

# Comando para iniciar el checker
CMD ["python", "checker/checker.py"]