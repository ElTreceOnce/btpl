FROM python:3.11-slim

WORKDIR /app

RUN apt-get update && apt-get install -y \
    gnupg2 \
    wget \
    && rm -rf /var/lib/apt/lists/*

COPY checker/requirements.txt ./checker/requirements.txt
RUN pip install --no-cache-dir -r ./checker/requirements.txt

COPY checker/ ./checker/

CMD ["python", "-u", "checker/checker.py"]