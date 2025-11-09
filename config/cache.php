<?php
// config/cache.php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

require_once __DIR__ . '/../vendor/autoload.php';

// Adapter berbasis file (default di /var/cache)
$psr6Cache = new FilesystemAdapter(
    namespace: 'app_cache',
    defaultLifetime: 60, // cache 1 menit
);

return new Psr16Cache($psr6Cache);
