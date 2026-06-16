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
            Redirect::to('/PW2-TP2/user/lobby');
            return;
        }
        $this->renderer->render("homeView");
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
        $año_nacimiento = $this->request->post('año_nacimiento');
        $sexo = $this->request->post('sexo') ?? 'Prefiero no decirlo';
        $pais = $this->request->post('pais');
        $ciudad = $this->request->post('ciudad');
        $email = $this->request->post('email');
        $password = $this->request->post('password');
        $password_repeat = $this->request->post('password_repeat');
        $usuario = $this->request->post('usuario');

        // Convertir campos opcionales vacíos a NULL
        $año_nacimiento = !empty($año_nacimiento) ? (int)$año_nacimiento : null;
        $pais = !empty($pais) ? $pais : null;
        $ciudad = !empty($ciudad) ? $ciudad : null;

        // Validaciones básicas
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

        // Validación de complejidad de contraseña
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

        // Registrar usuario (sin foto)
        $this->model->registrar($nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $email, $password, $usuario, null);

        // GENERAMOS EL TOKEN REAL DE 6 DÍGITOS
        $token = rand(100000, 999999);
        
        // Guardamos el token en la base de datos y seteamos cuenta_validada en 0
        $this->model->guardarTokenValidacion($email, $token);

        Log::info("UserController::procesarRegistro - Usuario registrado: $usuario. Token generado: $token");

        // Guardamos los datos temporalmente en la sesión para la pantalla de validación
        $_SESSION['email_en_validacion'] = $email;
        $_SESSION['token_creado'] = $token; // Guardado para mostrarlo "chiquito" en la vista

        // Redirigir a login con mensaje
        $_SESSION['success'] = "¡Cuenta creada exitosamente! Por favor, inicia sesión.";
        Redirect::to('/PW2-TP2/user/login');
    }

    private function validarContraseña($password)
    {
        // Verificar longitud mínima
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres';
        }

        // Verificar que tenga al menos una mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            return 'La contraseña debe contener al menos una letra mayúscula (A-Z)';
        }

        // Verificar que tenga al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            return 'La contraseña debe contener al menos un número (0-9)';
        }

        return null; // Contraseña válida
    }

    public function validarCuenta()
    {
        Log::info("UserController::validarCuenta (form)");

        if (!isset($_SESSION['email_en_validacion'])) {
            Redirect::to('/PW2-TP2/user/registro');
            return;
        }

        // Le pasamos el correo y el token de ayuda a la vista
        $data = [
            'email' => $_SESSION['email_en_validacion'],
            'token_ayuda' => $_SESSION['token_creado'] ?? null
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
            Redirect::to('/PW2-TP2/user/login');
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
        $this->renderer->render("loginView");
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

        $user = $this->model->loginPorCredenciales($usuario, $password);

        if ($user) {

            if (isset($user['cuenta_validada']) && $user['cuenta_validada'] == 0) {
                Log::warning("UserController::procesarLogin - Intento de ingreso sin validar: $usuario");
                // Guardamos los datos en la sesión temporal para la vista de validación
                $_SESSION['email_en_validacion'] = $user['email'];
                $_SESSION['token_creado'] = $user['token']; 
                Redirect::to('/PW2-TP2/user/validarCuenta');
                // vuelta al login mostrando el mensaje de error
                $this->renderer->render("loginView", ['error' => 'Tu cuenta aún no está validada. Por favor, ingresá el código de verificación.']);
                return; // Corta la ejecución acá para que no cree la sesión de juego
            }

            Log::info("UserController::procesarLogin - Login exitoso: $usuario");
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            Redirect::to('/PW2-TP2/user/perfil');
        } else {
            Log::warning("UserController::procesarLogin - Credenciales inválidas: $usuario");
            $this->renderer->render("loginView", ['error' => 'Usuario o contraseña inválidos']);
        }
    }

    public function perfil()
    {
        Log::info("UserController::perfil");
        
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/PW2-TP2/user/login');
            return;
        }

        $user = $this->model->obtenerPorId($_SESSION['user_id']);
        $user['partidas_ganadas'] = $this->model->contarPartidasGanadas($_SESSION['user_id'], PartidaController::TOTAL_PREGUNTAS);
        $this->renderer->render("perfilView", $user);
    }
    public function editarPerfil()
    {
        Log::info("UserController::editarPerfil");
        
        // Verificamos que esté logueado
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/PW2-TP2/user/login');
            return;
        }

        // Buscamos los datos actuales para rellenar el formulario
        $user = $this->model->obtenerPorId($_SESSION['user_id']);
        $this->renderer->render("editarPerfilView", $user);
    }

    public function procesarEdicion()
    {
        Log::info("UserController::procesarEdicion");
        
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/PW2-TP2/user/login');
            return;
        }

        $id = $_SESSION['user_id'];
        
        // Agarramos los datos nuevos del formulario
        $nombre_completo = $this->request->post('nombre_completo');
        $año_nacimiento = $this->request->post('año_nacimiento');
        $sexo = $this->request->post('sexo');
        $pais = $this->request->post('pais');
        $ciudad = $this->request->post('ciudad');

        // Convertir campos opcionales vacíos a NULL (igual que en tu registro)
        $año_nacimiento = !empty($año_nacimiento) ? (int)$año_nacimiento : null;
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

    
        $this->model->actualizarPerfil($id, $nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad);

        // Actualizamos el nombre en la sesión 
        $_SESSION['nombre_completo'] = $nombre_completo;

        Log::info("UserController::procesarEdicion - Perfil actualizado para ID: $id");

        // Volvemos al perfil
        Redirect::to('/PW2-TP2/user/perfil');
    }

    public function logout()
    {
        Log::info("UserController::logout");
        session_destroy();
        Redirect::to('/PW2-TP2/user/home');
    }

    public function lobby()
    {
        Log::info("UserController::lobby");
        
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/PW2-TP2/user/login');
            return;
        }

        $datosVista = $this->model->obtenerPorId($_SESSION['user_id']);

        $datosVista['user'] = $datosVista;

        $datosVista['ranking'] = $this->model->obtenerTodos();

        if (isset($_SESSION["mensaje"])) {
            $datosVista['mensaje'] = $_SESSION["mensaje"];
            $datosVista['mensaje_clase'] = (($_SESSION["mensaje_tipo"] ?? "info") === "error") ? "error" : "success";
            unset($_SESSION["mensaje"], $_SESSION["mensaje_tipo"]);
        }

        $this->renderer->render("lobbyView", $datosVista);
    }
}

