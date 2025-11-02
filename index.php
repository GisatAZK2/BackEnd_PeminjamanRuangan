<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/middleware/CorsMiddleware.php';
require_once __DIR__ . '/middleware/ApiKeyMiddleware.php';
require_once __DIR__ . '/middleware/JwtAuthMiddleware.php';

require_once __DIR__ . '/src/route.php';
