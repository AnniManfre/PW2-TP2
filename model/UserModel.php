<?php

class UserModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrar($nombre_completo, $anio_nacimiento, $sexo, $pais, $ciudad, $email, $password, $usuario, $foto_perfil = null)
    {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (nombre_completo, anio_nacimiento, sexo, pais, ciudad, email, password, usuario, foto_perfil, validado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE)";
        Log::info("SQL: $sql");
        return $this->database->execute($sql, [$nombre_completo, $anio_nacimiento, $sexo, $pais, $ciudad, $email, $password_hash, $usuario, $foto_perfil]);
    }

    public function loginPorCredenciales($usuario, $password)
    {
        $sql = "SELECT * FROM users WHERE usuario = ? AND validado = TRUE";
        Log::info("SQL: $sql [$usuario]");
        $filas = $this->database->query($sql, [$usuario]);
        
        if (!empty($filas)) {
            $user = $filas[0];
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return null;
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM users WHERE id = ?";
        Log::info("SQL: $sql [$id]");
        $filas = $this->database->query($sql, [$id]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function obtenerPorEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        Log::info("SQL: $sql [$email]");
        $filas = $this->database->query($sql, [$email]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function obtenerPorUsuario($usuario)
    {
        $sql = "SELECT * FROM users WHERE usuario = ?";
        Log::info("SQL: $sql [$usuario]");
        $filas = $this->database->query($sql, [$usuario]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function validarCuenta($email)
    {
        $sql = "UPDATE users SET validado = TRUE WHERE email = ?";
        Log::info("SQL: $sql [$email]");
        return $this->database->execute($sql, [$email]);
    }

    public function obtenerTodos()
    {
        $sql = "SELECT id, nombre_completo, usuario, puntaje FROM users ORDER BY puntaje DESC";
        Log::info("SQL: $sql");
        return $this->database->query($sql);
    }

    public function actualizarPerfil($id, $nombre_completo, $anio_nacimiento, $sexo, $pais, $ciudad, $foto_perfil = null)
    {
        $sql = "UPDATE users SET nombre_completo = ?, anio_nacimiento = ?, sexo = ?, pais = ?, ciudad = ?, foto_perfil = ? WHERE id = ?";
        Log::info("SQL: $sql");
        return $this->database->execute($sql, [$nombre_completo, $anio_nacimiento, $sexo, $pais, $ciudad, $foto_perfil, $id]);
    }
}

