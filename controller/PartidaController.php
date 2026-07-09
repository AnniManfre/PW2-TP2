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

        // Sólo las categorías con suficientes preguntas activas entran a la ruleta,
        // así el sorteo nunca cae en una categoría injugable.
        $categorias = $this->model->obtenerCategoriasJugables(self::TOTAL_PREGUNTAS);
        $foto_perfil = $this->model->obtenerFotoPerfilUsuario($_SESSION['user_id']);

        $data = [
            "usuario" => $_SESSION['usuario'],
            "categorias" => $categorias,
            "hay_categorias" => count($categorias) > 0,
            "esAdmin" => (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'),
            "foto_perfil" => $foto_perfil,
        ];
        if (isset($_SESSION['mensaje'])) {
        $data['mensaje']       = $_SESSION['mensaje'];
        $data['mensaje_clase'] = ($_SESSION['mensaje_tipo'] ?? 'info') === 'error' ? 'error' : 'success';
        unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
    }

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
           // Calcular el nivel del usuario para validar preguntas disponibles
        $puntajeAcumulado = $this->model->obtenerPuntajeUsuario($_SESSION['user_id']);
        $nivel = $this->calcularNivel($puntajeAcumulado);
 
        // Verificar que haya al menos 4 preguntas activas para este nivel y categoría
        $disponibles = $this->model->contarPreguntasDisponibles((int)$catId, $nivel);
 
        if ($disponibles < self::TOTAL_PREGUNTAS) {
            $_SESSION['mensaje'] = "La categoría elegida no tiene suficientes preguntas para tu nivel. 
                                    Se necesitan al menos " . self::TOTAL_PREGUNTAS . " y hay $disponibles. 
                                    Elegí otra categoría.";
            $_SESSION['mensaje_tipo'] = 'error';
            header("Location: /partida/ruleta");
            exit;
        }

        $_SESSION['categoria_partida'] = (int)$catId;
        $_SESSION['contador'] = 0;
        $_SESSION['puntaje'] = 0;
        $_SESSION['preguntas_usadas'] = [];
        unset($_SESSION['nivel_partida']);
        unset($_SESSION['pregunta_activa']);
        unset($_SESSION['pregunta_actual']);
        unset($_SESSION['inicio_pregunta']);

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

        $foto_perfil = $this->model->obtenerFotoPerfilUsuario($_SESSION['user_id']);

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
            "foto_perfil" => $foto_perfil,
        ];

        $this->renderer->render("jugarView", $data);
    }

   public function responder()
    {
        unset($_SESSION["pregunta_activa"]);
        $timeout = $this->request->post("timeout");
        $tiempoTranscurrido = time() - $_SESSION["inicio_pregunta"];

        $pregunta = $_SESSION["pregunta_actual"];
        $respuesta = $this->request->post("respuesta");

        $_SESSION["contador"]++;

        if ($timeout == "1" || $tiempoTranscurrido > 30) {
            // Tiempo agotado — guardamos la respuesta como incorrecta
            $this->model->guardarRespuesta(
                $_SESSION["user_id"],
                $pregunta["id"],
                false
            );

            $categoria_id = $_SESSION['categoria_partida'];
            $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);

            $_SESSION["mensaje"]      = "Tiempo agotado. Puntaje: " . $_SESSION["puntaje"];
            $_SESSION["mensaje_tipo"] = "error";
            $_SESSION["puntaje"]      = 0;
            $_SESSION["contador"]     = 0;
            $_SESSION["preguntas_usadas"] = [];

            unset($_SESSION["nivel_partida"]);
            unset($_SESSION["categoria_partida"]);

            header("Location: /user/lobby");
            exit;
        }

        $respuestaCorrecta = strtolower($pregunta["respuesta_correcta"]);
        $esCorrecta = ($respuesta == $pregunta["respuesta_correcta"]);

        // Guardamos la respuesta en la tabla respuestas (para calcular nivel dinámico)
        $this->model->guardarRespuesta(
            $_SESSION["user_id"],
            $pregunta["id"],
            $esCorrecta
        );

        if ($esCorrecta) {
            $_SESSION["puntaje"]++;
            $_SESSION["mensaje"]      = "¡Correcto!";
            $_SESSION["mensaje_tipo"] = "success";

            if ($_SESSION["contador"] >= self::TOTAL_PREGUNTAS) {
                // Ganó la partida
                $categoria_id = $_SESSION['categoria_partida'];
                $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);

                $_SESSION["mensaje"]      = "¡Partida finalizada! Puntaje: " . $_SESSION["puntaje"] . "/" . self::TOTAL_PREGUNTAS;
                $_SESSION["mensaje_tipo"] = "info";
                $_SESSION["puntaje"]      = 0;
                $_SESSION["contador"]     = 0;
                $_SESSION["preguntas_usadas"] = [];
                unset($_SESSION["nivel_partida"]);
                unset($_SESSION["categoria_partida"]);
                header("Location: /user/lobby");
                exit;
            }
        } else {
            // Perdió por respuesta incorrecta
            $categoria_id = $_SESSION['categoria_partida'];
            $this->model->guardarPartida($_SESSION["user_id"], $_SESSION["puntaje"], $categoria_id);

            $_SESSION["mensaje"]      = "¡Juego terminado! Respuesta incorrecta. Tu puntaje fue: " . $_SESSION["puntaje"];
            $_SESSION["mensaje_tipo"] = "error";
            $_SESSION["puntaje"]      = 0;
            $_SESSION["contador"]     = 0;
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

    // El usuario reporta la pregunta actual mientras juega (se llama por AJAX, no corta la partida)
    public function reportar()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || !isset($_SESSION["pregunta_actual"]["id"])) {
            echo json_encode(["ok" => false]);
            exit;
        }

        $this->model->reportarPregunta($_SESSION["pregunta_actual"]["id"]);
        echo json_encode(["ok" => true]);
        exit;
    }

    // Muestra el formulario para sugerir una pregunta nueva
    public function sugerir()
    {
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        $data = [
            "usuario"     => $_SESSION['usuario'],
            "categorias"  => $this->model->obtenerCategorias(),
            "esAdmin"     => (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'),
            "page_title"  => 'Sugerir Pregunta',
        ];

        if (isset($_SESSION["error_sugerir"])) {
            $data['error'] = $_SESSION["error_sugerir"];
            unset($_SESSION["error_sugerir"]);
        }

        $this->renderer->render("sugerirView", $data);
    }

    // Guarda la pregunta sugerida con estado 'sugerida' para que el admin la revise
    public function procesarSugerencia()
    {
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        $pregunta     = trim($this->request->post('pregunta') ?? '');
        $a            = trim($this->request->post('opcion_a') ?? '');
        $b            = trim($this->request->post('opcion_b') ?? '');
        $c            = trim($this->request->post('opcion_c') ?? '');
        $d            = trim($this->request->post('opcion_d') ?? '');
        $correcta     = $this->request->post('respuesta_correcta');
        $categoria_id = $this->request->post('categoria_id');

        // Validación básica
        if ($pregunta === '' || $a === '' || $b === '' || $c === '' || $d === ''
            || !in_array($correcta, ['a', 'b', 'c', 'd'], true)
            || empty($categoria_id)) {
            $_SESSION["error_sugerir"] = "Completá todos los campos y marcá cuál es la respuesta correcta.";
            Redirect::to('/partida/sugerir');
            return;
        }

        $this->model->sugerirPregunta($pregunta, $a, $b, $c, $d, $correcta, $categoria_id);

        $_SESSION["mensaje"] = "¡Gracias! Tu pregunta fue enviada y un administrador la revisará.";
        $_SESSION["mensaje_tipo"] = "success";
        Redirect::to('/user/lobby');
    }
}