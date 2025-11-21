<?php
class Router
{
    private static array $routes = [];
    private static ?PDO $pdo = null;
    private static mixed $cache = null;

    /**
     * Inisialisasi global dependency (PDO dan cache)
     */
    public static function init(PDO $pdo, $cache)
    {
        self::$pdo = $pdo;
        self::$cache = $cache;
    }

    public static function get(string $path, array|callable $handler)
    {
        self::addRoute('GET', $path, $handler);
    }

    public static function post(string $path, array|callable $handler)
    {
        self::addRoute('POST', $path, $handler);
    }

    public static function put(string $path, array|callable $handler)
    {
        self::addRoute('PUT', $path, $handler);
    }

    public static function delete(string $path, array|callable $handler)
    {
        self::addRoute('DELETE', $path, $handler);
    }

    private static function addRoute(string $method, string $path, array|callable $handler)
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => rtrim($path, '/'),
            'handler' => $handler
        ];
    }

    public static function view(string $path, string $viewName)
{
    self::$routes[] = [
        'method' => 'GET',
        'path' => rtrim($path, '/'),
        'handler' => function () use ($viewName) {
            $file = __DIR__ . '/../views/' . $viewName . '.php';

            if (!file_exists($file)) {
                http_response_code(404);
                echo "View {$viewName} tidak ditemukan.";
                return;
            }

            include $file;
        }
    ];
}


    public static function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $cleanUri = '/' . trim($uri, '/'); // normalisasi path

        foreach (self::$routes as $route) {
            $routePath = '/' . trim($route['path'], '/');
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $routePath);
            $pattern = str_replace('/', '\/', $pattern);
            $pattern = '/^' . $pattern . '$/';

            if ($route['method'] === $method && preg_match($pattern, $cleanUri, $matches)) {
                array_shift($matches); // hapus full match

                // Jika handler berupa Closure (callable)
                if (is_callable($route['handler'])) {
                    return call_user_func($route['handler'], ...$matches);
                }

                // Jika handler berupa [ControllerClass, method]
                [$controllerClass, $methodName] = $route['handler'];
                $controllerFile = __DIR__ . '/../controllers/' . $controllerClass . '.php';

                if (!file_exists($controllerFile)) {
                    http_response_code(500);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Controller file $controllerClass tidak ditemukan"
                    ]);
                    return;
                }

                require_once $controllerFile;

                // 🔹 Gunakan konstruktor dengan cache
                try {
                    $controller = new $controllerClass(self::$pdo, self::$cache);
                } catch (ArgumentCountError) {
                    // fallback jika controller belum diubah ke 2 argumen
                    $controller = new $controllerClass(self::$pdo);
                }

                if (!method_exists($controller, $methodName)) {
                    http_response_code(500);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Method $methodName tidak ditemukan di $controllerClass"
                    ]);
                    return;
                }

                return $controller->$methodName(...$matches);
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
