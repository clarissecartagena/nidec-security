<?php

class Response {
    public function redirect(string $location, int $statusCode = 302): void {
        http_response_code($statusCode);
        header('Location: ' . $location);
        exit;
    }

    public function notFound(): void {
        http_response_code(404);
        require_once __DIR__ . '/../../app/controllers/NotFoundController.php';
        (new NotFoundController())->index();
        exit;
    }
}
