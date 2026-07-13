<?php

class AdminModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerEstadisticas()
    {
        $totalUsuarios = $this->database->query("SELECT COUNT(*) AS total FROM users");
        $totalPreguntas = $this->database->query("SELECT COUNT(*) AS total FROM preguntas WHERE estado = 'activa'");
        $preguntasSugeridas = $this->database->query("SELECT COUNT(*) AS total FROM preguntas WHERE estado = 'sugerida'");
        $preguntasReportadas = $this->database->query("SELECT COUNT(*) AS total FROM preguntas WHERE estado = 'reportada'");
        $totalPartidas = $this->database->query("SELECT COUNT(*) AS total FROM partidas");

        return [
            'total_usuarios' => $totalUsuarios[0]['total'] ?? 0,
            'total_preguntas' => $totalPreguntas[0]['total'] ?? 0,
            'preguntas_sugeridas' => $preguntasSugeridas[0]['total'] ?? 0,
            'preguntas_reportadas' => $preguntasReportadas[0]['total'] ?? 0,
            'partidas' => $totalPartidas[0]['total'] ?? 0,
        ];
    }

    public function obtenerTodasPreguntas()
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre 
                FROM preguntas p 
                JOIN categorias c ON p.categoria_id = c.id 
                ORDER BY p.id DESC";
        return $this->database->query($sql);
    }

    public function obtenerPreguntasPorEstado($estado)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre 
                FROM preguntas p 
                JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.estado = ? 
                ORDER BY p.id DESC";
        return $this->database->query($sql, [$estado]);
    }

    public function obtenerPreguntaPorId($id)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre 
                FROM preguntas p 
                JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.id = ?";
        $res = $this->database->query($sql, [$id]);
        return !empty($res) ? $res[0] : null;
    }

    public function insertarPregunta($pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id, $estado = 'activa')
    {
        $sql = "INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->database->execute($sql, [
            $pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d,
            $respuesta_correcta, $categoria_id, $estado
        ]);
    }

    public function actualizarPregunta($id, $pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id, $estado)
    {
        $sql = "UPDATE preguntas
                SET pregunta = ?, opcion_a = ?, opcion_b = ?, opcion_c = ?, opcion_d = ?,
                    respuesta_correcta = ?, categoria_id = ?, estado = ?
                WHERE id = ?";
        return $this->database->execute($sql, [
            $pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d,
            $respuesta_correcta, $categoria_id, $estado, $id
        ]);
    }

    public function actualizarEstadoPregunta($id, $estado)
    {
        $sql = "UPDATE preguntas SET estado = ? WHERE id = ?";
        return $this->database->execute($sql, [$estado, $id]);
    }

    public function eliminarPregunta($id)
{
    
    $this->database->execute(
        "DELETE FROM respuestas WHERE pregunta_id = ?",
        [$id]
    );

    return $this->database->execute(
        "DELETE FROM preguntas WHERE id = ?",
        [$id]
    );
}

    public function obtenerTodosUsuarios()
    {
        $sql = "SELECT id, nombre_completo, usuario, email, rol, validado, cuenta_validada, puntaje 
                FROM users 
                ORDER BY id DESC";
        return $this->database->query($sql);
    }

    public function obtenerUsuarioPorId($id)
    {
        $sql = "SELECT id, nombre_completo, usuario, email, rol, validado, cuenta_validada, puntaje 
                FROM users 
                WHERE id = ?";
        $res = $this->database->query($sql, [$id]);
        return !empty($res) ? $res[0] : null;
    }

    public function actualizarRolUsuario($id, $rol)
    {
        $sql = "UPDATE users SET rol = ? WHERE id = ?";
        return $this->database->execute($sql, [$rol, $id]);
    }

    public function eliminarUsuario($id)
    {
        // Primero eliminar partidas asociadas por clave foránea
        $this->database->execute("DELETE FROM partidas WHERE usuario_id = ?", [$id]);
        $sql = "DELETE FROM users WHERE id = ?";
        return $this->database->execute($sql, [$id]);
    }

    // Métricas para reportes
    public function obtenerUsuariosPorPais()
    {
        return $this->database->query("SELECT IFNULL(pais, 'No especificado') AS pais, COUNT(*) AS cantidad FROM users GROUP BY pais ORDER BY cantidad DESC");
    }

    public function obtenerUsuariosPorSexo()
    {
        return $this->database->query("SELECT sexo, COUNT(*) AS cantidad FROM users GROUP BY sexo");
    }

    public function obtenerPreguntasPorCategoria()
    {
        $sql = "SELECT c.nombre AS categoria, COUNT(p.id) AS cantidad 
                FROM categorias c 
                LEFT JOIN preguntas p ON p.categoria_id = c.id AND p.estado = 'activa' 
                GROUP BY c.id";
        return $this->database->query($sql);
    }

    public function obtenerPartidasPorUsuario()
    {
        $sql = "SELECT u.usuario, COUNT(p.id) AS partidas_jugadas, SUM(p.puntaje) AS puntaje_total, ROUND(AVG(p.puntaje), 2) AS promedio_puntaje
                FROM partidas p 
                JOIN users u ON p.usuario_id = u.id 
                GROUP BY u.id 
                ORDER BY partidas_jugadas DESC 
                LIMIT 10";
        return $this->database->query($sql);
    }

    //Categorias ABM

    public function obtenerCategorias()
    {
        return $this->database->query("SELECT * FROM categorias ORDER BY nombre");
    }

     public function obtenerTodasCategorias()
    {
        return $this->database->query("SELECT * FROM categorias ORDER BY nombre");
    }
 
    public function obtenerCategoriaPorId($id)
    {
        $res = $this->database->query("SELECT * FROM categorias WHERE id = ?", [$id]);
        return !empty($res) ? $res[0] : null;
    }
 
    public function insertarCategoria($nombre, $color)
    {
        return $this->database->execute(
            "INSERT INTO categorias (nombre, color) VALUES (?, ?)",
            [$nombre, $color]
        );
    }
 
    public function actualizarCategoria($id, $nombre, $color)
    {
        return $this->database->execute(
            "UPDATE categorias SET nombre = ?, color = ? WHERE id = ?",
            [$nombre, $color, $id]
        );
    }
 
 public function eliminarCategoria($id)
{
    // Verificar que no tenga preguntas asociadas
    $preguntas = $this->database->query(
        "SELECT COUNT(*) AS total FROM preguntas WHERE categoria_id = ?",
        [$id]
    );
    if ($preguntas[0]['total'] > 0) {
        return 'tiene_preguntas';
    }

    // Eliminar partidas asociadas a esta categoría
    $this->database->execute(
        "DELETE FROM partidas WHERE categoria_id = ?",
        [$id]
    );

    // Ahora sí eliminar la categoría
    return $this->database->execute(
        "DELETE FROM categorias WHERE id = ?",
        [$id]
    );
}
public function obtenerRatioPregunta($pregunta_id)
{
    $filas = $this->database->query(
        "SELECT COUNT(*) AS total, SUM(correcta) AS correctas
         FROM respuestas WHERE pregunta_id = ?",
        [$pregunta_id]
    );

    if (empty($filas) || (int)$filas[0]['total'] === 0) {
        return null; // Sin historial
    }

    return (int)$filas[0]['correctas'] / (int)$filas[0]['total'];
}
}
