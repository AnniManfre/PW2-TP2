CREATE SCHEMA IF NOT EXISTS juego;
USE juego;

-- Tabla de usuarios - Solo lo que pide la consigna
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
