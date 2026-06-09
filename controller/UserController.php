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

        // Validaciones básicas
        if (empty($nombre_completo) || empty($email) || empty($usuario) || empty($password)) {
            Log::warning("UserController::procesarRegistro - Campos vacíos");
            $this->renderer->render("registroView", ['error' => 'Los campos obligatorios no pueden estar vacíos']);
            return;
        }

        if ($password !== $password_repeat) {
            Log::warning("UserController::procesarRegistro - Las contrasenias no coinciden");
            $this->renderer->render("registroView", ['error' => 'Las contrasenias no coinciden']);
            return;
        }

        // Validación de complejidad de contrasenia
        $passwordError = $this->validarContrasenia($password);
        if ($passwordError) {
            Log::warning("UserController::procesarRegistro - Contrasenia débil: $passwordError");
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
        $this->model->registrar($nombre_completo, $anio_nacimiento, $sexo, $pais, $ciudad, $email, $password, $usuario, null);

        // Simular validación de email
        $this->model->validarCuenta($email);

        Log::info("UserController::procesarRegistro - Usuario registrado: $usuario");

        // Redirigir a login con mensaje
        $_SESSION['success'] = "¡Cuenta creada exitosamente! Por favor, inicia sesión.";
        Redirect::to('/user/login');
    }

    /**
     * Valida la complejidad de la contrasenia
     * Requisitos:
     * - Mínimo 8 caracteres
     * - Al menos 1 letra mayúscula
     * - Al menos 1 número
     *
     * @param string $password
     * @return string|null Mensaje de error si falla, null si es válida
     */
    private function validarContrasenia($password)
    {
        // Verificar longitud mínima
        if (strlen($password) < 8) {
            return 'La contrasenia debe tener al menos 8 caracteres';
        }

        // Verificar que tenga al menos una mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            return 'La contrasenia debe contener al menos una letra mayúscula (A-Z)';
        }

        // Verificar que tenga al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            return 'La contrasenia debe contener al menos un número (0-9)';
        }

        return null; // Contrasenia válida
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
            $this->renderer->render("loginView", ['error' => 'Usuario y contrasenia son requeridos']);
            return;
        }

        $user = $this->model->loginPorCredenciales($usuario, $password);

        if ($user) {
            Log::info("UserController::procesarLogin - Login exitoso: $usuario");
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            Redirect::to('/user/lobby');
        } else {
            Log::warning("UserController::procesarLogin - Credenciales inválidas: $usuario");
            $this->renderer->render("loginView", ['error' => 'Usuario o contrasenia inválidos']);
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
        $this->renderer->render("perfilView", $user);
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

        $user = $this->model->obtenerPorId($_SESSION['user_id']);
        $ranking = $this->model->obtenerTodos();
        $this->renderer->render("lobbyView", ['user' => $user, 'ranking' => $ranking]);
    }
}

