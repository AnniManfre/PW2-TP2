<?php

class Configurator {

    private $config;

    public function __construct() {
        $this->config = parse_ini_file("config/config.ini");
    }

    private function getUserModel() {
        return new UserModel($this->getDatabase());
    }

    private function getRenderer() {
        return new MustacheRenderer(__DIR__ . '/../view');
    }

    public function getUserController() {
        return new UserController($this->getUserModel(), $this->getRenderer(), new Request());
    }

    private function getPartidaModel() {
        return new PartidaModel($this->getDatabase());
    }

    public function getPartidaController() {
        return new PartidaController($this->getPartidaModel(), $this->getRenderer(), new Request());
    }

    private function getAdminModel() {
        return new AdminModel($this->getDatabase());
    }

    public function getAdminController() {
        return new AdminController($this->getAdminModel(), $this->getRenderer(), new Request());
    }

    private function getDatabase() {
        return new MyDatabase($this->config['hostname'],
                              $this->config['username'],
                              $this->config['password'],
                              $this->config['database'],
                              $this->config['port'] ?? null
        );
    }

    public function getRouter() {
        return new Router($this, 'user', 'home');
    }

    public function getOrDefault($controllerName, $defaultControllerName) {
        $getter = 'get' . ucfirst($controllerName) . 'Controller';
        if (method_exists($this, $getter) == true) {
            return $this->{$getter}();
        }

        $defaultGetter = 'get' . ucfirst($defaultControllerName) . 'Controller';
        return $this->{$defaultGetter}();
    }
}

?>