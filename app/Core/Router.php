<?php
declare(strict_types=1);

class Router
{
    private array $routes = [];
    private string $basePath;
    private string $groupPrefix = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = $this->normalizePath($basePath);

        if ($this->basePath === '/') {
            $this->basePath = '';
        }
    }

    public function get(string $path, callable|string $action): self
    {
        return $this->add('GET', $path, $action);
    }

    public function post(string $path, callable|string $action): self
    {
        return $this->add('POST', $path, $action);
    }

    public function add(string $method, string $path, callable|string $action): self
    {
        $path = $this->normalizePath($this->groupPrefix . '/' . ltrim($path, '/'));

        $this->routes[strtoupper($method)][] = [
            'path' => $path,
            'action' => $action,
            'pattern' => $this->compilePattern($path),
        ];

        return $this;
    }

    public function group(string $prefix, callable $registerRoutes): void
    {
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix = $this->normalizePath($previousPrefix . '/' . ltrim($prefix, '/'));

        try {
            $registerRoutes($this);
        } finally {
            $this->groupPrefix = $previousPrefix;
        }
    }

    public function dispatch(?string $requestMethod = null, ?string $requestUri = null): void
    {
        $method = strtoupper($requestMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = $requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $path = $this->requestPath($uri);

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $parameters = array_filter(
                $matches,
                static fn (string|int $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            $this->runAction($route['action'], $parameters);
            return;
        }

        foreach ($this->routes as $registeredMethod => $routes) {
            if ($registeredMethod === $method) {
                continue;
            }

            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $path)) {
                    http_response_code(405);
                    echo '405 Method Not Allowed';
                    return;
                }
            }
        }

        http_response_code(404);
        echo '404 Page Not Found';
    }

    private function runAction(callable|string $action, array $parameters): void
    {
        if (is_callable($action)) {
            $action(...array_values($parameters));
            return;
        }

        if (!str_contains($action, '@')) {
            throw new RuntimeException("Invalid route action [{$action}].");
        }

        [$controllerClass, $method] = explode('@', $action, 2);

        if (!class_exists($controllerClass)) {
            throw new RuntimeException("Controller [{$controllerClass}] was not found.");
        }

        $controller = new $controllerClass();

        if (!is_callable([$controller, $method])) {
            throw new RuntimeException(
                "Controller action [{$controllerClass}@{$method}] was not found."
            );
        }

        $controller->{$method}(...array_values($parameters));
    }

    private function requestPath(string $uri): string
    {
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');

        if (
            $this->basePath !== ''
            && ($path === $this->basePath || str_starts_with($path, $this->basePath . '/'))
        ) {
            $path = substr($path, strlen($this->basePath)) ?: '/';
        }

        return $this->normalizePath(rawurldecode($path));
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    private function compilePattern(string $path): string
    {
        $pattern = preg_replace_callback(
            '/\\\\\{([A-Za-z_][A-Za-z0-9_]*)\\\\\}/',
            static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            preg_quote($path, '#')
        );

        return '#^' . $pattern . '$#';
    }
}
