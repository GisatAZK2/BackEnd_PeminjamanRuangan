<?php


require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/core/Router.php';
$cache = require __DIR__ . '/config/cache.php';
require_once __DIR__ . '/middleware/CorsMiddleware.php';
require_once __DIR__ . '/middleware/ApiKeyMiddleware.php';

Router::init($pdo, $cache);

require_once __DIR__ . '/src/route.php';

?>

<!DOCTYPE html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" type="image/x-icon" href="public/icon.svg">
  <title>Peminjaman Ruang Rapat</title>
  
  <link rel="stylesheet" href="public/css/tailwind.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
  

