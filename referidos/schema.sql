-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    cedula VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    estado VARCHAR(50) DEFAULT 'pendiente',
    motivo VARCHAR(255),
    codigo_corto VARCHAR(50),
    creado_en TIMESTAMP DEFAULT NOW()
);

-- Tabla de cola (para el checker)
CREATE TABLE IF NOT EXISTS cola (
    id SERIAL PRIMARY KEY,
    cedula VARCHAR(50),
    password VARCHAR(255),
    usuario_id INTEGER REFERENCES usuarios(id),
    estado VARCHAR(50) DEFAULT 'pendiente',
    resultado VARCHAR(50),
    motivo VARCHAR(255),
    creado_en TIMESTAMP DEFAULT NOW(),
    actualizado_en TIMESTAMP DEFAULT NOW()
);

-- Tabla de referidos
CREATE TABLE IF NOT EXISTS referidos (
    id SERIAL PRIMARY KEY,
    reffer_id INTEGER REFERENCES usuarios(id),
    reffered_cedula VARCHAR(50),
    estado VARCHAR(50) DEFAULT 'pendiente',
    creado_en TIMESTAMP DEFAULT NOW()
);