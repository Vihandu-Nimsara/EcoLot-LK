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
        $viewFile = $rootPath . '/app/Views/' . $view . '.php';

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