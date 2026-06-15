<?php

class PartidaController {
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request) {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function jugar() {
        if (!isset($_SESSION['contador'])) {
            $_SESSION['contador'] = 0;
        }

        if (!isset($_SESSION["puntaje"])) {
            $_SESSION["puntaje"] = 0;
        }

        if (!isset($_SESSION["preguntas_usadas"])) {
            $_SESSION["preguntas_usadas"] = [];
        }

        $mensaje = null;
        if (isset($_SESSION["mensaje"])) {
            $mensaje = $_SESSION["mensaje"];
            unset($_SESSION["mensaje"]);
        }

        $pregunta = $this->model->obtenerPreguntaRandom($_SESSION["preguntas_usadas"]);
        if (empty($pregunta) == true) {
            $_SESSION["mensaje"] = "Error, no hay más preguntas.";

            header("Location: /PW2-TP2/user/lobby");
            exit;
        }

        $pregunta = $pregunta[0];

        $_SESSION["preguntas_usadas"][] = $pregunta["id"];
        $_SESSION["pregunta_actual"] = $pregunta;

        $data = [
            "pregunta" => $pregunta["pregunta"],
            "a" => $pregunta["opcion_a"],
            "b" => $pregunta["opcion_b"],
            "c" => $pregunta["opcion_c"],
            "d" => $pregunta["opcion_d"],
            "mensaje" => $mensaje,
            "puntaje" => $_SESSION['puntaje'],

            "categoria" => $pregunta["nombre"],
            "color" => $pregunta["color"]
        ];

        $this->renderer->render("jugarView", $data);
    }

    public function responder() {
        $pregunta = $_SESSION["pregunta_actual"];
        $respuesta = $this->request->post("respuesta");

        $_SESSION["contador"]++;

        if ($respuesta == $pregunta["respuesta_correcta"]) {
            $_SESSION["puntaje"]++;
            $_SESSION["mensaje"] = "Correcto";

            if ($_SESSION["contador"] >= 10) {
                $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"]);

                $_SESSION["puntaje"] = 0;
                $_SESSION["contador"] = 0;
                $_SESSION["preguntas_usadas"] = [];
                $_SESSION["mensaje"] = "Partida finalizada";

                header("Location: /PW2-TP2/lobby");
                exit;
            }
        } else if ($respuesta != $pregunta["respuesta_correcta"]) {
            $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"]);

            $_SESSION["puntaje"] = 0;
            $_SESSION["contador"] = 0;
            $_SESSION["preguntas_usadas"] = [];
            $_SESSION["mensaje"] = "Respuesta incorrecta. La respuesta correcta era: " . $pregunta["respuesta_correcta"];

            header("Location: /PW2-TP2/partida/jugar");
            exit;
        }

        header("Location: /PW2-TP2/partida/jugar");
        exit;
    }
}

?>