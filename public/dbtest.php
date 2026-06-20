<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=3306;dbname=' . getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASSWORD')
    );
    echo "DB connected!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
