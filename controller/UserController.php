<?php

class UserController
{
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model    = $model;
        $this->renderer = $renderer;
        $this->request  = $request;
    }

    public function home()
    {
        Log::info("UserController::home");
        //si el usuario ya tiene la sesión iniciada
        if (isset($_SESSION['user_id'])) {
            Redirect::to('/user/lobby');
            return;
        }
        $this->renderer->render("homeView", ['banner' => true]);
    }

    public function registro()
    {
        Log::info("UserController::registro (form)");
        $this->renderer->render("registroView");
    }

    public function procesarRegistro()
    {
        Log::info("UserController::procesarRegistro");

        $nombre_completo = $this->request->post('nombre_completo');
        $anio_nacimiento = $this->request->post('anio_nacimiento');
        $sexo = $this->request->post('sexo') ?? 'Prefiero no decirlo';
        $pais = $this->request->post('pais');
        $ciudad = $this->request->post('ciudad');
        $email = $this->request->post('email');
        $password = $this->request->post('password');
        $password_repeat = $this->request->post('password_repeat');
        $usuario = $this->request->post('usuario');

        // Convertir campos opcionales vacíos a NULL
        $anio_nacimiento = !empty($anio_nacimiento) ? (int)$anio_nacimiento : null;
        $pais = !empty($pais) ? $pais : null;
        $ciudad = !empty($ciudad) ? $ciudad : null;

        // Validaciones 
        if (empty($nombre_completo) || empty($email) || empty($usuario) || empty($password)) {
            Log::warning("UserController::procesarRegistro - Campos vacíos");
            $this->renderer->render("registroView", ['error' => 'Los campos obligatorios no pueden estar vacíos']);
            return;
        }

        if ($password !== $password_repeat) {
            Log::warning("UserController::procesarRegistro - Las contraseñas no coinciden");
            $this->renderer->render("registroView", ['error' => 'Las contraseñas no coinciden']);
            return;
        }

        // Validación de contraseña
        $passwordError = $this->validarContraseña($password);
        if ($passwordError) {
            Log::warning("UserController::procesarRegistro - Contraseña débil: $passwordError");
            $this->renderer->render("registroView", ['error' => $passwordError]);
            return;
        }

        // Verificar si el usuario o email ya existen
        if ($this->model->obtenerPorEmail($email)) {
            Log::warning("UserController::procesarRegistro - Email ya existe: $email");
            $this->renderer->render("registroView", ['error' => 'El email ya está registrado']);
            return;
        }

        if ($this->model->obtenerPorUsuario($usuario)) {
            Log::warning("UserController::procesarRegistro - Usuario ya existe: $usuario");
            $this->renderer->render("registroView", ['error' => 'El nombre de usuario ya está registrado']);
            return;
        }

        // Registrar usuario 
        $this->model->registrar($nombre_completo, $anio_nacimiento, $sexo, $pais, $ciudad, $email, $password, $usuario, null);

        
        $token = rand(100000, 999999);
        
        // Guardamos el token en la base de datos y seteamos cuenta_validada en 0
        $this->model->guardarTokenValidacion($email, $token);

        Log::info("UserController::procesarRegistro - Usuario registrado: $usuario. Token generado: $token");

        $mailer = new Mailer();
        $correoEnviado = $mailer->enviarTokenActivacion($email, $nombre_completo, $token);

        if ($correoEnviado) {
            $_SESSION['email_en_validacion'] = $email;
            $_SESSION['success'] = "¡Cuenta creada! Te enviamos un código de verificación de 6 dígitos a tu correo.";
            Redirect::to('/user/validarCuenta'); 
            exit;
        } else {
            $_SESSION['error'] = "Hubo un problema al enviarte el correo de activación. Por favor, contactá a soporte.";
            Redirect::to('/user/registro');
            exit;
        }
        
    }

    private function validarContraseña($password)
    {
       
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres';
        }

        
        if (!preg_match('/[A-Z]/', $password)) {
            return 'La contraseña debe contener al menos una letra mayúscula (A-Z)';
        }

       
        if (!preg_match('/[0-9]/', $password)) {
            return 'La contraseña debe contener al menos un número (0-9)';
        }

        return null; 
    }
public function validarCuenta() {
  
        Log::info("UserController::validarCuenta (form)");

        $data = [
            'email' => $_SESSION['email_en_validacion'] ?? 'Email no guardado en sesión',
        ];

        $this->renderer->render("validarCuentaView", $data);
    }
    

    public function procesarValidacion()
    {
        Log::info("UserController::procesarValidacion");

        $token_ingresado = $this->request->post('token');
        $email = $_SESSION['email_en_validacion'] ?? null;

        if (empty($token_ingresado) || empty($email)) {
            $this->renderer->render("validarCuentaView", [
                'error' => 'El código es requerido',
                'token_ayuda' => $_SESSION['token_creado'] ?? null
            ]);
            return;
        }

        // Consultamos al modelo si coincide el correo con el token
        $esValido = $this->model->verificarToken($email, $token_ingresado);

        if ($esValido) {
            Log::info("UserController::procesarValidacion - Token correcto para: $email");

            // Activamos la cuenta cambiando el estado a 1
            $this->model->activarCuenta($email);

            // Limpiamos las variables temporales de la sesión
            unset($_SESSION['email_en_validacion']);
            unset($_SESSION['token_creado']);

            $_SESSION['success'] = "¡Cuenta validada exitosamente! Ya podés iniciar sesión.";
            Redirect::to('/user/login');
        } else {
            Log::warning("UserController::procesarValidacion - Token incorrecto para: $email");
            $this->renderer->render("validarCuentaView", [
                'error' => 'El código ingresado es incorrecto',
                'email' => $email,
                'token_ayuda' => $_SESSION['token_creado'] ?? null
            ]);
        }
    }

    public function login()
    {
        Log::info("UserController::login (form)");

        // Mostrar el aviso que dejó otra acción (ej. "Cuenta validada exitosamente")
        // y limpiarlo para que no se repita al recargar.
        $data = [];
        if (isset($_SESSION['success'])) {
            $data['success'] = $_SESSION['success'];
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        $this->renderer->render("loginView", $data);
    }

    public function procesarLogin()
    {
        Log::info("UserController::procesarLogin");

        $usuario = $this->request->post('usuario');
        $password = $this->request->post('password');

        if (empty($usuario) || empty($password)) {
            Log::warning("UserController::procesarLogin - Campos vacíos");
            $this->renderer->render("loginView", ['error' => 'Usuario y contraseña son requeridos']);
            return;
        }

        // Cuentas de staff con credenciales fijas (no existen en la BD).
        // Entran directo a su panel y no pueden jugar.
        $staff = [
            'admin'  => ['pass' => 'Admin1234',  'rol' => 'admin',  'destino' => '/admin/dashboard'],
            'editor' => ['pass' => 'Editor1234', 'rol' => 'editor', 'destino' => '/editor/dashboard'],
        ];
        if (isset($staff[$usuario]) && $password === $staff[$usuario]['pass']) {
            Log::info("UserController::procesarLogin - Login de staff: $usuario");
            session_regenerate_id(true);
            $_SESSION['user_id'] = 0;            // centinela: no es un usuario real de la BD
            $_SESSION['usuario'] = $usuario;
            $_SESSION['rol']     = $staff[$usuario]['rol'];
            Redirect::to($staff[$usuario]['destino']);
            exit;
        }

        $user = $this->model->loginPorCredenciales($usuario, $password);

        if ($user) {

            // Para entrar hay que estar validado (cuenta_validada == 1). Cualquier
            // otro valor (0, NULL o ausente) se considera NO validada. Usamos empty()
            // porque isset() daría false con NULL y dejaría pasar la cuenta sin validar.
            if (empty($user['cuenta_validada'])) {
                Log::warning("UserController::procesarLogin - Intento de ingreso sin validar: $usuario");
                
                $_SESSION['email_en_validacion'] = $user['email'];
                $_SESSION['token_creado'] = $user['token']; 
                Redirect::to('/user/validarCuenta');
            
                exit; 
            }

            Log::info("UserController::procesarLogin - Login exitoso: $usuario");

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            // Los usuarios de la BD son siempre jugadores; admin/editor solo
            // existen vía las credenciales fijas de staff.
            $_SESSION['rol'] = 'usuario';

            Redirect::to('/user/perfil');
        } else {
            Log::warning("UserController::procesarLogin - Credenciales inválidas: $usuario");
            $this->renderer->render("loginView", ['error' => 'Usuario o contraseña inválidos']);
        }
    }

    public function perfil()
    {
        Log::info("UserController::perfil");

        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        $user = $this->model->obtenerPorId($_SESSION['user_id']);
        $user['partidas_ganadas'] = $this->model->contarPartidasGanadas($_SESSION['user_id'], PartidaController::TOTAL_PREGUNTAS);
        $user['efectividad']      = $this->model->getEfectividad($_SESSION['user_id'], PartidaController::TOTAL_PREGUNTAS);
        $user['racha_actual']     = $this->model->obtenerRacha($_SESSION['user_id'], PartidaController::TOTAL_PREGUNTAS);

        $puntaje = $user['puntaje'] ?? 0;
        if ($puntaje < 20)      $nivelNum = 1;
        elseif ($puntaje < 40)  $nivelNum = 2;
        else                    $nivelNum = 3;

        $user['nivel']     = 'Nivel ' . $nivelNum;
        $user['categoria'] = PartidaController::NIVELES[$nivelNum];
        $user['page_title'] = 'Mi Perfil';

        $this->renderer->render("perfilView", $user);
    }
    public function editarPerfil()
    {
        Log::info("UserController::editarPerfil");

        // Verificamos que esté logueado
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        // Buscamos los datos actuales para rellenar el formulario
        $user = $this->model->obtenerPorId($_SESSION['user_id']);
        $user['page_title'] = 'Editar Perfil';
        $this->renderer->render("editarPerfilView", $user);
    }

    public function procesarEdicion()
    {
        Log::info("UserController::procesarEdicion");

        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        $id = $_SESSION['user_id'];

        // Recuperamos la foto que ya tiene el usuario para no borrarla al editar.
        $usuarioActual = $this->model->obtenerPorId($id);
        $foto_perfil = $usuarioActual['foto_perfil'];

        // Agarramos los datos nuevos del formulario
        $nombre_completo = $this->request->post('nombre_completo');
        $anio_nacimiento = $this->request->post('anio_nacimiento');
        $sexo = $this->request->post('sexo');
        $pais = $this->request->post('pais');
        $ciudad = $this->request->post('ciudad');

        // Convertir campos opcionales vacíos a NULL (igual que en tu registro)
        $anio_nacimiento = !empty($anio_nacimiento) ? (int)$anio_nacimiento : null;
        $pais = !empty($pais) ? $pais : null;
        $ciudad = !empty($ciudad) ? $ciudad : null;

        // Validación básica
        if (empty($nombre_completo)) {
            Log::warning("UserController::procesarEdicion - Nombre vacío");
            $user = $this->model->obtenerPorId($id);
            $user['error'] = 'El nombre completo es obligatorio';
            $this->renderer->render("editarPerfilView", $user);
            return;
        }


        $this->model->actualizarPerfil($id, $nombre_completo, $anio_nacimiento, $sexo, $pais, $ciudad, $foto_perfil);

        // Actualizamos el nombre en la sesión 
        $_SESSION['nombre_completo'] = $nombre_completo;

        Log::info("UserController::procesarEdicion - Perfil actualizado para ID: $id");

        // Volvemos al perfil
        Redirect::to('/user/perfil');
    }

    public function logout()
    {
        Log::info("UserController::logout");
        session_destroy();
        Redirect::to('/user/home');
    }

    public function lobby()
    {
        Log::info("UserController::lobby");

        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        $datosVista = $this->model->obtenerPorId($_SESSION['user_id']);
        $datosVista["esAdmin"] = ($_SESSION["rol"] === "admin");

        $datosVista['user'] = $datosVista;

        // Solo las últimas 5 en el lobby; el resto se ve en /user/historial.
        $datosVista['ranking'] = $this->model->obtenerPartidasRecientes($_SESSION['user_id'], 5);

        $datosVista['banner'] = true;

        if (isset($_SESSION["mensaje"])) {
            $datosVista['mensaje'] = $_SESSION["mensaje"];
            $datosVista['mensaje_clase'] = (($_SESSION["mensaje_tipo"] ?? "info") === "error") ? "error" : "success";
            unset($_SESSION["mensaje"], $_SESSION["mensaje_tipo"]);
        }

        $this->renderer->render("lobbyView", $datosVista);
    }

    // Historial completo de partidas del usuario (la página del "Ver todo" del lobby).
    public function historial()
    {
        Log::info("UserController::historial");

        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        $usuarioActual = $this->model->obtenerPorId($_SESSION['user_id']);

        $this->renderer->render("historialView", [
            // Misma clave 'ranking' para reutilizar la tabla del lobby.
            'ranking'     => $this->model->obtenerPartidasRecientes($_SESSION['user_id']),
            'usuario'     => $usuarioActual['usuario'],
            'foto_perfil' => $usuarioActual['foto_perfil'] ?? null,
            'page_title'  => 'Historial de Partidas',
        ]);
    }

    // Genera y devuelve la imagen QR (PNG) que apunta a la vista pública del usuario
    public function qr()
    {
        Log::info("UserController::qr");

        $id = $this->request->get('id', $_SESSION['user_id'] ?? null);
        if ($id === null) {
            return;
        }

        require_once __DIR__ . '/../phpqrcode/qrlib.php';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url  = "http://" . $host . "/user/publico?id=" . urlencode($id);

        // Limpiamos cualquier salida previa para no corromper el PNG
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        QRcode::png($url, false, QR_ECLEVEL_M, 6, 2);
        exit;
    }

    // Vista pública (la que abre el QR)
    public function publico()
    {
        Log::info("UserController::publico");

        $id = $this->request->get('id');
        $usuario = $id !== null ? $this->model->obtenerPorId($id) : null;

        if ($usuario === null) {
            $this->renderer->render("perfilPublicoView", ['no_encontrado' => true]);
            return;
        }

        $puntaje = $usuario['puntaje'] ?? 0;
        if ($puntaje < 20)      $nivelNum = 1;
        elseif ($puntaje < 40)  $nivelNum = 2;
        else                    $nivelNum = 3;

        $datos = [
            'nombre_completo'  => $usuario['nombre_completo'],
            'usuario'          => $usuario['usuario'],
            'pais'             => $usuario['pais'],
            'ciudad'           => $usuario['ciudad'],
            'foto_perfil'      => $usuario['foto_perfil'] ?? null,
            'puntaje'          => $puntaje,
            'nivel'            => 'Nivel ' . $nivelNum,
            'categoria'        => PartidaController::NIVELES[$nivelNum],
            'partidas_ganadas' => $this->model->contarPartidasGanadas($usuario['id'], PartidaController::TOTAL_PREGUNTAS),
        ];

        $this->renderer->render("perfilPublicoView", $datos);
    }

    // Ranking global: todos los usuarios ordenados por tokens acumulados
    public function ranking()
    {
        Log::info("UserController::ranking");

        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/user/login');
            return;
        }

        // obtenerTodos() ya devuelve los usuarios ordenados por puntaje DESC
        $usuarios = $this->model->obtenerTodos();

        $ranking = [];
        $posicion = 0;
        foreach ($usuarios as $u) {
            $posicion++;
            $ranking[] = [
                'posicion'        => $posicion,
                'usuario'         => $u['usuario'],
                'nombre_completo' => $u['nombre_completo'],
                'puntaje'         => $u['puntaje'],
                'es_actual'       => ($u['id'] == $_SESSION['user_id']),
                'top1'            => $posicion === 1,
                'top2'            => $posicion === 2,
                'top3'            => $posicion === 3,
            ];
        }

        $usuarioActual = $this->model->obtenerPorId($_SESSION['user_id']);

        $this->renderer->render("rankingView", [
            'ranking'     => $ranking,
            'usuario'     => $usuarioActual['usuario'],
            'foto_perfil' => $usuarioActual['foto_perfil'] ?? null,
            'page_title'  => 'Ranking Global',
        ]);
    }
}
