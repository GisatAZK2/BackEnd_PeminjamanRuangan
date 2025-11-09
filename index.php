<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/core/Router.php';
$cache = require __DIR__ . '/config/cache.php';
require_once __DIR__ . '/middleware/CorsMiddleware.php';
require_once __DIR__ . '/middleware/ApiKeyMiddleware.php';

// 🔹 Inisialisasi Router global dengan PDO dan cache
Router::init($pdo, $cache);

require_once __DIR__ . '/src/route.php';
