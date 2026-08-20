<?php
declare(strict_types=1);

class Controller
{
    public function view(string $view, array $data = [], ?string $layout = null): void
    {
        $rootPath = defined('APP_ROOT')
            ? APP_ROOT
            : dirname(__DIR__, 2);

        $view = trim($view, '/');

        if (!preg_match('#^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$#', $view)) {
            throw new InvalidArgumentException("Invalid view path [{$view}].");
        }

        $viewFile = $rootPath . '/app/Views/' . $view . '.php';
        $appConfig = require $rootPath . '/config/app.php';
        $basePath = rtrim((string) ($appConfig['base_path'] ?? ''), '/');
        $appName = (string) ($appConfig['name'] ?? 'Application');

        if (!is_file($viewFile)) {
            throw new RuntimeException("View [{$view}] was not found.");
        }

        extract($data, EXTR_SKIP);

        ob_start();

        try {
            require $viewFile;
            $content = (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $layout ??= $this->resolveLayout($view, $rootPath);

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = $rootPath . '/app/Views/' . trim($layout, '/') . '.php';

        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout [{$layout}] was not found.");
        }

        ob_start();

        try {
            require $layoutFile;
            $page = (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        echo trim($page) === '' ? $content : $page;
    }

    protected function postString(string $field, bool $trim = true): string
    {
        $value = $_POST[$field] ?? '';

        if (!is_string($value)) {
            return '';
        }

        return $trim ? trim($value) : $value;
    }

    protected function hasValidCsrfToken(): bool
    {
        $token = $_POST['_csrf_token'] ?? null;

        return is_string($token) && Csrf::validate($token);
    }

    protected function redirect(string $path, int $status = 303): never
    {
        $rootPath = defined('APP_ROOT')
            ? APP_ROOT
            : dirname(__DIR__, 2);
        $config = require $rootPath . '/config/app.php';
        $basePath = rtrim((string) ($config['base_path'] ?? ''), '/');

        header(
            'Location: ' . $basePath . '/' . ltrim($path, '/'),
            true,
            $status
        );
        exit;
    }

    private function resolveLayout(string $view, string $rootPath): ?string
    {
        $segments = explode('/', $view);
        $roleDirectory = $segments[0] ?? '';

        if ($roleDirectory === '' || in_array($roleDirectory, ['auth', 'common', 'components'], true)) {
            return null;
        }

        $layout = $roleDirectory . '/layouts/main';
        $layoutFile = $rootPath . '/app/Views/' . $layout . '.php';

        return is_file($layoutFile) ? $layout : null;
    }
}
