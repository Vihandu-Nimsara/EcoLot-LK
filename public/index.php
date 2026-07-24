<?php

require_once "../app/Core/Controller.php";
require_once "../app/Core/Router.php";


$router = new Router();

require_once "../routes/web.php";


$uri = $_SERVER['REQUEST_URI'];

// Remove query parameters from the request path
$uri = parse_url($uri, PHP_URL_PATH);

// Dynamically determine project base folder to prevent hardcoded directory mismatch
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = str_replace('\\', '/', $basePath);
if ($basePath !== '/') {
    $basePath = rtrim($basePath, '/');
}

if ($basePath !== '' && $basePath !== '/') {
    $uri = str_replace($basePath, '', $uri);
} else {
    $uri = str_replace("/EcoLot-LK/public", "", $uri);
}

$router->dispatch($uri);