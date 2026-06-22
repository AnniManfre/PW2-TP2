<?php

require_once(__DIR__ . '/../vendor/mustache/src/Mustache/Autoloader.php');

class MustacheRenderer {

    private $mustache;

    public function __construct($viewsFolder) {
        Mustache_Autoloader::register();
        $this->mustache = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader($viewsFolder),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($viewsFolder),
        ]);
    }

    public function render($viewName, $data = []) {
        // Asegurar que la sesión está iniciada y mezclar datos de sesión
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $sessionData = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : [];
        // Los datos pasados explícitamente en $data tienen prioridad sobre los de sesión
        $context = array_merge($sessionData, $data);

        $template = $this->mustache->loadTemplate($viewName);
        echo $template->render($context);
    }
}

?>