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
    nivel TINYINT NOT NULL DEFAULT 2,

    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

INSERT INTO categorias (nombre, color)
VALUES ('Historia', '#4caf50'),
       ('Deportes', '#2196f3'),
       ('Cultura', '#9c27b0');


-- ============================================================
-- NIVEL 1 - FÁCIL
-- ============================================================

-- Historia
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿En qué continente está Argentina?', 'África', 'Europa', 'América', 'Asia', 'c', 1, 1),
('¿Cuántos años tiene un siglo?', '10', '1000', '50', '100', 'd', 1, 1),
('¿En qué país está la Torre Eiffel?', 'Italia', 'España', 'Alemania', 'Francia', 'd', 1, 1),
('¿Cuál es la capital de Argentina?', 'Córdoba', 'Rosario', 'Buenos Aires', 'Mendoza', 'c', 1, 1);

-- Deportes
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿Cuántos jugadores tiene un equipo de básquet en cancha?', '6', '5', '7', '4', 'b', 2, 1),
('¿En qué deporte se usa una raqueta y pelota amarilla?', 'Fútbol', 'Tenis', 'Golf', 'Vóley', 'b', 2, 1),
('¿Cada cuántos años se realizan los Juegos Olímpicos?', '2', '5', '3', '4', 'd', 2, 1),
('¿Qué país ganó más Mundiales de fútbol?', 'Argentina', 'Brasil', 'Alemania', 'Italia', 'b', 2, 1);

-- Cultura
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿Cuál es la capital de Francia?', 'Londres', 'Berlín', 'París', 'Roma', 'c', 3, 1),
('¿Cuántos lados tiene un cuadrado?', '3', '5', '6', '4', 'd', 3, 1),
('¿De qué animal proviene la lana?', 'Vaca', 'Oveja', 'Cabra', 'Cerdo', 'b', 3, 1),
('¿Cuántos meses tiene un año?', '10', '11', '13', '12', 'd', 3, 1);


-- ============================================================
-- NIVEL 2 - INTERMEDIO
-- ============================================================

-- Historia
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿En qué año comenzó la segunda guerra mundial?', '1914', '1939', '1945', '1925', 'b', 1, 2),
('¿Quién fue el primer presidente de Argentina?', 'San Martín', 'Sarmiento', 'Rivadavia', 'Perón', 'c', 1, 2),
('¿Qué imperio construyó las pirámides de Egipto?', 'Romano', 'Egipcio', 'Griego', 'Inca', 'b', 1, 2),
('¿En qué año llegó Cristóbal Colón a América?', '1392', '1500', '1492', '1480', 'c', 1, 2);

-- Deportes
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿Cuántos jugadores tiene un equipo de fútbol en cancha?', '9', '10', '11', '12', 'c', 2, 2),
('¿Dónde se jugaron los Juegos Olímpicos 2016?', 'Brasil', 'China', 'Rusia', 'Japón', 'a', 2, 2),
('¿Qué deporte practica Messi?', 'Tenis', 'Fútbol', 'Basket', 'Golf', 'b', 2, 2),
('¿Cuántos sets se necesitan ganar para llevarse un partido de tenis en Grand Slam masculino?', '2', '3', '4', '5', 'b', 2, 2);

-- Cultura
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿Cuál es el océano más grande?', 'Atlántico', 'Índico', 'Pacífico', 'Ártico', 'c', 3, 2),
('¿Cuál es el planeta más cercano al sol?', 'Venus', 'Mercurio', 'Marte', 'Tierra', 'b', 3, 2),
('¿Cuál es el idioma más hablado del mundo?', 'Inglés', 'Español', 'Chino mandarín', 'Hindi', 'c', 3, 2),
('¿Cuántos días tiene un año normal?', '365', '360', '366', '364', 'a', 3, 2);


-- ============================================================
-- NIVEL 3 - DIFÍCIL
-- ============================================================

-- Historia
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿En qué año fue la Revolución Francesa?', '1776', '1789', '1804', '1815', 'b', 1, 3),
('¿Cuál fue el primer país en otorgar el voto femenino?', 'EE.UU.', 'Francia', 'Nueva Zelanda', 'Suiza', 'c', 1, 3),
('¿Cuántos años duró realmente la Guerra de los Cien Años?', '100', '116', '80', '150', 'b', 1, 3),
('¿Qué tratado puso fin a la Primera Guerra Mundial?', 'Tratado de Versalles', 'Tratado de París', 'Tratado de Viena', 'Tratado de Utrecht', 'a', 1, 3);

-- Deportes
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿En qué año se celebraron los primeros Juegos Olímpicos modernos?', '1900', '1892', '1896', '1904', 'c', 2, 3),
('¿Cuántos Grand Slams ganó Roger Federer en total?', '17', '19', '20', '21', 'c', 2, 3),
('¿En qué ciudad se jugó la final del Mundial 2014?', 'São Paulo', 'Río de Janeiro', 'Brasilia', 'Buenos Aires', 'b', 2, 3),
('¿Qué equipo ganó más veces la Champions League?', 'Barcelona', 'Bayern Munich', 'AC Milan', 'Real Madrid', 'd', 2, 3);

-- Cultura
INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel) VALUES
('¿En qué año fue publicada la primera parte de Don Quijote?', '1615', '1605', '1499', '1550', 'b', 3, 3),
('¿Cuál es el elemento químico con símbolo Au?', 'Plata', 'Platino', 'Oro', 'Aluminio', 'c', 3, 3),
('¿Cuántos huesos tiene el cuerpo humano adulto?', '206', '210', '198', '215', 'a', 3, 3),
('¿Cuál es la montaña más alta del mundo?', 'K2', 'Mont Blanc', 'Kilimanjaro', 'Monte Everest', 'd', 3, 3);


CREATE TABLE IF NOT EXISTS partidas (
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    puntaje INTEGER DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES users(id)
);
ALTER TABLE partidas ADD COLUMN categoria_id INTEGER NULL;
ALTER TABLE partidas ADD FOREIGN KEY (categoria_id) REFERENCES categorias(id);