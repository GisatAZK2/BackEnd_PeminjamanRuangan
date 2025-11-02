<?php
// src/route.php

CorsMiddleware::handle();
ApiKeyMiddleware::validate();

// === ROUTES ===

// Auth
Router::post('/api/login', [AuthController::class, 'login']);
Router::post('/api/logout', [AuthController::class, 'logout']);

// User Management
Router::get('/api/users', [UserController::class, 'getAll']);
Router::get('/api/users/detail', [UserController::class, 'getDetail']);
Router::post('/api/users/add', [UserController::class, 'add']);
Router::put('/api/users/update', [UserController::class, 'update']);
Router::delete('/api/users/delete', [UserController::class, 'delete']);

// Jalankan router
Router::dispatch($pdo);
