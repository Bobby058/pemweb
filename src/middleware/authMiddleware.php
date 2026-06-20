<?php
// middleware/authMiddleware.php — pengganti authMiddleware.js

require_once __DIR__ . '/../utils/Jwt.php';

/**
 * Mengecek header Authorization dan memverifikasi JWT.
 * Jika valid, mengembalikan array data user (decoded payload).
 * Jika tidak valid, langsung mengirim response 401 dan menghentikan eksekusi (exit),
 * persis seperti perilaku `return res.status(401)...` pada versi Express.
 */
function authMiddleware(): array
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    // fallback jika getallheaders() tidak tersedia (mis. di beberapa konfigurasi server)
    $authHeader = $headers['Authorization']
        ?? $headers['authorization']
        ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? null);

    if (!$authHeader) {
        sendJson(['message' => 'Token tidak ada'], 401);
        exit;
    }

    $parts = explode(' ', $authHeader);
    $token = $parts[1] ?? '';

    $decoded = Jwt::verify($token, getenv('JWT_SECRET') ?: '');

    if (!$decoded) {
        sendJson(['message' => 'Token tidak valid'], 401);
        exit;
    }

    return $decoded; // berisi: id, nama, role, iat, exp
}
