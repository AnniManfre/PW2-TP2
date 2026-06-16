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

        // Crear array de opciones y shufflearlo
        $opciones = [
            "a" => $pregunta["opcion_a"],
            "b" => $pregunta["opcion_b"],
            "c" => $pregunta["opcion_c"],
            "d" => $pregunta["opcion_d"]
        ];
        
        $letras = array_keys($opciones);
        shuffle($letras);
        
        // Guardar el mapeo para usar en responder()
        $_SESSION["pregunta_actual"]["mapeo_opciones"] = $letras;
        
        // Crear datos para la vista con opciones shuffleadas
        $opcionesShuffleadas = [];
        foreach ($letras as $indice => $letra) {
            $opcionesShuffleadas[] = [
                "letra" => chr(65 + $indice), // A, B, C, D
                "valor" => $letra, // a, b, c, d (valor original para validar)
                "texto" => $opciones[$letra]
            ];
        }

        $data = [
            "usuario" => $_SESSION['usuario'],
            "pregunta" => $pregunta["pregunta"],
            "opciones" => $opcionesShuffleadas,
            "mensaje" => $mensaje,
            "puntaje" => $_SESSION['puntaje'],

            "categoria" => $pregunta["nombre"],
            "color" => $pregunta["color"]
        ];

        $this->renderer->render("jugarView", $data);
    }

    public function responder() {
        $pregunta = $_SESSION["pregunta_actual"];
        $respuestaEnviada = $this->request->post("respuesta");
        
        // Si la respuesta está en el mapeo, es una letra original (a, b, c, d)
        // Si no, podría ser un índice del nuevo orden (1, 2, 3, 4)
        $respuesta = $respuestaEnviada;

        $_SESSION["contador"]++;

        // Obtener el texto de la respuesta correcta desde la base de datos
        $respuestaCorrecta = strtolower($pregunta["respuesta_correcta"]);
        $textoRespuestaCorrecta = $pregunta["opcion_" . $respuestaCorrecta];

        if ($respuesta == $pregunta["respuesta_correcta"]) {
            $_SESSION["puntaje"]++;
            $_SESSION["mensaje"] = "¡Correcto!";
            $_SESSION["mensaje_tipo"] = "success";
        } else {
            $_SESSION["mensaje"] = "Incorrecto. La respuesta correcta es: " . $textoRespuestaCorrecta;
            $_SESSION["mensaje_tipo"] = "error";
        }

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