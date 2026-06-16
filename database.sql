CREATE SCHEMA IF NOT EXISTS juego;
USE juego;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    año_nacimiento INT,
    sexo ENUM('Masculino', 'Femenino', 'Prefiero no decirlo') DEFAULT 'Prefiero no decirlo',
    pais VARCHAR(50),
    ciudad VARCHAR(50),
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    foto_perfil VARCHAR(255),
    puntaje INT DEFAULT 0,
    validado BOOLEAN DEFAULT FALSE
);

ALTER TABLE users ADD COLUMN token INT NULL;
ALTER TABLE users ADD COLUMN cuenta_validada TINYINT DEFAULT 0;

CREATE TABLE IF NOT EXISTS categorias (
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL,
    color VARCHAR(30) NOT NULL
);

CREATE TABLE IF NOT EXISTS preguntas (
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    pregunta VARCHAR(300) NOT NULL,

    opcion_a VARCHAR(300) NOT NULL,
    opcion_b VARCHAR(300) NOT NULL,
    opcion_c VARCHAR(300) NOT NULL,
    opcion_d VARCHAR(300) NOT NULL,

    respuesta_correcta CHAR(1) NOT NULL,

    categoria_id INTEGER NOT NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

INSERT INTO categorias (nombre, color)
VALUES ('Historia', '#4caf50'),
       ('Deportes', '#2196f3'),
       ('Cultura', '#9c27b0');

-- Historia (número 1).
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿En qué año comenzó la segunda guerra mundial?', '1914', '1939', '1945', '1925', 'b', 1);

INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Quién fue el primer presidente de Argentina?', 'San Martín', 'Sarmiento', 'Rivadavia', 'Perón', 'c', 1);

INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Qué imperio construyó las pirámides de egipto?', 'Romano', 'Egipcio', 'Griego', 'Inca', 'b', 1);



-- Deportes (número 2).
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Cuántos jugadores tiene un equipo de fútbol en cancha?', '9', '10', '11', '12', 'c', 2);

INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Dónde se jugaron los juegos olímpicos 2016?', 'Brasil', 'China', 'Rusia', 'Japón', 'a', 2);

INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Qué deporte practica Messi?', 'Tenis', 'Fútbol', 'Basket', 'Golf', 'b', 2);



-- Cultura (número 3).
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Cuál es el océano más grande?', 'Atlántico', 'Índico', 'Pacífico', 'Ártico', 'c', 3);

INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Cuál es el planeta más cercano al sol?', 'Venus', 'Mercurio', 'Marte', 'Tierra', 'b', 3);

INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Cuál es el idioma más hablado del mundo?', 'Inglés', 'Español', 'Chino mandarín', 'Hindi', 'c', 3);

INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id)
VALUES ('¿Cuántos días tiene un año normal?', '365', '360', '366', '364', 'a', 3);

CREATE TABLE IF NOT EXISTS partidas (
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    puntaje INTEGER DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES users(id)
);