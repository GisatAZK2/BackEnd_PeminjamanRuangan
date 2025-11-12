<?php

// Jalankan middleware global
CorsMiddleware::handle();
ApiKeyMiddleware::validate();

// ========================================================
// 🔓 Public routes (tanpa JWT)
// ========================================================
Router::post('/api/login', [AuthController::class, 'login']);
Router::post('/api/logout', [AuthController::class, 'logout']);


// ========================================================
// 🔓 Dashboard routes (All Role)
// ========================================================
Router::get('/api/statistik', [StatistikController::class, 'index']);


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
Router::get('/api/divisi/{id}', [DivisiController::class, 'getById']);
Router::post('/api/divisi', [DivisiController::class, 'add']);
Router::put('/api/divisi/{id}', [DivisiController::class, 'update']);
Router::delete('/api/divisi/{id}', [DivisiController::class, 'delete']);

// ========================================================
// 🏢 Room Management (protected)
// ========================================================
Router::post('/api/AddRoom',([RuanganController::class, 'addRoom']));
Router::get('/api/ruangan', [RuanganController::class, 'getroomAll']);
Router::get('/api/ruangan/{id}', [RuanganController::class, 'getroomById']);
Router::put('/api/ruangan/{id}', [RuanganController::class, 'updateRoom']);
Router::delete('/api/ruangan/{id}', [RuanganController::class, 'deleteRoom']);

Router::post('/api/BookingRoom',([RuanganController::class, 'createBooking']));
Router::post('/api/UpdateStatusBooking/{id}',([RuanganController::class, 'updateStatus']));
Router::post('/api/RoomFinished/{id}',([RuanganController::class, 'markFinished']));
Router::get('/api/downloadNotulen/{id}', ([RuanganController::class, 'downloadNotulen']));
Router::get('/api/roomAvailability', ([RuanganController::class, 'getRoomAvailability']));
Router::get('/api/GetHistory',([RuanganController::class, 'getBookingHistory']));
Router::post('/api/AutoFinishRoom',([RuanganController::class, 'autoMarkFinished']));

// ========================================================
// 🚀 Jalankan router
// ========================================================
Router::dispatch($pdo , $cache);
