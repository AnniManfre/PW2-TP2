<?php

// Control de acceso centralizado: se llama una sola vez en index.php (antes del
// dispatch) en lugar de repetir el chequeo dentro de cada método del controlador.
class Auth
{
    // Rutas que se pueden ver sin estar logueado, por controlador => [métodos].
    private static $publicas = [
        'user' => [
            'home', 'login', 'procesarLogin',
            'registro', 'procesarRegistro',
            'validarCuenta', 'procesarValidacion',
            'publico', 'qr', 'logout',
        ],
        'partida' => ['abandonar'],
    ];

    // Panel al que va cada rol de staff.
    public static function panelDe($rol)
    {
        if ($rol === 'admin')  return '/admin/dashboard';
        if ($rol === 'editor') return '/editor/dashboard';
        return '/user/lobby';
    }

    // Decide el acceso según la ruta solicitada. Redirige y corta si no corresponde.
    public static function guard($controller, $method)
    {
        $rol = $_SESSION['rol'] ?? null; // null = no logueado

        // 1) Paneles de staff: cada uno exige su rol.
        if ($controller === 'admin')  { self::exigir($rol, 'admin');  return; }
        if ($controller === 'editor') { self::exigir($rol, 'editor'); return; }

        // 2) Rutas públicas: no requieren login.
        if (self::esPublica($controller, $method)) {
            return;
        }

        // 3) Resto (jugar, lobby, perfil, ranking...): requiere login.
        if (!$rol) {
            Redirect::to('/user/login');
        }

        // El staff no juega: se lo manda a su panel.
        if (in_array($rol, ['admin', 'editor'], true)) {
            Redirect::to(self::panelDe($rol));
        }
    }

    private static function esPublica($controller, $method)
    {
        return isset(self::$publicas[$controller])
            && in_array($method, self::$publicas[$controller], true);
    }

    // Exige un rol concreto; si no coincide, manda a donde corresponda según quién sea.
    private static function exigir($rol, $esperado)
    {
        if ($rol === $esperado) {
            return;
        }
        if (in_array($rol, ['admin', 'editor'], true)) {
            Redirect::to(self::panelDe($rol)); // otro staff: a su propio panel
        }
        if ($rol === 'usuario') {
            Redirect::to('/user/lobby');       // jugador logueado: a su lobby
        }
        Redirect::to('/user/login');           // no logueado
    }
}
