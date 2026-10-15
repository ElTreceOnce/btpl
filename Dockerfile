FROM python:3.11-slim

WORKDIR /app

# Copia los requirements al contenedor
COPY checker/requirements.txt ./checker/

# Instala las librerías
RUN pip install --no-cache-dir -r ./checker/requirements.txt

# Copia todo el código de tu carpeta checker al contenedor
COPY checker/ ./checker/

# Comando para iniciar el checker
CMD ["python", "-u", "checker/checker.py"]