<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/db.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM users LIMIT 1");
$user = $stmt->fetch();
echo json_encode($user);
