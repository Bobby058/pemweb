<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/dev/stderr');
error_reporting(E_ALL);
// server.php — pengganti server.js
// Bisa dijalankan dengan PHP built-in server sebagai router:
//   php -S localhost:5001 src/server.php
// atau di-include lewat virtual host (Apache/Nginx + PHP-FPM) yang mengarah ke sini.

require_once __DIR__ . '/db.php'; // sekaligus load .env

const PUBLIC_DIR = __DIR__ . '/../public';

// ===== CORS (biar bisa diakses dari origin lain, mis. Apache di port 80) =====
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Helper kirim response JSON (pengganti res.status(x).json(...))
 */
function sendJson($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
}

/**
 * Helper ambil body JSON dari request (pengganti express.json() / req.body)
 */
function getJsonBody(): array
{
    static $body = null;
    if ($body === null) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        $body = is_array($decoded) ? $decoded : [];
    }
    return $body;
}

require_once __DIR__ . '/routes/authroute.php';

// ===== Routing utama =====

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = $uri === '' ? '/' : $uri;

// Jika request diawali "/api" -> arahkan ke handler API (pengganti app.use('/api', routes))
if (str_starts_with($uri, '/api')) {
    $apiPath = substr($uri, 4); // hilangkan prefix "/api"
    if ($apiPath === '') $apiPath = '/';

    $handled = handleAuthRoutes($method, $apiPath);

    if (!$handled) {
        sendJson(['message' => 'Endpoint tidak ditemukan'], 404);
    }
    exit;
}

// Jika bukan "/api" -> layani sebagai static file (pengganti express.static('public'))
$filePath = realpath(PUBLIC_DIR . $uri);

if ($filePath && str_starts_with($filePath, realpath(PUBLIC_DIR)) && is_file($filePath)) {
    $mime = match (pathinfo($filePath, PATHINFO_EXTENSION)) {
        'html' => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        default => 'application/octet-stream',
    };
    header("Content-Type: $mime");
    readfile($filePath);
    exit;
}

// Default: arahkan ke index.html (mirip SPA fallback) jika file tidak ditemukan
if ($uri === '/' ) {
    $index = PUBLIC_DIR . '/index.html';
    if (is_file($index)) {
        header('Content-Type: text/html');
        readfile($index);
        exit;
    }
}

http_response_code(404);
echo 'Not Found';
