<?php

class PartidaController
{
    const TOTAL_PREGUNTAS = 4;

    // Umbrales de nivel según puntaje acumulado del usuario
    // Nivel 1 (Principiante): 0–19 pts | Nivel 2 (Intermedio): 20–39 pts | Nivel 3 (Avanzado): ≥40 pts
    const NIVELES = [
        1 => 'Principiante',
        2 => 'Intermedio',
        3 => 'Avanzado',
    ];

    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    private function calcularNivel($puntaje)
    {
        if ($puntaje < 20) return 1;
        if ($puntaje < 40) return 2;
        return 3;
    }

    public function ruleta()
    {
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        $categorias = $this->model->obtenerCategorias();

        $data = [
            "usuario" => $_SESSION['usuario'],
            "categorias" => $categorias,
            "esAdmin" => (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'),
        ];

        $this->renderer->render("ruletaView", $data);
    }

    public function empezar()
    {
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        $catId = $this->request->post("categoria");
        $ids = array_column($this->model->obtenerCategorias(), "id");

        if (!in_array((int)$catId, array_map('intval', $ids), true)) {
            header("Location: /partida/ruleta");
            exit;
        }

        $_SESSION['categoria_partida'] = (int)$catId;
        $_SESSION['contador'] = 0;
        $_SESSION['puntaje'] = 0;
        $_SESSION['preguntas_usadas'] = [];
        unset($_SESSION['nivel_partida']);

        header("Location: /partida/jugar");
        exit;
    }

    public function jugar()
    {
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        // La categoría se sortea en la ruleta antes de jugar
        if (!isset($_SESSION['categoria_partida'])) {
            header("Location: /partida/ruleta");
            exit;
        }
        $categoria = $_SESSION['categoria_partida'];

        if (!isset($_SESSION['contador'])) {
            $_SESSION['contador'] = 0;
        }

        if (!isset($_SESSION["puntaje"])) {
            $_SESSION["puntaje"] = 0;
        }

        if (!isset($_SESSION["preguntas_usadas"])) {
            $_SESSION["preguntas_usadas"] = [];
        }

        if (isset($_SESSION["pregunta_activa"]) && $_SESSION["pregunta_activa"] === true) {

            // perdió por refresh - ARREGLADO CON CATEGORIA
            $categoria_id = $_SESSION['categoria_partida'];
            $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);

            $_SESSION["mensaje"] = "Perdiste la partida por recargar la página.";
            $_SESSION["mensaje_tipo"] = "error";

            $_SESSION["puntaje"] = 0;
            $_SESSION["contador"] = 0;
            $_SESSION["preguntas_usadas"] = [];

            unset($_SESSION["nivel_partida"]);
            unset($_SESSION["pregunta_activa"]);
            unset($_SESSION["categoria_partida"]);

            header("Location: /user/lobby");
            exit;
        }

        // Calcular nivel al inicio de cada partida nueva y guardarlo en sesión
        if (!isset($_SESSION['nivel_partida'])) {
            $puntajeAcumulado = $this->model->obtenerPuntajeUsuario($_SESSION['user_id']);
            $_SESSION['nivel_partida'] = $this->calcularNivel($puntajeAcumulado);
        }
        $nivel = $_SESSION['nivel_partida'];

        $mensaje = null;
        $mensajeTipo = null;
        if (isset($_SESSION["mensaje"])) {
            $mensaje = $_SESSION["mensaje"];
            $mensajeTipo = $_SESSION["mensaje_tipo"] ?? "info";
            unset($_SESSION["mensaje"], $_SESSION["mensaje_tipo"]);
        }

        $pregunta = $this->model->obtenerPreguntaRandom($_SESSION["preguntas_usadas"], $nivel, $categoria);
        if (empty($pregunta) == true) {
            if ($_SESSION["contador"] > 0) {
                // No hay más preguntas - ARREGLADO CON CATEGORIA
                $categoria_id = $_SESSION['categoria_partida'];
                $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);
                
                $_SESSION["mensaje"] = "Partida finalizada. Puntaje: " . $_SESSION["puntaje"] . "/" . $_SESSION["contador"];
                $_SESSION["mensaje_tipo"] = "info";
            } else {
                $_SESSION["mensaje"] = "No hay preguntas disponibles para jugar.";
                $_SESSION["mensaje_tipo"] = "error";
            }

            $_SESSION["puntaje"] = 0;
            $_SESSION["contador"] = 0;
            $_SESSION["preguntas_usadas"] = [];
            unset($_SESSION["nivel_partida"]);
            unset($_SESSION["categoria_partida"]);

            header("Location: /user/lobby");
            exit;
        }

        $pregunta = $pregunta[0];

        $_SESSION["preguntas_usadas"][] = $pregunta["id"];
        $_SESSION["pregunta_actual"] = $pregunta;
        $_SESSION["inicio_pregunta"] = time();
        $_SESSION["pregunta_activa"] = true;

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
            "mensaje_tipo" => $mensajeTipo,
            "puntaje" => $_SESSION['puntaje'],

            "numero_pregunta" => $_SESSION["contador"] + 1,
            "total_preguntas" => self::TOTAL_PREGUNTAS,
            "progreso" => round(($_SESSION["contador"] / self::TOTAL_PREGUNTAS) * 100),

            "categoria" => $pregunta["nombre"],
            "color" => $pregunta["color"],

            "nivel" => $nivel,
            "nivel_nombre" => self::NIVELES[$nivel],
            "tiempo_restante" => 30,
            "esAdmin" => (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'),
        ];

        $this->renderer->render("jugarView", $data);
    }

    public function responder()
    {

        unset($_SESSION["pregunta_activa"]);
        $timeout = $this->request->post("timeout");
        $tiempoTranscurrido = time() - $_SESSION["inicio_pregunta"];

        $pregunta = $_SESSION["pregunta_actual"];
        $respuestaEnviada = $this->request->post("respuesta");
        $respuesta = $respuestaEnviada;

        $_SESSION["contador"]++;

        if ($timeout == "1" || $tiempoTranscurrido > 30) {
            
            // Tiempo agotado - ARREGLADO CON CATEGORIA
            $categoria_id = $_SESSION['categoria_partida'];
            $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);

            $_SESSION["mensaje"] =
                "Tiempo agotado. Puntaje: " .
                $_SESSION["puntaje"];

            $_SESSION["mensaje_tipo"] = "error";

            $_SESSION["puntaje"] = 0;
            $_SESSION["contador"] = 0;
            $_SESSION["preguntas_usadas"] = [];

            unset($_SESSION["nivel_partida"]);
            unset($_SESSION["categoria_partida"]);

            header("Location: /user/lobby");
            exit;
        }

        // Obtener el texto de la respuesta correcta desde la base de datos
        $respuestaCorrecta = strtolower($pregunta["respuesta_correcta"]);
        $textoRespuestaCorrecta = $pregunta["opcion_" . $respuestaCorrecta];

        if ($respuesta == $pregunta["respuesta_correcta"]) {
            $_SESSION["puntaje"]++;
            $_SESSION["mensaje"] = "¡Correcto!";
            $_SESSION["mensaje_tipo"] = "success";

            if ($_SESSION["contador"] >= self::TOTAL_PREGUNTAS) {
                
                // Ganó la partida - ARREGLADO CON CATEGORIA
                $categoria_id = $_SESSION['categoria_partida'];
                $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);
                
                $_SESSION["mensaje"] = "¡Partida finalizada! Puntaje: " . $_SESSION["puntaje"] . "/" . self::TOTAL_PREGUNTAS;
                $_SESSION["mensaje_tipo"] = "info";
                $_SESSION["puntaje"] = 0;
                $_SESSION["contador"] = 0;
                $_SESSION["preguntas_usadas"] = [];
                unset($_SESSION["nivel_partida"]);
                unset($_SESSION["categoria_partida"]);
                header("Location: /user/lobby");
                exit;
            }
        } else {
            
            // Perdió por error - ARREGLADO CON CATEGORIA
            $categoria_id = $_SESSION['categoria_partida'];
            $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);
            
            $_SESSION["mensaje"] = "¡Juego terminado! Respuesta incorrecta. Tu puntaje fue: " . $_SESSION["puntaje"];
            $_SESSION["mensaje_tipo"] = "error";
            $_SESSION["puntaje"] = 0;
            $_SESSION["contador"] = 0;
            $_SESSION["preguntas_usadas"] = [];
            unset($_SESSION["nivel_partida"]);
            unset($_SESSION["categoria_partida"]);
            header("Location: /user/lobby");
            exit;
        }

        header("Location: /partida/jugar");
        exit;
    }

    public function abandonar()
    {
        if (isset($_SESSION['user_id']) && isset($_SESSION["contador"]) && $_SESSION["contador"] >= 0 && !empty($_SESSION["preguntas_usadas"])) {
            
            // Abandonó el juego - ARREGLADO CON CATEGORIA
            $categoria_id = $_SESSION['categoria_partida'];
            $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);
            
            $_SESSION["mensaje"] = "Juego abandonado. Tu puntaje fue: " . $_SESSION["puntaje"];
            $_SESSION["puntaje"] = 0;
            $_SESSION["contador"] = 0;
            $_SESSION["preguntas_usadas"] = [];
            unset($_SESSION["nivel_partida"]);
            unset($_SESSION["categoria_partida"]);
            header("Location: /user/lobby");
        } else {
            header("Location: /user/home");
        }
        exit;
    }
}