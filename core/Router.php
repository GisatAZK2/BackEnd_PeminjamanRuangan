<?php
class Router
{
    private static array $routes = [];

    public static function get(string $path, array $handler)
    {
        self::addRoute('GET', $path, $handler);
    }

    public static function post(string $path, array $handler)
    {
        self::addRoute('POST', $path, $handler);
    }
    public static function put(string $path, array $handler)
{
    self::addRoute('PUT', $path, $handler);
}

public static function delete(string $path, array $handler)
{
    self::addRoute('DELETE', $path, $handler);
}


    private static function addRoute(string $method, string $path, array $handler)
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => rtrim($path, '/'),
            'handler' => $handler
        ];
    }

    public static function dispatch(PDO $pdo)
{
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    $cleanUri = '/' . trim($uri, '/'); // normalize

    foreach (self::$routes as $route) {
        $routePath = '/' . trim($route['path'], '/'); // normalize juga
        if ($route['method'] === $method && $routePath === $cleanUri) {
            [$controllerClass, $methodName] = $route['handler'];
            $controllerFile = __DIR__ . '/../controllers/' . $controllerClass . '.php';

            if (!file_exists($controllerFile)) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Controller file not found"]);
                return;
            }

            require_once $controllerFile;
            $controller = new $controllerClass($pdo);

            if (!method_exists($controller, $methodName)) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Method $methodName not found"]);
                return;
            }

            return $controller->$methodName();
        }
    }

    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Route tidak ditemukan',
        'path' => $uri,
        'method' => $method
    ]);
}
}
