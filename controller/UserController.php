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

        // Simular validación de email
        $this->model->validarCuenta($email);

        Log::info("UserController::procesarRegistro - Usuario registrado: $usuario");

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

        // Mandamos a actualizar al modelo
        $this->model->actualizarPerfil($id, $nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad);

        // Actualizamos el nombre en la sesión por si lo usás en el header
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

        $user = $this->model->obtenerPorId($_SESSION['user_id']);
        $ranking = $this->model->obtenerTodos();
        $this->renderer->render("lobbyView", ['user' => $user, 'ranking' => $ranking]);
    }
}

