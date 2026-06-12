CREATE TABLE IF NOT EXISTS rutas (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre   TEXT    NOT NULL,
    origen   TEXT    NOT NULL,
    destino  TEXT    NOT NULL,
    km       REAL    NOT NULL,
    tipo     TEXT    NOT NULL CHECK(tipo IN ('estandar', 'aeropuerto'))
);

CREATE TABLE IF NOT EXISTS conductores (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre      TEXT    NOT NULL,
    vehiculo    TEXT    NOT NULL CHECK(vehiculo IN ('estandar', 'vip')),
    disponible  INTEGER NOT NULL DEFAULT 1 CHECK(disponible IN (0, 1))
);

CREATE TABLE IF NOT EXISTS tarifas (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre        TEXT  NOT NULL UNIQUE,
    precio_km     REAL  NOT NULL,
    multiplicador REAL  NOT NULL DEFAULT 1.0,
    cargo_fijo    REAL  NOT NULL DEFAULT 0.0
);

CREATE TABLE IF NOT EXISTS cotizaciones (
    id           INTEGER  PRIMARY KEY AUTOINCREMENT,
    ruta_id      INTEGER  NOT NULL REFERENCES rutas(id),
    tarifa_id    INTEGER  NOT NULL REFERENCES tarifas(id),
    km           REAL     NOT NULL,
    precio_base  REAL     NOT NULL,
    recargo      REAL     NOT NULL DEFAULT 0.0,
    precio_final REAL     NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT (datetime('now'))
);

-- Rutas
INSERT INTO rutas (nombre, origen, destino, km, tipo) VALUES
    ('Centro → Shopping',       'Centro',       'Shopping',   5.0,  'estandar'),
    ('Centro → Aeropuerto',     'Centro',       'Aeropuerto', 18.0, 'aeropuerto'),
    ('Barrio Norte → Hospital', 'Barrio Norte', 'Hospital',   3.5,  'estandar'),
    ('Terminal → Hotel',        'Terminal',     'Hotel',      12.0, 'aeropuerto'),
    ('Universidad → Centro',    'Universidad',  'Centro',     7.2,  'estandar');

-- Conductores
INSERT INTO conductores (nombre, vehiculo, disponible) VALUES
    ('Carlos Méndez', 'estandar', 1),
    ('Laura Soria',   'estandar', 1),
    ('Miguel Torres', 'vip',      1),
    ('Ana Gómez',     'estandar', 0),
    ('Roberto Díaz',  'vip',      1);

-- Tarifas
INSERT INTO tarifas (nombre, precio_km, multiplicador, cargo_fijo) VALUES
    ('normal',   1.20, 1.00, 0.00),
    ('nocturna', 1.20, 1.30, 0.00),
    ('lluvia',   1.20, 1.20, 0.00),
    ('feriado',  1.20, 1.50, 0.00),
    ('vip',      2.50, 1.00, 5.00);
