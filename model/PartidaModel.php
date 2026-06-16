<?php

class PartidaModel {
    private $database;

    public function __construct($database) {
        $this->database = $database;
    }

    public function obtenerPreguntaRandom($idsUsados = []) {
        if (empty($idsUsados) == true) {
            $sql = "SELECT pre.*, cat.nombre, cat.color
                    FROM preguntas pre
                    JOIN categorias cat ON pre.categoria_id = cat.id
                    ORDER BY RAND()
                    LIMIT 1";
        } else {
            $ids = implode(",", $idsUsados);
            $sql = "SELECT pre.*, cat.nombre, cat.color
                    FROM preguntas pre
                    JOIN categorias cat ON pre.categoria_id = cat.id
                    WHERE pre.id NOT IN ($ids)
                    ORDER BY RAND()
                    LIMIT 1";
        }

        return $this->database->query($sql);
    }

    public function guardarPartida($usuario_id, $puntaje) {
        $this->database->execute(
            "INSERT INTO partidas (usuario_id, puntaje) VALUES (?, ?)",
            [$usuario_id, $puntaje]
        );

        // Acumulamos el puntaje de la partida en el total del usuario (sus "Tkn").
        return $this->database->execute(
            "UPDATE users SET puntaje = puntaje + ? WHERE id = ?",
            [$puntaje, $usuario_id]
        );
    }
}

?>