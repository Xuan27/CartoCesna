<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

$tokenUrl = 'https://westwood.maps.arcgis.com/sharing/rest/generateToken';

$postData = http_build_query([
    'username'   => $username,
    'password'   => $password,
    'client'     => 'requestip',
    'expiration' => 120,   // 2 hours
    'f'          => 'json',
]);

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$err      = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['success' => false, 'message' => 'Request failed: ' . $err]);
    exit;
}

$data = json_decode($response, true);

if (!empty($data['error'])) {
    echo json_encode(['success' => false, 'message' => $data['error']['message'] ?? 'Authentication failed.']);
    exit;
}

if (empty($data['token'])) {
    echo json_encode(['success' => false, 'message' => 'No token returned. Check your credentials.']);
    exit;
}

echo json_encode(['success' => true, 'token' => $data['token'], 'expires' => $data['expires'] ?? null]);
