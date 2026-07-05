<?php

class PartidaModel {
    private $database;

    public function __construct($database) {
        $this->database = $database;
    }

    public function obtenerPuntajeUsuario($usuario_id) {
        $filas = $this->database->query("SELECT puntaje FROM users WHERE id = ?", [$usuario_id]);
        return !empty($filas) ? (int)$filas[0]['puntaje'] : 0;
    }

    // Obtiene la ruta de la foto de perfil de un usuario para renderizar en la barra de navegación del juego
    public function obtenerFotoPerfilUsuario($usuario_id) {
        $filas = $this->database->query("SELECT foto_perfil FROM users WHERE id = ?", [$usuario_id]);
        return !empty($filas) ? $filas[0]['foto_perfil'] : null;
    }

    public function obtenerCategorias() {
        return $this->database->query("SELECT id, nombre, color FROM categorias ORDER BY id");
    }

    public function obtenerPreguntaRandom($idsUsados = [], $nivel = 2, $categoriaId = null) {
        if (empty($idsUsados)) {
            $sql = "SELECT pre.*, cat.nombre, cat.color
                    FROM preguntas pre
                    JOIN categorias cat ON pre.categoria_id = cat.id
                    WHERE pre.nivel = ?
                    AND pre.categoria_id = ?
                    AND pre.estado = 'activa'
                    ORDER BY RAND()
                    LIMIT 1";
            return $this->database->query($sql, [$nivel, $categoriaId]);
        } else {
            $ids = implode(",", $idsUsados);
            $sql = "SELECT pre.*, cat.nombre, cat.color
                    FROM preguntas pre
                    JOIN categorias cat ON pre.categoria_id = cat.id
                    WHERE pre.id NOT IN ($ids)
                    AND pre.nivel = ?
                    AND pre.categoria_id = ?
                    AND pre.estado = 'activa'
                    ORDER BY RAND()
                    LIMIT 1";
            return $this->database->query($sql, [$nivel, $categoriaId]);
        }
    }

    public function guardarPartida($usuario_id, $puntaje,$categoria_id) {
        $this->database->execute(
            "INSERT INTO partidas (usuario_id, puntaje,categoria_id) VALUES (?, ?,?)",
            [$usuario_id, $puntaje, $categoria_id]
        );

        return $this->database->execute(
            "UPDATE users SET puntaje = puntaje + ? WHERE id = ?",
            [$puntaje, $usuario_id]
        );
    }

    // El usuario reporta una pregunta mientras juega: pasa a estado 'reportada'
    public function reportarPregunta($id) {
        return $this->database->execute(
            "UPDATE preguntas SET estado = 'reportada' WHERE id = ? AND estado = 'activa'",
            [$id]
        );
    }

    // El usuario sugiere una pregunta nueva: se guarda como 'sugerida' para que el admin la apruebe
    public function sugerirPregunta($pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id, $nivel) {
        return $this->database->execute(
            "INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, nivel, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sugerida')",
            [$pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id, $nivel]
        );
    }

}

?>
