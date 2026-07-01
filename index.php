<?php
session_start();

require_once __DIR__ . '/helpers/Autoloader.php';

$config = new Configurator();
$router = $config->getRouter();

$controller = $_GET['controller'] ?? 'user';
$method     = $_GET['method'] ?? 'home';

Auth::guard($controller, $method);

$router->dispatch($controller, $method);