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

    public function obtenerPreguntaRandom($idsUsados = [], $nivel = 2) {
        if (empty($idsUsados)) {
            $sql = "SELECT pre.*, cat.nombre, cat.color
                    FROM preguntas pre
                    JOIN categorias cat ON pre.categoria_id = cat.id
                    WHERE pre.nivel = ?
                    ORDER BY RAND()
                    LIMIT 1";
            return $this->database->query($sql, [$nivel]);
        } else {
            $ids = implode(",", $idsUsados);
            $sql = "SELECT pre.*, cat.nombre, cat.color
                    FROM preguntas pre
                    JOIN categorias cat ON pre.categoria_id = cat.id
                    WHERE pre.id NOT IN ($ids)
                    AND pre.nivel = ?
                    ORDER BY RAND()
                    LIMIT 1";
            return $this->database->query($sql, [$nivel]);
        }
    }

    public function guardarPartida($usuario_id, $puntaje) {
        $this->database->execute(
            "INSERT INTO partidas (usuario_id, puntaje) VALUES (?, ?)",
            [$usuario_id, $puntaje]
        );

        return $this->database->execute(
            "UPDATE users SET puntaje = puntaje + ? WHERE id = ?",
            [$puntaje, $usuario_id]
        );
    }
}

?>
