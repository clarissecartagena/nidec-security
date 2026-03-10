<?php

class LogoutController
{
    public function index(): void
    {
        auth_logout();
        header('Location: /login.php');
        exit();
    }
}
