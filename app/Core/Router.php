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


            $controller = new $controller();


            $controller->$method();

        }
        else
        {
            echo "404 Page Not Found";
        }
    }
}