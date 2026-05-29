<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/helpers/Autoloader.php';

// Parsear la URL para extraer controller y method
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$parts = explode('/', $url);

$controller = isset($parts[0]) && !empty($parts[0]) ? $parts[0] : '';
$method = isset($parts[1]) && !empty($parts[1]) ? $parts[1] : '';

$config = new Configurator();
$router = $config->getRouter();

$router->dispatch($controller, $method);
