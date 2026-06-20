<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$_SERVER['REQUEST_URI'] = '/auth/login';
$_SERVER['REQUEST_METHOD'] = 'POST';

require_once __DIR__ . '/../src/server.php';
