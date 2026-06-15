<?php

class UserModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrar($nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $email, $password, $usuario, $foto_perfil = null)
    {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (nombre_completo, año_nacimiento, sexo, pais, ciudad, email, password, usuario, foto_perfil, validado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE)";
        Log::info("SQL: $sql");
        return $this->database->execute($sql, [$nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $email, $password_hash, $usuario, $foto_perfil]);
    }

    public function loginPorCredenciales($usuario, $password)
    {
        $sql = "SELECT * FROM users WHERE usuario = ? AND validado = TRUE";
        Log::info("SQL: $sql [$usuario]");
        $filas = $this->database->query($sql, [$usuario]);
        
        if (!empty($filas)) {
            $user = $filas[0];
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return null;
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM users WHERE id = ?";
        Log::info("SQL: $sql [$id]");
        $filas = $this->database->query($sql, [$id]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function obtenerPorEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        Log::info("SQL: $sql [$email]");
        $filas = $this->database->query($sql, [$email]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function obtenerPorUsuario($usuario)
    {
        $sql = "SELECT * FROM users WHERE usuario = ?";
        Log::info("SQL: $sql [$usuario]");
        $filas = $this->database->query($sql, [$usuario]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function validarCuenta($email)
    {
        $sql = "UPDATE users SET validado = TRUE WHERE email = ?";
        Log::info("SQL: $sql [$email]");
        return $this->database->execute($sql, [$email]);
    }

    public function obtenerTodos()
    {
        $sql = "SELECT id, nombre_completo, usuario, puntaje FROM users ORDER BY puntaje DESC";
        Log::info("SQL: $sql");
        return $this->database->query($sql);
    }

    public function actualizarPerfil($id, $nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $foto_perfil = null)
    {
        $sql = "UPDATE users SET nombre_completo = ?, año_nacimiento = ?, sexo = ?, pais = ?, ciudad = ?, foto_perfil = ? WHERE id = ?";
        Log::info("SQL: $sql");
        return $this->database->execute($sql, [$nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $foto_perfil, $id]);
    }

    public function procesarEdicion()
    {
        Log::info("UserController::procesarEdicion");
        
        if (!isset($_SESSION['user_id'])) {
            Redirect::to('/PW2-TP2/user/login');
            return;
        }

        $id = $_SESSION['user_id'];
        
        // 1. Buscamos el usuario actual en la base de datos para recuperar su foto
        $usuarioActual = $this->model->obtenerPorId($id);
        $foto_perfil = $usuarioActual['foto_perfil']; // Guardamos la ruta de la foto que ya tiene

        // 2. Agarramos los datos nuevos del formulario
        $nombre_completo = $this->request->post('nombre_completo');
        $año_nacimiento = $this->request->post('año_nacimiento');
        $sexo = $this->request->post('sexo');
        $pais = $this->request->post('pais');
        $ciudad = $this->request->post('ciudad');

        // Convertir campos opcionales vacíos a NULL
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

        // 3. LLAMAMOS A TU MÉTODO pasando exactamente todos los argumentos que pide
        $this->model->actualizarPerfil($id, $nombre_completo, $año_nacimiento, $sexo, $pais, $ciudad, $foto_perfil);

        // Actualizamos la sesión por las dudas
        $_SESSION['nombre_completo'] = $nombre_completo;

        Log::info("UserController::procesarEdicion - Perfil actualizado con éxito para ID: $id");

        // Volvemos a la vista del perfil
        Redirect::to('/PW2-TP2/user/perfil');
    }
}

