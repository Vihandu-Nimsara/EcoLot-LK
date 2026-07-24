<?php

class Controller
{
    public function view($view, $data = [], $layout = null)
    {
        extract($data);

        ob_start();

        require "../app/Views/" . $view . ".php";

        $content = ob_get_clean();

        // Dynamically select layout based on view prefix if not explicitly passed
        if ($layout === null) {
            if (strpos($view, 'public_user/') === 0) {
                $layout = 'public_user/layouts/main';
            } else {
                $layout = 'municipal_officer/layouts/main';
            }
        }

        require "../app/Views/" . $layout . ".php";
    }
}