<?php
header('Content-Type: application/json');
require_once '../../Private/db_config.php';
require_once __DIR__ . '/olc_helper.php';

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->query("
        SELECT project_id, project_name, project_status, location, plus_code, latitude, longitude
        FROM survey_projects
        ORDER BY created_date DESC
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($projects as $p) {
        $lat = $p['latitude']  !== null ? (float)$p['latitude']  : null;
        $lon = $p['longitude'] !== null ? (float)$p['longitude'] : null;

        // If coordinates not yet stored but plus code exists, decode now and cache
        if (($lat === null || $lon === null) && !empty(trim($p['plus_code'] ?? ''))) {
            $decoded = olcProcessRaw($conn, $p['plus_code']);
            if ($decoded) {
                $lat = $decoded['lat'];
                $lon = $decoded['lon'];
                // Store for all future requests — no decoding ever needed again
                $conn->prepare("
                    UPDATE survey_projects
                    SET latitude = :lat, longitude = :lon, plus_code = :code
                    WHERE project_id = :id
                ")->execute([
                    ':lat'  => $lat,
                    ':lon'  => $lon,
                    ':code' => $decoded['full_code'],
                    ':id'   => $p['project_id'],
                ]);
            }
        }

        $result[] = [
            'projectId'     => $p['project_id'],
            'projectName'   => $p['project_name'],
            'projectStatus' => $p['project_status'],
            'location'      => $p['location'],
            'plus_code'     => $p['plus_code'],
            'lat'           => $lat,
            'lon'           => $lon,
        ];
    }

    echo json_encode(['success' => true, 'projects' => $result]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
