<?php
/**
 * Simple regex-based router. Patterns use {name} placeholders which match
 * one path segment and are passed to the handler as positional args.
 */

class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $pattern, string $handler): void
    {
        $this->routes['GET'][$pattern] = $handler;
    }

    public function post(string $pattern, string $handler): void
    {
        $this->routes['POST'][$pattern] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $path, $m)) {
                array_shift($m);
                [$class, $action] = explode('@', $handler);
                require_once BASE_PATH . '/app/controllers/' . $class . '.php';
                $controller = new $class();
                $controller->$action(...array_map('urldecode', $m));
                return;
            }
        }

        http_response_code(404);
        if (class_exists('Auth') && Auth::check()) {
            render('errors/404', ['title' => 'Not Found']);
        } else {
            echo '404 Not Found';
        }
    }
}
