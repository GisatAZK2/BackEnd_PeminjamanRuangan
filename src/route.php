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

// Room Mangement
Router::post('/api/AddRoom', [RuanganController::class, 'addRoom']);
Router::post('/api/BookingRoom', [RuanganController::class, 'createBooking']);
Router::post('/api/UpdateStatusBooking/{id}', [RuanganController::class, 'updateStatus']);
Router::post('/api/RoomFinished/{id}', [RuanganController::class, 'markFinished']);
Router::post('/api/AutoFinishRoom', [RuanganController::class, 'autoMarkFinished']);


// Jalankan router
Router::dispatch($pdo);
