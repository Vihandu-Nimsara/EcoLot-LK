<?php

class Router
{
    private array $routes = [];


    public function get($path, $action)
    {
        $this->routes[$path] = $action;
    }


    public function dispatch($uri)
    {
        if(isset($this->routes[$uri]))
        {
            $action = $this->routes[$uri];

            [$controller, $method] = explode('@', $action);

            require_once "../app/Controllers/".$controller.".php";

            // Support both global controllers and namespaced controllers
            if (class_exists($controller)) {
                $controllerInstance = new $controller();
            } else {
                $namespacedClass = "\\App\\Controllers\\" . $controller;
                if (class_exists($namespacedClass)) {
                    $controllerInstance = new $namespacedClass();
                } else {
                    die("Controller class {$controller} not found.");
                }
            }

            $controllerInstance->$method();
        }
        else
        {
            echo "404 Page Not Found";
        }
    }
}