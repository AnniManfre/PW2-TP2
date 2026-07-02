<?php

class AdminController
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

    // El control de acceso (rol admin) lo resuelve Auth::guard() en index.php.

    public function dashboard()
    {
        Log::info("AdminController::dashboard");

        $stats = $this->model->obtenerEstadisticas();
        // 'staff' (no 'usuario') para no disparar el link de perfil de jugador del header.
        $stats['staff'] = $_SESSION['usuario'] ?? '';
        // Sin page_title: el hero del panel ya muestra el título; así la banda
        // superior no lo repite.
        $this->renderer->render("admin/dashboard", $stats);
    }

    public function preguntas()
    {
        Log::info("AdminController::preguntas");

        $preguntas = $this->model->obtenerPreguntasPorEstado('activa');
        $this->renderer->render("admin/adminPreguntas", [
            'preguntas' => $preguntas,
            'base' => 'admin',
            'page_title' => 'Administrar Preguntas',
        ]);
    }

    public function agregarPregunta()
    {
        Log::info("AdminController::agregarPregunta");

        $categorias = $this->model->obtenerCategorias();
        $this->renderer->render("admin/adminAgregarPregunta", [
            'categorias' => $categorias,
            'titulo_accion' => 'Agregar Pregunta',
            'action_url' => '/admin/procesarAgregarPregunta',
            'base' => 'admin',
            'page_title' => 'Agregar Pregunta',
        ]);
    }

    public function procesarAgregarPregunta()
    {
        Log::info("AdminController::procesarAgregarPregunta");

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
            $categorias = $this->model->obtenerCategorias();
            $this->renderer->render("admin/adminAgregarPregunta", [
                'error' => 'Todos los campos son obligatorios',
                'categorias' => $categorias,
                'titulo_accion' => 'Agregar Pregunta',
                'action_url' => '/admin/procesarAgregarPregunta',
                'base' => 'admin',
            ]);
            return;
        }

        $this->model->insertarPregunta($pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id, $nivel, $estado);

        $_SESSION['mensaje'] = "Pregunta creada exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";

        if ($estado === 'sugerida') {
            Redirect::to('/admin/sugeridas');
        } else {
            Redirect::to('/admin/preguntas');
        }
    }

    public function editarPregunta()
    {
        $id = (int)$this->request->get('id');
        Log::info("AdminController::editarPregunta - ID: $id");

        $pregunta = $this->model->obtenerPreguntaPorId($id);
        if (!$pregunta) {
            Redirect::to('/admin/preguntas');
            return;
        }

        $categorias = $this->model->obtenerCategorias();

        // Marcar la categoría seleccionada y la respuesta correcta
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
            'action_url' => '/admin/procesarEditarPregunta',
            'is_edit' => true,
            'base' => 'admin',
            'page_title' => 'Editar Pregunta',
        ]);
    }

    public function procesarEditarPregunta()
    {
        $id = (int)$this->request->post('id');
        Log::info("AdminController::procesarEditarPregunta - ID: $id");

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
            $categorias = $this->model->obtenerCategorias();
            $this->renderer->render("admin/adminAgregarPregunta", [
                'error' => 'Todos los campos son obligatorios',
                'pregunta' => ['id' => $id, 'pregunta' => $pregunta, 'opcion_a' => $opcion_a, 'opcion_b' => $opcion_b, 'opcion_c' => $opcion_c, 'opcion_d' => $opcion_d],
                'categorias' => $categorias,
                'titulo_accion' => 'Editar Pregunta',
                'action_url' => '/admin/procesarEditarPregunta',
                'is_edit' => true,
                'base' => 'admin',
            ]);
            return;
        }

        $this->model->actualizarPregunta($id, $pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $categoria_id, $nivel, $estado);

        $_SESSION['mensaje'] = "Pregunta actualizada correctamente.";
        $_SESSION['mensaje_tipo'] = "success";

        if ($estado === 'sugerida') {
            Redirect::to('/admin/sugeridas');
        } elseif ($estado === 'reportada') {
            Redirect::to('/admin/reportadas');
        } else {
            Redirect::to('/admin/preguntas');
        }
    }

    public function eliminarPregunta()
    {
        $id = (int)$this->request->get('id');
        Log::info("AdminController::eliminarPregunta - ID: $id");

        $pregunta = $this->model->obtenerPreguntaPorId($id);
        $this->model->eliminarPregunta($id);

        $_SESSION['mensaje'] = "Pregunta eliminada exitosamente.";
        $_SESSION['mensaje_tipo'] = "info";

        if ($pregunta && $pregunta['estado'] === 'sugerida') {
            Redirect::to('/admin/sugeridas');
        } elseif ($pregunta && $pregunta['estado'] === 'reportada') {
            Redirect::to('/admin/reportadas');
        } else {
            Redirect::to('/admin/preguntas');
        }
    }

    public function sugeridas()
    {
        Log::info("AdminController::sugeridas");

        $preguntas = $this->model->obtenerPreguntasPorEstado('sugerida');
        $this->renderer->render("admin/adminSugeridas", [
            'preguntas' => $preguntas,
            'base' => 'admin',
            'page_title' => 'Preguntas Sugeridas',
        ]);
    }

    public function aprobarSugerida()
    {
        $id = (int)$this->request->get('id');
        Log::info("AdminController::aprobarSugerida - ID: $id");

        $this->model->actualizarEstadoPregunta($id, 'activa');

        $_SESSION['mensaje'] = "La pregunta sugerida ha sido aprobada e incorporada al juego.";
        $_SESSION['mensaje_tipo'] = "success";

        Redirect::to('/admin/sugeridas');
    }

    public function reportadas()
    {
        Log::info("AdminController::reportadas");

        $preguntas = $this->model->obtenerPreguntasPorEstado('reportada');
        $this->renderer->render("admin/adminReportadas", [
            'preguntas' => $preguntas,
            'base' => 'admin',
            'page_title' => 'Preguntas Reportadas',
        ]);
    }

    public function resolverReporte()
    {
        $id = (int)$this->request->get('id');
        $accion = $this->request->get('accion'); // 'keep' o 'delete'
        Log::info("AdminController::resolverReporte - ID: $id, Accion: $accion");

        if ($accion === 'keep') {
            $this->model->actualizarEstadoPregunta($id, 'activa');
            $_SESSION['mensaje'] = "El reporte ha sido descartado. La pregunta sigue activa.";
            $_SESSION['mensaje_tipo'] = "success";
        } elseif ($accion === 'delete') {
            $this->model->eliminarPregunta($id);
            $_SESSION['mensaje'] = "La pregunta reportada ha sido eliminada.";
            $_SESSION['mensaje_tipo'] = "info";
        }

        Redirect::to('/admin/reportadas');
    }

    public function usuarios()
    {
        Log::info("AdminController::usuarios");

        $usuarios = $this->model->obtenerTodosUsuarios();
        $this->renderer->render("admin/adminUsuarios", [
            'usuarios' => $usuarios,
            'page_title' => 'Administrar Usuarios',
        ]);
    }

    public function eliminarUsuario()
    {
        $id = (int)$this->request->get('id');
        Log::info("AdminController::eliminarUsuario - ID: $id");

        $this->model->eliminarUsuario($id);
        $_SESSION['mensaje'] = "El usuario y todas sus partidas asociadas han sido eliminados.";
        $_SESSION['mensaje_tipo'] = "info";

        Redirect::to('/admin/usuarios');
    }

    public function reportes()
    {
        Log::info("AdminController::reportes");

        $data = $this->datosReportes();
        // Datos en JSON para los gráficos (Chart.js) de la vista.
        $data['sexo_json'] = json_encode($this->paraGrafico($data['usuarios_por_sexo'], 'sexo', 'cantidad'));
        $data['categoria_json'] = json_encode($this->paraGrafico($data['preguntas_por_categoria'], 'categoria', 'cantidad'));
        $data['pais_json'] = json_encode($this->paraGrafico($data['usuarios_por_pais'], 'pais', 'cantidad'));
        $data['page_title'] = 'Reportes y Estadísticas';

        $this->renderer->render("admin/adminReportes", $data);
    }

    // Genera el PDF del panel de estadísticas con dompdf
    public function reportesPdf()
    {
        Log::info("AdminController::reportesPdf");

        ini_set('display_errors', '0');

        require_once __DIR__ . '/../vendor/autoload.php';

        $data = $this->datosReportes();
        $html = $this->renderer->fetch("admin/reportesPdf", $data);

        // Usamos temp del sistema (escribible por Apache) y fuentes core del PDF
        // (Helvetica, definida en el template): así dompdf no intenta embeber ningún
        // .ttf dentro de htdocs/vendor, que el usuario de Apache no puede escribir.
        $options = new \Dompdf\Options();
        $options->set('tempDir', sys_get_temp_dir());
        $options->set('isRemoteEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Descartar cualquier salida previa (avisos, espacios) para no corromper el PDF.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream("estadisticas-trivia-clash.pdf", ['Attachment' => true]);
        exit;
    }

    private function datosReportes()
    {
        return [
            'usuarios_por_pais' => $this->model->obtenerUsuariosPorPais(),
            'usuarios_por_sexo' => $this->model->obtenerUsuariosPorSexo(),
            'preguntas_por_categoria' => $this->model->obtenerPreguntasPorCategoria(),
            'partidas_por_usuario' => $this->model->obtenerPartidasPorUsuario(),
        ];
    }

    // Convierte una lista de filas en {labels:[...], valores:[...]} para Chart.js.
    private function paraGrafico($filas, $claveEtiqueta, $claveValor)
    {
        $labels = [];
        $valores = [];
        foreach ($filas as $fila) {
            $labels[] = $fila[$claveEtiqueta];
            $valores[] = (int)$fila[$claveValor];
        }
        return ['labels' => $labels, 'valores' => $valores];
    }
}
