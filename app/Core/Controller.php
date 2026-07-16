<?php

class Controller
{
    public function view($view, $data = [])
    {
        extract($data);

        ob_start();

        require "../app/Views/" . $view . ".php";

        $content = ob_get_clean();


        require "../app/Views/municipal_officer/layouts/main.php";
    }
}