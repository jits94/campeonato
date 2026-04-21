CREATE DATABASE IF NOT EXISTS campeonato_db;
USE campeonato_db;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'veedor') NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    logo VARCHAR(255) DEFAULT 'default.png',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    ci VARCHAR(20) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);

CREATE TABLE torneos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('todos_contra_todos', 'fase_grupos') NOT NULL,
    estado ENUM('activo', 'finalizado') DEFAULT 'activo'
);

CREATE TABLE directiva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    torneo_id INT NOT NULL,
    cargo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE
);

CREATE TABLE inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    torneo_id INT NOT NULL,
    equipo_id INT NOT NULL,
    monto_cobrado DECIMAL(10,2) NOT NULL,
    estado ENUM('borrador', 'registrado') DEFAULT 'registrado',
    FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);

CREATE TABLE grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    torneo_id INT NOT NULL,
    nombre_grupo VARCHAR(50) NOT NULL,
    FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE
);

CREATE TABLE grupo_equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    equipo_id INT NOT NULL,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);

CREATE TABLE partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    torneo_id INT NOT NULL,
    equipo_local_id INT NOT NULL,
    equipo_visitante_id INT NOT NULL,
    fecha DATE,
    hora TIME,
    estado ENUM('programado', 'en_juego', 'finalizado', 'walkover') DEFAULT 'programado',
    fase VARCHAR(50) DEFAULT 'Regular', 
    goles_local INT DEFAULT 0,
    goles_visitante INT DEFAULT 0,
    observacion TEXT,
    FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_local_id) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id) ON DELETE CASCADE
);

CREATE TABLE eventos_partido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    equipo_id INT NOT NULL,
    jugador_id INT NOT NULL,
    tipo ENUM('gol', 'amarilla', 'roja') NOT NULL,
    minuto INT,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE CASCADE
);

CREATE TABLE sanciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
    FOREIGN KEY (evento_id) REFERENCES eventos_partido(id) ON DELETE CASCADE
);

CREATE TABLE gastos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    torneo_id INT,
    categoria ENUM('cancha', 'arbitraje', 'administrativo') NOT NULL,
    descripcion TEXT,
    monto DECIMAL(10,2) NOT NULL,
    fecha DATE,
    FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE SET NULL
);

CREATE TABLE cobros_partido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    equipo_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);

CREATE TABLE transferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jugador_id INT NOT NULL,
    equipo_origen_id INT NOT NULL,
    equipo_destino_id INT NOT NULL,
    torneo_id INT,
    monto DECIMAL(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_origen_id) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_destino_id) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE SET NULL
);

-- TODO PASSWORD ESTA ENCRYPTADO CON PASSWORD_HASH (Bcrypt). El password de todos es "123456"
INSERT INTO usuarios (nombre, usuario, password, rol) VALUES
('Administrador del Sistema', 'admin', '$2y$10$w81oXzJ9A4K/S2tXJtH8.OGW./H.L/3Z1c0Z7v/A6F.x1n0/mCg.i', 'administrador'),
('Veedor Principal', 'veedor1', '$2y$10$w81oXzJ9A4K/S2tXJtH8.OGW./H.L/3Z1c0Z7v/A6F.x1n0/mCg.i', 'veedor'),
('Veedor Secundario', 'veedor2', '$2y$10$w81oXzJ9A4K/S2tXJtH8.OGW./H.L/3Z1c0Z7v/A6F.x1n0/mCg.i', 'veedor'),
('Veedor Tres', 'veedor3', '$2y$10$w81oXzJ9A4K/S2tXJtH8.OGW./H.L/3Z1c0Z7v/A6F.x1n0/mCg.i', 'veedor'),
('Veedor Cuatro', 'veedor4', '$2y$10$w81oXzJ9A4K/S2tXJtH8.OGW./H.L/3Z1c0Z7v/A6F.x1n0/mCg.i', 'veedor');

INSERT INTO equipos (nombre) VALUES
('Real Madrid FC'), ('FC Barcelona'), ('Boca Juniors'), ('River Plate'), ('Flamengo'),
('Corinthians'), ('Colo Colo'), ('Universidad de Chile'), ('Peñarol'), ('Nacional');

INSERT INTO jugadores (equipo_id, nombre, ci) VALUES
(1, 'Jugador 1 RM', '1001'), (1, 'Jugador 2 RM', '1002'), (1, 'Jugador 3 RM', '1003'), (1, 'Jugador 4 RM', '1004'), (1, 'Jugador 5 RM', '1005'), (1, 'Jugador 6 RM', '1006'),
(2, 'Jugador 1 FCB', '2001'), (2, 'Jugador 2 FCB', '2002'), (2, 'Jugador 3 FCB', '2003'), (2, 'Jugador 4 FCB', '2004'), (2, 'Jugador 5 FCB', '2005'), (2, 'Jugador 6 FCB', '2006'),
(3, 'Jugador 1 BJ', '3001'), (3, 'Jugador 2 BJ', '3002'), (3, 'Jugador 3 BJ', '3003'), (3, 'Jugador 4 BJ', '3004'), (3, 'Jugador 5 BJ', '3005'), (3, 'Jugador 6 BJ', '3006'),
(4, 'Jugador 1 RP', '4001'), (4, 'Jugador 2 RP', '4002'), (4, 'Jugador 3 RP', '4003'), (4, 'Jugador 4 RP', '4004'), (4, 'Jugador 5 RP', '4005'), (4, 'Jugador 6 RP', '4006'),
(5, 'Jugador 1 FL', '5001'), (5, 'Jugador 2 FL', '5002'), (5, 'Jugador 3 FL', '5003'), (5, 'Jugador 4 FL', '5004'), (5, 'Jugador 5 FL', '5005'), (5, 'Jugador 6 FL', '5006'),
(6, 'Jugador 1 CO', '6001'), (6, 'Jugador 2 CO', '6002'), (6, 'Jugador 3 CO', '6003'), (6, 'Jugador 4 CO', '6004'), (6, 'Jugador 5 CO', '6005'), (6, 'Jugador 6 CO', '6006'),
(7, 'Jugador 1 CC', '7001'), (7, 'Jugador 2 CC', '7002'), (7, 'Jugador 3 CC', '7003'), (7, 'Jugador 4 CC', '7004'), (7, 'Jugador 5 CC', '7005'), (7, 'Jugador 6 CC', '7006'),
(8, 'Jugador 1 UCH', '8001'), (8, 'Jugador 2 UCH', '8002'), (8, 'Jugador 3 UCH', '8003'), (8, 'Jugador 4 UCH', '8004'), (8, 'Jugador 5 UCH', '8005'), (8, 'Jugador 6 UCH', '8006'),
(9, 'Jugador 1 PE', '9001'), (9, 'Jugador 2 PE', '9002'), (9, 'Jugador 3 PE', '9003'), (9, 'Jugador 4 PE', '9004'), (9, 'Jugador 5 PE', '9005'), (9, 'Jugador 6 PE', '9006'),
(10, 'Jugador 1 NA', '10001'), (10, 'Jugador 2 NA', '10002'), (10, 'Jugador 3 NA', '10003'), (10, 'Jugador 4 NA', '10004'), (10, 'Jugador 5 NA', '10005'), (10, 'Jugador 6 NA', '10006');
