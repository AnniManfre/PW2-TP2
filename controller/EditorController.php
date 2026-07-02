<?php

// Panel del editor: puede crear/editar/eliminar preguntas y revisar las sugeridas.
// No accede a reportadas, usuarios ni estadísticas.
// El control de acceso (rol editor) lo resuelve Auth::guard() en index.php.
// Reutiliza AdminModel y las vistas compartidas de view/admin/ (con base = 'editor').
class EditorController
{
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function dashboard()
    {
        Log::info("EditorController::dashboard");
        $this->renderer->render("admin/editorPanel", [
            'staff' => $_SESSION['usuario'] ?? '',
        ]);
    }

    public function preguntas()
    {
        Log::info("EditorController::preguntas");
        $preguntas = $this->model->obtenerPreguntasPorEstado('activa');
        $this->renderer->render("admin/adminPreguntas", [
            'preguntas' => $preguntas,
            'base' => 'editor',
            'page_title' => 'Administrar Preguntas',
        ]);
    }

    public function agregarPregunta()
    {
        Log::info("EditorController::agregarPregunta");
        $this->renderer->render("admin/adminAgregarPregunta", [
            'categorias' => $this->model->obtenerCategorias(),
            'titulo_accion' => 'Agregar Pregunta',
            'action_url' => '/editor/procesarAgregarPregunta',
            'base' => 'editor',
            'page_title' => 'Agregar Pregunta',
        ]);
    }

    public function procesarAgregarPregunta()
    {
        Log::info("EditorController::procesarAgregarPregunta");

        $pregunta = $this->request->post('pregunta');
        $opcion_a = $this->request->post('opcion_a');
        $opcion_b = $this->request->post('opcion_b');
        $opcion_c = $this->request->post('opcion_c');
        $opcion_d = $this->request->post('opcion_d');
        $respuesta_correcta = $this->request->post('respuesta_correcta');
        $categoria_id = (int)$this->request->post('categoria_id');
        $nivel = (int)$this->request->post('nivel');

        if (empty($pregunta) || empty($opcion_a) || empty($opcion_b) || empty($opcion_c) || empty($opcion_d) || empty($respuesta_correcta) || empty($categoria_id) || empty($nivel)) {
            $this->renderer->render("admin/adminAgregarPregunta", [
                'error' => 'Todos los campos son obligatorios',
                'categorias' => $this->model->obtenerCategorias(),
                'titulo_accion' => 'Agregar Pregunta',
                'action_url' => '/editor/procesarAgregarPregunta',
                'base' => 'editor',
            ]);
            return;
        }

        $this->model->insertarPregunta($pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id, $nivel, 'activa');

        $_SESSION['mensaje'] = "Pregunta creada exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
        Redirect::to('/editor/preguntas');
    }

    public function editarPregunta()
    {
        $id = (int)$this->request->get('id');
        Log::info("EditorController::editarPregunta - ID: $id");

        $pregunta = $this->model->obtenerPreguntaPorId($id);
        if (!$pregunta) {
            Redirect::to('/editor/preguntas');
            return;
        }

        $categorias = $this->model->obtenerCategorias();
        foreach ($categorias as &$cat) {
            $cat['selected'] = ($cat['id'] == $pregunta['categoria_id']);
        }

        $niveles = [
            ['valor' => 1, 'nombre' => 'Fácil (Principiante)', 'selected' => ($pregunta['nivel'] == 1)],
            ['valor' => 2, 'nombre' => 'Medio (Intermedio)', 'selected' => ($pregunta['nivel'] == 2)],
            ['valor' => 3, 'nombre' => 'Difícil (Avanzado)', 'selected' => ($pregunta['nivel'] == 3)]
        ];

        $respuestas = [
            ['valor' => 'a', 'selected' => (strtolower($pregunta['respuesta_correcta']) == 'a')],
            ['valor' => 'b', 'selected' => (strtolower($pregunta['respuesta_correcta']) == 'b')],
            ['valor' => 'c', 'selected' => (strtolower($pregunta['respuesta_correcta']) == 'c')],
            ['valor' => 'd', 'selected' => (strtolower($pregunta['respuesta_correcta']) == 'd')]
        ];

        $this->renderer->render("admin/adminAgregarPregunta", [
            'pregunta' => $pregunta,
            'categorias' => $categorias,
            'niveles' => $niveles,
            'respuestas' => $respuestas,
            'titulo_accion' => 'Editar Pregunta',
            'action_url' => '/editor/procesarEditarPregunta',
            'is_edit' => true,
            'base' => 'editor',
            'page_title' => 'Editar Pregunta',
        ]);
    }

    public function procesarEditarPregunta()
    {
        $id = (int)$this->request->post('id');
        Log::info("EditorController::procesarEditarPregunta - ID: $id");

        $pregunta = $this->request->post('pregunta');
        $opcion_a = $this->request->post('opcion_a');
        $opcion_b = $this->request->post('opcion_b');
        $opcion_c = $this->request->post('opcion_c');
        $opcion_d = $this->request->post('opcion_d');
        $respuesta_correcta = $this->request->post('respuesta_correcta');
        $categoria_id = (int)$this->request->post('categoria_id');
        $nivel = (int)$this->request->post('nivel');
        $estado = $this->request->post('estado') ?? 'activa';

        if (empty($pregunta) || empty($opcion_a) || empty($opcion_b) || empty($opcion_c) || empty($opcion_d) || empty($respuesta_correcta) || empty($categoria_id) || empty($nivel)) {
            $this->renderer->render("admin/adminAgregarPregunta", [
                'error' => 'Todos los campos son obligatorios',
                'pregunta' => ['id' => $id, 'pregunta' => $pregunta, 'opcion_a' => $opcion_a, 'opcion_b' => $opcion_b, 'opcion_c' => $opcion_c, 'opcion_d' => $opcion_d],
                'categorias' => $this->model->obtenerCategorias(),
                'titulo_accion' => 'Editar Pregunta',
                'action_url' => '/editor/procesarEditarPregunta',
                'is_edit' => true,
                'base' => 'editor',
            ]);
            return;
        }

        $this->model->actualizarPregunta($id, $pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id, $nivel, $estado);

        $_SESSION['mensaje'] = "Pregunta actualizada correctamente.";
        $_SESSION['mensaje_tipo'] = "success";

        // El editor solo maneja preguntas activas y sugeridas.
        Redirect::to($estado === 'sugerida' ? '/editor/sugeridas' : '/editor/preguntas');
    }

    public function eliminarPregunta()
    {
        $id = (int)$this->request->get('id');
        Log::info("EditorController::eliminarPregunta - ID: $id");

        $pregunta = $this->model->obtenerPreguntaPorId($id);
        $this->model->eliminarPregunta($id);

        $_SESSION['mensaje'] = "Pregunta eliminada exitosamente.";
        $_SESSION['mensaje_tipo'] = "info";

        Redirect::to(($pregunta && $pregunta['estado'] === 'sugerida') ? '/editor/sugeridas' : '/editor/preguntas');
    }

    public function sugeridas()
    {
        Log::info("EditorController::sugeridas");
        $preguntas = $this->model->obtenerPreguntasPorEstado('sugerida');
        $this->renderer->render("admin/adminSugeridas", [
            'preguntas' => $preguntas,
            'base' => 'editor',
            'page_title' => 'Preguntas Sugeridas',
        ]);
    }

    public function aprobarSugerida()
    {
        $id = (int)$this->request->get('id');
        Log::info("EditorController::aprobarSugerida - ID: $id");

        $this->model->actualizarEstadoPregunta($id, 'activa');

        $_SESSION['mensaje'] = "La pregunta sugerida ha sido aprobada e incorporada al juego.";
        $_SESSION['mensaje_tipo'] = "success";

        Redirect::to('/editor/sugeridas');
    }
}
