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

    private function checkAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            Redirect::to('/user/lobby');
            exit();
        }
    }

    public function dashboard()
    {
        $this->checkAdmin();
        Log::info("AdminController::dashboard");
        
        $stats = $this->model->obtenerEstadisticas();
        $this->renderer->render("dashboard/dashboard", $stats);
    }

    public function preguntas()
    {
        $this->checkAdmin();
        Log::info("AdminController::preguntas");

        $preguntas = $this->model->obtenerPreguntasPorEstado('activa');
        $this->renderer->render("dashboard/adminPreguntas", ['preguntas' => $preguntas]);
    }

    public function agregarPregunta()
    {
        $this->checkAdmin();
        Log::info("AdminController::agregarPregunta");

        $categorias = $this->model->obtenerCategorias();
        $this->renderer->render("dashboard/adminAgregarPregunta", [
            'categorias' => $categorias,
            'titulo_accion' => 'Agregar Pregunta',
            'action_url' => '/admin/procesarAgregarPregunta'
        ]);
    }

    public function procesarAgregarPregunta()
    {
        $this->checkAdmin();
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
            $this->renderer->render("dashboard/adminAgregarPregunta", [
                'error' => 'Todos los campos son obligatorios',
                'categorias' => $categorias,
                'titulo_accion' => 'Agregar Pregunta',
                'action_url' => '/admin/procesarAgregarPregunta'
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
        $this->checkAdmin();
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

        $this->renderer->render("dashboard/adminAgregarPregunta", [
            'pregunta' => $pregunta,
            'categorias' => $categorias,
            'niveles' => $niveles,
            'respuestas' => $respuestas,
            'titulo_accion' => 'Editar Pregunta',
            'action_url' => '/admin/procesarEditarPregunta',
            'is_edit' => true
        ]);
    }

    public function procesarEditarPregunta()
    {
        $this->checkAdmin();
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
            $this->renderer->render("dashboard/adminAgregarPregunta", [
                'error' => 'Todos los campos son obligatorios',
                'pregunta' => ['id' => $id, 'pregunta' => $pregunta, 'opcion_a' => $opcion_a, 'opcion_b' => $opcion_b, 'opcion_c' => $opcion_c, 'opcion_d' => $opcion_d],
                'categorias' => $categorias,
                'titulo_accion' => 'Editar Pregunta',
                'action_url' => '/admin/procesarEditarPregunta',
                'is_edit' => true
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
        $this->checkAdmin();
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
        $this->checkAdmin();
        Log::info("AdminController::sugeridas");

        $preguntas = $this->model->obtenerPreguntasPorEstado('sugerida');
        $this->renderer->render("dashboard/adminSugeridas", ['preguntas' => $preguntas]);
    }

    public function aprobarSugerida()
    {
        $this->checkAdmin();
        $id = (int)$this->request->get('id');
        Log::info("AdminController::aprobarSugerida - ID: $id");

        $this->model->actualizarEstadoPregunta($id, 'activa');

        $_SESSION['mensaje'] = "La pregunta sugerida ha sido aprobada e incorporada al juego.";
        $_SESSION['mensaje_tipo'] = "success";

        Redirect::to('/admin/sugeridas');
    }

    public function reportadas()
    {
        $this->checkAdmin();
        Log::info("AdminController::reportadas");

        $preguntas = $this->model->obtenerPreguntasPorEstado('reportada');
        $this->renderer->render("dashboard/adminReportadas", ['preguntas' => $preguntas]);
    }

    public function resolverReporte()
    {
        $this->checkAdmin();
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
        $this->checkAdmin();
        Log::info("AdminController::usuarios");

        $usuarios = $this->model->obtenerTodosUsuarios();
        foreach ($usuarios as &$user) {
            $user['es_rol_usuario'] = ($user['rol'] === 'usuario');
            $user['es_rol_admin'] = ($user['rol'] === 'admin');
            $user['es_rol_editor'] = ($user['rol'] === 'editor');
        }
        $this->renderer->render("dashboard/adminUsuarios", ['usuarios' => $usuarios]);
    }

    public function cambiarRol()
    {
        $this->checkAdmin();
        $id = (int)$this->request->post('usuario_id');
        $nuevo_rol = $this->request->post('rol');
        Log::info("AdminController::cambiarRol - ID: $id, Rol: $nuevo_rol");

        if (in_array($nuevo_rol, ['usuario', 'admin', 'editor'])) {
            $this->model->actualizarRolUsuario($id, $nuevo_rol);
            $_SESSION['mensaje'] = "El rol del usuario ha sido actualizado a: $nuevo_rol.";
            $_SESSION['mensaje_tipo'] = "success";
        }

        Redirect::to('/admin/usuarios');
    }

    public function eliminarUsuario()
    {
        $this->checkAdmin();
        $id = (int)$this->request->get('id');
        Log::info("AdminController::eliminarUsuario - ID: $id");

        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['mensaje'] = "No puedes eliminar tu propia cuenta de administrador.";
            $_SESSION['mensaje_tipo'] = "error";
        } else {
            $this->model->eliminarUsuario($id);
            $_SESSION['mensaje'] = "El usuario y todas sus partidas asociadas han sido eliminados.";
            $_SESSION['mensaje_tipo'] = "info";
        }

        Redirect::to('/admin/usuarios');
    }

    public function reportes()
    {
        $this->checkAdmin();
        Log::info("AdminController::reportes");

        $usuariosPorPais = $this->model->obtenerUsuariosPorPais();
        $usuariosPorSexo = $this->model->obtenerUsuariosPorSexo();
        $preguntasPorCategoria = $this->model->obtenerPreguntasPorCategoria();
        $partidasPorUsuario = $this->model->obtenerPartidasPorUsuario();

        $data = [
            'usuarios_por_pais' => $usuariosPorPais,
            'usuarios_por_sexo' => $usuariosPorSexo,
            'preguntas_por_categoria' => $preguntasPorCategoria,
            'partidas_por_usuario' => $partidasPorUsuario
        ];

        $this->renderer->render("dashboard/adminReportes", $data);
    }
}
