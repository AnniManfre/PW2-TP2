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
     /**
     * Calcula el nivel de una pregunta:
     * - Sin respuestas → nivel 2 (intermedio, por defecto)
     * - Más del 70% correctas → nivel 1 (fácil)
     * - Entre 30% y 70% correctas → nivel 2 (intermedio)
     * - Menos del 30% correctas → nivel 3 (difícil)
     */
    private function calcularNivelPregunta($pregunta_id) {
        $filas = $this->database->query(
            "SELECT 
                COUNT(*) AS total,
                SUM(correcta) AS correctas
             FROM respuestas
             WHERE pregunta_id = ?",
            [$pregunta_id]
        );
 
        if (empty($filas) || (int)$filas[0]['total'] === 0) {
            return 1; 
        }
 
        $total     = (int)$filas[0]['total'];
        $correctas = (int)$filas[0]['correctas'];
        $ratio     = $correctas / $total;
 
        if ($ratio > 0.70) return 1; // fácil
        if ($ratio < 0.30) return 3; // difícil
        return 2;                    // intermedio
    }

    /**
     * Obtiene una pregunta random acorde al nivel del usuario y la categoría.
     * El nivel de cada pregunta se calcula dinámicamente desde la tabla respuestas.
     * No muestra preguntas ya vistas en esta partida.
     */
    public function obtenerPreguntaRandom($idsUsados = [], $nivelUsuario = 2, $categoriaId = null) {
        
        if (empty($idsUsados)) {
            $sql = "SELECT pre.*, cat.nombre, cat.color
                    FROM preguntas pre
                    JOIN categorias cat ON pre.categoria_id = cat.id
                    WHERE pre.categoria_id = ?
                    AND pre.estado = 'activa'
                    ORDER BY RAND()";
            $candidatas = $this->database->query($sql, [$categoriaId]);
        } else {
            $ids = implode(",", array_map('intval', $idsUsados));
            $sql = "SELECT pre.*, cat.nombre, cat.color
                    FROM preguntas pre
                    JOIN categorias cat ON pre.categoria_id = cat.id
                    WHERE pre.id NOT IN ($ids)
                    AND pre.categoria_id = ?
                    AND pre.estado = 'activa'
                    ORDER BY RAND()";
            $candidatas = $this->database->query($sql, [$categoriaId]);
        }
 
          if (empty($candidatas)) {
            return [];
        }
 
        // Filtrar por nivel del usuario (calculado dinámicamente)
        $acordes = array_filter($candidatas, function($pregunta) use ($nivelUsuario) {
            return $this->calcularNivelPregunta($pregunta['id']) === $nivelUsuario;
        });
 
        // Si no hay preguntas del nivel exacto, usar cualquiera disponible
        // (para no dejar al usuario sin preguntas)
        if (empty($acordes)) {
            $acordes = $candidatas;
        }
 
        return [array_values($acordes)[0]];
    }

     /**
     * Cuenta preguntas disponibles para una categoría y nivel de usuario.
     * Usa el nivel dinámico calculado desde respuestas.
     */
    public function contarPreguntasDisponibles($categoria_id, $nivelUsuario) {
        $sql = "SELECT id FROM preguntas WHERE categoria_id = ? AND estado = 'activa'";
        $preguntas = $this->database->query($sql, [$categoria_id]);
 
        $count = 0;
        foreach ($preguntas as $p) {
            if ($this->calcularNivelPregunta($p['id']) === $nivelUsuario) {
                $count++;
            }
        }
 
        // Si no hay ninguna del nivel exacto, contar todas 
        return $count > 0 ? $count : count($preguntas);
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
      public function guardarRespuesta($usuario_id, $pregunta_id, $correcta) {
        return $this->database->execute(
            "INSERT INTO respuestas (usuario_id, pregunta_id, correcta) VALUES (?, ?, ?)",
            [$usuario_id, $pregunta_id, (int)$correcta]
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
    public function sugerirPregunta($pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id) {
        return $this->database->execute(
            "INSERT INTO preguntas (pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, categoria_id, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'sugerida')",
            [$pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id]
        );
    }
      


}

?>
