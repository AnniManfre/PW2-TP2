<?php

class UserModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrar($nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $email, $password, $usuario, $foto_perfil = null)
    {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (nombre_completo, año_nacimiento, sexo, pais, ciudad, email, password, usuario, foto_perfil, validado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE)";
        Log::info("SQL: $sql");
        return $this->database->execute($sql, [$nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $email, $password_hash, $usuario, $foto_perfil]);
    }

    public function loginPorCredenciales($usuario, $password)
    {
        $sql = "SELECT * FROM users WHERE usuario = ?";
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

    public function contarPartidasGanadas($id, $puntajePerfecto)
    {
        $sql = "SELECT COUNT(*) AS ganadas FROM partidas WHERE usuario_id = ? AND puntaje = ?";
        Log::info("SQL: $sql [$id, $puntajePerfecto]");
        $filas = $this->database->query($sql, [$id, $puntajePerfecto]);
        return !empty($filas) ? (int)$filas[0]["ganadas"] : 0;
    }

    public function actualizarPerfil($id, $nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $foto_perfil = null)
    {
        $sql = "UPDATE users SET nombre_completo = ?, año_nacimiento = ?, sexo = ?, pais = ?, ciudad = ?, foto_perfil = ? WHERE id = ?";
        Log::info("SQL: $sql");
        return $this->database->execute($sql, [$nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $foto_perfil, $id]);
    }

    public function guardarTokenValidacion($email, $token)
    {
        $sql = "UPDATE users SET token = ?, cuenta_validada = 0 WHERE email = ?";
        return $this->database->execute($sql, [$token, $email]);
    }

    public function verificarToken($email, $token)
    {
        $sql = "SELECT id FROM users WHERE email = ? AND token = ?";
        $resultado = $this->database->query($sql, [$email, $token]);
        return !empty($resultado);
    }

    public function activarCuenta($email)
    {
        // Valida la cuenta y remueve el token por seguridad
        $sql = "UPDATE users SET cuenta_validada = 1, token = NULL WHERE email = ?";
        return $this->database->execute($sql, [$email]);
    }
    public function obtenerPartidasRecientes($usuario_id)
    {
        $sql = "SELECT p.id, 
                       p.puntaje, 
                       DATE_FORMAT(p.fecha, '%d/%m/%Y %H:%i') AS fecha_formateada,
                       c.nombre AS categoria_nombre,
                       c.color AS categoria_color
                FROM partidas p 
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.usuario_id = ? 
                ORDER BY p.fecha DESC";
                
        return $this->database->query($sql, [$usuario_id]);
    }

public function getEfectividad($id, $puntajePerfecto)
    {
        $sql = "SELECT puntaje FROM partidas WHERE usuario_id = ?";
        $historial = $this->database->query($sql, [$id]);

        if (empty($historial)) {
            return 0; // Si nunca jugó, efectividad 0%
        }

        $respuestas_correctas = 0;
        $preguntas_jugadas = 0;

        foreach ($historial as $partida) {
            $puntos = (int)$partida['puntaje'];
            $respuestas_correctas += $puntos;

            if ($puntos === $puntajePerfecto) {
                
                $preguntas_jugadas += $puntajePerfecto;
            } else {
                
                $preguntas_jugadas += ($puntos + 1);
            }
        }

        if ($preguntas_jugadas > 0) {
            return round(($respuestas_correctas / $preguntas_jugadas) * 100);
        }

        return 0;
    }
    public function obtenerRacha($id, $puntajePerfecto)
    {
        $sql = "SELECT puntaje FROM partidas WHERE usuario_id = ? ORDER BY fecha DESC";
        $historial = $this->database->query($sql, [$id]);

        $racha = 0;

        if (!empty($historial)) {
            foreach ($historial as $partida) {
                if ((int)$partida['puntaje'] === $puntajePerfecto) {
                    $racha++;
                } else {
                    break; 
                }
            }
        }

        return $racha;
    }
}

