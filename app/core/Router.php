<?php

require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';

class Router {
    /** @var array<string, array<string, callable>> */
    private $routes = [];

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, callable $handler): void {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void {
        $this->map('POST', $path, $handler);
    }

    public function map(string $method, string $path, callable $handler): void {
        $method = strtoupper($method);
        $path = '/' . trim($path, '/');
        if ($path === '//') $path = '/';
        if (!isset($this->routes[$method])) $this->routes[$method] = [];
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void {
        $method = $this->request->method();
        $path = $this->request->path();

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            $this->response->notFound();
            return;
        }

        $handler($this->request, $this->response);
    }
}
