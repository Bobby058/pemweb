<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$_SERVER['REQUEST_URI'] = '/api/summary';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test';

ob_start();
require_once __DIR__ . '/../src/server.php';
$output = ob_get_clean();
echo $output;
