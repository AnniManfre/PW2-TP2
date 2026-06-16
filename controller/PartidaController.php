<?php

class PartidaController {
    const TOTAL_PREGUNTAS = 10;

    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request) {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function jugar() {

        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/PW2-TP2/user/login');
            return;
        }

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
        $mensajeTipo = null;
        if (isset($_SESSION["mensaje"])) {
            $mensaje = $_SESSION["mensaje"];
            $mensajeTipo = $_SESSION["mensaje_tipo"] ?? "info";
            unset($_SESSION["mensaje"], $_SESSION["mensaje_tipo"]);
        }

        $pregunta = $this->model->obtenerPreguntaRandom($_SESSION["preguntas_usadas"]);
        if (empty($pregunta) == true) {
            if ($_SESSION["contador"] > 0) {
                $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"]);
                $_SESSION["mensaje"] = "Partida finalizada. Puntaje: " . $_SESSION["puntaje"] . "/" . $_SESSION["contador"];
                $_SESSION["mensaje_tipo"] = "info";
            } else {
                $_SESSION["mensaje"] = "No hay preguntas disponibles para jugar.";
                $_SESSION["mensaje_tipo"] = "error";
            }

            $_SESSION["puntaje"] = 0;
            $_SESSION["contador"] = 0;
            $_SESSION["preguntas_usadas"] = [];

            header("Location: /PW2-TP2/user/lobby");
            exit;
        }

        $pregunta = $pregunta[0];

        $_SESSION["preguntas_usadas"][] = $pregunta["id"];
        $_SESSION["pregunta_actual"] = $pregunta;

        $data = [
            "usuario" => $_SESSION['usuario'],
            "pregunta" => $pregunta["pregunta"],
            "a" => $pregunta["opcion_a"],
            "b" => $pregunta["opcion_b"],
            "c" => $pregunta["opcion_c"],
            "d" => $pregunta["opcion_d"],
            "mensaje" => $mensaje,
            "mensaje_tipo" => $mensajeTipo,
            "puntaje" => $_SESSION['puntaje'],

            "numero_pregunta" => $_SESSION["contador"] + 1,
            "total_preguntas" => self::TOTAL_PREGUNTAS,
            "progreso" => round(($_SESSION["contador"] / self::TOTAL_PREGUNTAS) * 100),

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
            $_SESSION["mensaje"] = "¡Correcto!";
            $_SESSION["mensaje_tipo"] = "success";
        } else {
            $_SESSION["mensaje"] = "Incorrecto. La respuesta correcta era: " . $pregunta["respuesta_correcta"];
            $_SESSION["mensaje_tipo"] = "error";
        }

        // La partida termina recién al completar las 10 preguntas (acierte o no).
        if ($_SESSION["contador"] >= self::TOTAL_PREGUNTAS) {
            $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"]);

            $_SESSION["mensaje"] = "Partida finalizada. Puntaje: " . $_SESSION["puntaje"] . "/" . self::TOTAL_PREGUNTAS;
            $_SESSION["mensaje_tipo"] = "info";
            $_SESSION["puntaje"] = 0;
            $_SESSION["contador"] = 0;
            $_SESSION["preguntas_usadas"] = [];

            header("Location: /PW2-TP2/user/lobby");
            exit;
        }

        header("Location: /PW2-TP2/partida/jugar");
        exit;
    }
}

?>