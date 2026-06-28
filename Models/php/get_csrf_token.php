<?php
require_once '../../classes/Security.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

Security::secureSession();
$token = Security::getCsrfToken();

echo json_encode(['csrf_token' => $token]);
