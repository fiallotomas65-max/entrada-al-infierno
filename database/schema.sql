-- =====================================================
-- Clinical Data System (C.D.S.) - Hospital de Clinicas
-- Esquema de base de datos
-- =====================================================

CREATE DATABASE IF NOT EXISTS cds_hospital CHARACTER SET utf8mb4;
USE cds_hospital;

-- ---------- Usuarios que ingresan al panel ----------
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('Administrador', 'Recepcionista', 'Coordinador') NOT NULL
);

-- ---------- Modulo de documentacion para pacientes ----------
CREATE TABLE documentos (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(100) NOT NULL,
    archivo VARCHAR(255) NOT NULL,
    fecha_alta DATETIME NOT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE encuestas (
    id_encuesta INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    estado ENUM('Activa', 'Inactiva') NOT NULL DEFAULT 'Activa',
    fecha_creacion DATETIME NOT NULL
);

CREATE TABLE preguntas (
    id_pregunta INT AUTO_INCREMENT PRIMARY KEY,
    id_encuesta INT NOT NULL,
    texto VARCHAR(300) NOT NULL,
    tipo ENUM('cerrada', 'abierta') NOT NULL,
    FOREIGN KEY (id_encuesta) REFERENCES encuestas(id_encuesta)
);

CREATE TABLE opciones (
    id_opcion INT AUTO_INCREMENT PRIMARY KEY,
    id_pregunta INT NOT NULL,
    texto VARCHAR(150) NOT NULL,
    FOREIGN KEY (id_pregunta) REFERENCES preguntas(id_pregunta)
);

-- las respuestas son anonimas a proposito: no guardan ningun dato del paciente
CREATE TABLE respuestas (
    id_respuesta INT AUTO_INCREMENT PRIMARY KEY,
    id_encuesta INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    FOREIGN KEY (id_encuesta) REFERENCES encuestas(id_encuesta)
);

CREATE TABLE respuestas_detalle (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_respuesta INT NOT NULL,
    id_pregunta INT NOT NULL,
    id_opcion INT NULL,
    texto_libre VARCHAR(500) NULL,
    FOREIGN KEY (id_respuesta) REFERENCES respuestas(id_respuesta),
    FOREIGN KEY (id_pregunta) REFERENCES preguntas(id_pregunta),
    FOREIGN KEY (id_opcion) REFERENCES opciones(id_opcion)
);

-- ---------- Modulo de trazabilidad de ambulancias ----------
CREATE TABLE ambulancias (
    id_ambulancia INT AUTO_INCREMENT PRIMARY KEY,
    patente VARCHAR(20) NOT NULL UNIQUE,
    modelo VARCHAR(100) NOT NULL,
    estado ENUM('Disponible', 'En servicio', 'Mantenimiento') NOT NULL DEFAULT 'Disponible'
);

CREATE TABLE personal (
    id_personal INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    documento VARCHAR(20) NOT NULL UNIQUE,
    cargo ENUM('Conductor', 'Enfermera', 'Auxiliar') NOT NULL,
    telefono VARCHAR(20)
);

CREATE TABLE rutas (
    id_ruta INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    origen VARCHAR(150) NOT NULL,
    destino VARCHAR(150) NOT NULL,
    distancia_km DECIMAL(6,2) NOT NULL,
    tiempo_min INT NOT NULL
);

CREATE TABLE traslados (
    id_traslado INT AUTO_INCREMENT PRIMARY KEY,
    id_ambulancia INT NOT NULL,
    id_conductor INT NOT NULL,
    id_copiloto INT NULL,
    id_ruta INT NULL,
    tipo_elemento ENUM('Paciente', 'Material biologico', 'Equipamiento', 'Insumo') NOT NULL,
    elemento VARCHAR(300) NOT NULL,
    origen VARCHAR(300) NOT NULL,
    destino VARCHAR(300) NOT NULL,
    fecha_traslado DATE NOT NULL,
    estado ENUM('Pendiente', 'En curso', 'Finalizado', 'Cancelado') NOT NULL DEFAULT 'Pendiente',
    hora_salida DATETIME NULL,
    hora_llegada DATETIME NULL,
    observaciones TEXT,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_ambulancia) REFERENCES ambulancias(id_ambulancia),
    FOREIGN KEY (id_conductor) REFERENCES personal(id_personal),
    FOREIGN KEY (id_copiloto) REFERENCES personal(id_personal),
    FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE seguimiento (
    id_seguimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_traslado INT NOT NULL,
    estado VARCHAR(20) NOT NULL,
    fecha_hora DATETIME NOT NULL,
    comentario VARCHAR(300),
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_traslado) REFERENCES traslados(id_traslado),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- ---------- Datos de prueba ----------
-- contrasenas: mrodriguez=direccion2024 / vfernandez=recepcion2024 / jalvarez=coordina2024
-- (son hashes bcrypt reales y distintos entre si, generados especificamente
-- para estas contrasenas de prueba)
INSERT INTO usuarios (nombre, apellido, usuario, contrasena, rol) VALUES
('Mariana', 'Rodriguez', 'mrodriguez', '$2b$12$G4RmUNwugVCs08lcaqsJKewmT7Zm6E5kF2HunS0hMXgI/i.61EqkO', 'Administrador'),
('Valentina', 'Fernandez', 'vfernandez', '$2b$12$0Yhy6UfIYJ6SAOHkSFHzA.5MDIBSpwW0gYSehI7WAA4iZqz50wqvm', 'Recepcionista'),
('Javier', 'Alvarez', 'jalvarez', '$2b$12$fdzmwea.SRCZLlaiTMyMROuCgI5Awf6rWye5Kho/KmN2IWnZ7ezeO', 'Coordinador');

INSERT INTO ambulancias (patente, modelo, estado) VALUES
('SBA 1234', 'Mercedes Sprinter', 'Disponible'),
('SBB 5678', 'Fiat Ducato', 'Disponible');

INSERT INTO personal (nombre, apellido, documento, cargo, telefono) VALUES
('Marcos', 'Gimenez', '12345678', 'Conductor', '099111222'),
('Rosa', 'Ibarra', '23456789', 'Enfermera', '099222333');

INSERT INTO rutas (nombre, origen, destino, distancia_km, tiempo_min) VALUES
('Hospital - Aeropuerto', 'Hospital de Clinicas', 'Aeropuerto de Carrasco', 18.50, 30),
('Hospital - Terminal', 'Hospital de Clinicas', 'Terminal Tres Cruces', 6.20, 15);