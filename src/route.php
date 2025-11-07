<?php
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';
require_once __DIR__ . '/../middleware/ApiKeyMiddleware.php';
require_once __DIR__ . '/../middleware/JwtAuthMiddleware.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/RuanganController.php';

// Jalankan middleware global
CorsMiddleware::handle();
ApiKeyMiddleware::validate();

// ========================================================
// 🔒 Helper: Middleware wrapper untuk route yang butuh JWT
// ========================================================
$protected = function (array $handler) {
    return function (...$params) use ($handler) {
        JwtAuthMiddleware::handle(); // validasi JWT di sini

        [$controllerClass, $methodName] = $handler;
        global $pdo; // ambil koneksi PDO global

        $controllerFile = __DIR__ . '/../controllers/' . $controllerClass . '.php';
        if (!file_exists($controllerFile)) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Controller file $controllerClass tidak ditemukan"]);
            exit;
        }

        require_once $controllerFile;
        $controller = new $controllerClass($pdo);

        if (!method_exists($controller, $methodName)) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Method $methodName tidak ditemukan"]);
            exit;
        }

        // Jalankan method controller dengan parameter dinamis (misalnya {id})
        return $controller->$methodName(...$params);
    };
};

// ========================================================
// 🔓 Public routes (tanpa JWT)
// ========================================================
Router::post('/api/login', [AuthController::class, 'login']);
Router::post('/api/logout', [AuthController::class, 'logout']);

// ========================================================
// 👤 User Management (protected)
// ========================================================
// USER ENDPOINTS
Router::get('/api/users', [UserController::class, 'getAll']);
Router::get('/api/users/detail', [UserController::class, 'getDetail']);
Router::post('/api/users/add', [UserController::class, 'add']);
Router::put('/api/users/update', [UserController::class, 'update']);
Router::delete('/api/users/delete', [UserController::class, 'delete']);
Router::post('/api/users/request-edit', [UserController::class, 'requestEdit']);
Router::post('/api/users/change-role', [UserController::class, 'changeRole']);

// DIVISI ENDPOINTS (admin only)
Router::get('/api/divisi', [DivisiController::class, 'getAll']);
Router::post('/api/divisi', [DivisiController::class, 'add']);
Router::put('/api/divisi/{id}', [DivisiController::class, 'update']);
Router::delete('/api/divisi/{id}', [DivisiController::class, 'delete']);
// ========================================================
// 🏢 Room Management (protected)
// ========================================================
Router::post('/api/AddRoom', ([RuanganController::class, 'addRoom']));
Router::post('/api/BookingRoom',$protected([RuanganController::class, 'createBooking']));
Router::post('/api/UpdateStatusBooking/{id}',$protected([RuanganController::class, 'updateStatus']));
Router::post('/api/RoomFinished/{id}',$protected([RuanganController::class, 'markFinished']));
Router::get('/api/downloadNotulen/{id}', $protected([RuanganController::class, 'downloadNotulen']));
Router::get('/api/roomAvailability', ([RuanganController::class, 'getRoomAvailability']));
Router::get('/api/GetHistory',$protected([RuanganController::class, 'getBookingHistory']));
Router::post('/api/AutoFinishRoom',$protected([RuanganController::class, 'autoMarkFinished']));

// ========================================================
// 🚀 Jalankan router
// ========================================================
Router::dispatch($pdo);
