<?php

class LogoutController
{
    public function index(): void
    {
        auth_logout();
        header('Location: ' . app_url('login.php'));
        exit();
    }
}
