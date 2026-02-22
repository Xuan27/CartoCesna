<?php
header('Content-Type: application/json');
require_once '../../Private/db_config.php';
$db = new Database();
$conn = $db->getConnection();

try {
    // Support both GET and POST
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
    } else {
        // Try JSON body first, then form POST
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $action = $input['action'] ?? '';
        } else {
            $input = $_POST;
            $action = $_POST['action'] ?? '';
        }
    }

    // ========== PROPERTIES ==========

    if ($action === 'get_properties') {
        $stmt = $conn->query("
            SELECT sp.*,
                   COUNT(sc.scan_id) as scan_count
            FROM scan_properties sp
            LEFT JOIN scan_projects sc ON sp.property_id = sc.property_id
            GROUP BY sp.property_id
            ORDER BY sp.created_date DESC
        ");
        echo json_encode(['success' => true, 'properties' => $stmt->fetchAll()]);

    } elseif ($action === 'get_property') {
        $propertyId = $_GET['property_id'] ?? ($input['property_id'] ?? null);
        if (!$propertyId) {
            echo json_encode(['success' => false, 'message' => 'Property ID required']);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM scan_properties WHERE property_id = :id");
        $stmt->execute(['id' => $propertyId]);
        $property = $stmt->fetch();
        if (!$property) {
            echo json_encode(['success' => false, 'message' => 'Property not found']);
            exit;
        }
        echo json_encode(['success' => true, 'property' => $property]);

    } elseif ($action === 'add_property') {
        $address = $input['address'] ?? '';
        if (empty($address)) {
            echo json_encode(['success' => false, 'message' => 'Address is required']);
            exit;
        }
        $stmt = $conn->prepare("
            INSERT INTO scan_properties (address, city, state, zip, property_type, sqft, floors, latitude, longitude, notes)
            VALUES (:address, :city, :state, :zip, :type, :sqft, :floors, :lat, :lng, :notes)
        ");
        $stmt->execute([
            'address' => $address,
            'city'    => $input['city'] ?? null,
            'state'   => $input['state'] ?? null,
            'zip'     => $input['zip'] ?? null,
            'type'    => $input['property_type'] ?? 'residential',
            'sqft'    => $input['sqft'] ?? null,
            'floors'  => $input['floors'] ?? 1,
            'lat'     => $input['latitude'] ?? null,
            'lng'     => $input['longitude'] ?? null,
            'notes'   => $input['notes'] ?? null
        ]);
        echo json_encode(['success' => true, 'message' => 'Property added', 'property_id' => $conn->lastInsertId()]);

    } elseif ($action === 'update_property') {
        $propertyId = $input['property_id'] ?? null;
        $address = $input['address'] ?? '';
        if (!$propertyId || empty($address)) {
            echo json_encode(['success' => false, 'message' => 'Property ID and address are required']);
            exit;
        }
        $stmt = $conn->prepare("
            UPDATE scan_properties
            SET address = :address, city = :city, state = :state, zip = :zip,
                property_type = :type, sqft = :sqft, floors = :floors,
                latitude = :lat, longitude = :lng, notes = :notes
            WHERE property_id = :id
        ");
        $stmt->execute([
            'address' => $address,
            'city'    => $input['city'] ?? null,
            'state'   => $input['state'] ?? null,
            'zip'     => $input['zip'] ?? null,
            'type'    => $input['property_type'] ?? 'residential',
            'sqft'    => $input['sqft'] ?? null,
            'floors'  => $input['floors'] ?? 1,
            'lat'     => $input['latitude'] ?? null,
            'lng'     => $input['longitude'] ?? null,
            'notes'   => $input['notes'] ?? null,
            'id'      => $propertyId
        ]);
        echo json_encode(['success' => true, 'message' => 'Property updated']);

    } elseif ($action === 'delete_property') {
        $propertyId = $input['property_id'] ?? null;
        if (!$propertyId) {
            echo json_encode(['success' => false, 'message' => 'Property ID required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM scan_properties WHERE property_id = :id");
        $stmt->execute(['id' => $propertyId]);
        echo json_encode(['success' => true, 'message' => 'Property deleted']);

    // ========== SCANS ==========

    } elseif ($action === 'get_scans') {
        $propertyId = $_GET['property_id'] ?? ($input['property_id'] ?? null);
        if ($propertyId) {
            $stmt = $conn->prepare("
                SELECT sp.*, prop.address,
                       COUNT(sc.scene_id) as scene_count
                FROM scan_projects sp
                LEFT JOIN scan_properties prop ON sp.property_id = prop.property_id
                LEFT JOIN scan_scenes sc ON sp.scan_id = sc.scan_id
                WHERE sp.property_id = :pid
                GROUP BY sp.scan_id
                ORDER BY sp.created_date DESC
            ");
            $stmt->execute(['pid' => $propertyId]);
        } else {
            $stmt = $conn->query("
                SELECT sp.*, prop.address,
                       COUNT(sc.scene_id) as scene_count
                FROM scan_projects sp
                LEFT JOIN scan_properties prop ON sp.property_id = prop.property_id
                LEFT JOIN scan_scenes sc ON sp.scan_id = sc.scan_id
                GROUP BY sp.scan_id
                ORDER BY sp.created_date DESC
            ");
        }
        echo json_encode(['success' => true, 'scans' => $stmt->fetchAll()]);

    } elseif ($action === 'get_scan') {
        $scanId = $_GET['scan_id'] ?? ($input['scan_id'] ?? null);
        if (!$scanId) {
            echo json_encode(['success' => false, 'message' => 'Scan ID required']);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT sp.*, prop.address
            FROM scan_projects sp
            LEFT JOIN scan_properties prop ON sp.property_id = prop.property_id
            WHERE sp.scan_id = :id
        ");
        $stmt->execute(['id' => $scanId]);
        $scan = $stmt->fetch();
        if (!$scan) {
            echo json_encode(['success' => false, 'message' => 'Scan not found']);
            exit;
        }
        echo json_encode(['success' => true, 'scan' => $scan]);

    } elseif ($action === 'add_scan') {
        $name = $input['scan_name'] ?? '';
        $propertyId = $input['property_id'] ?? null;
        if (empty($name) || !$propertyId) {
            echo json_encode(['success' => false, 'message' => 'Scan name and property are required']);
            exit;
        }
        $stmt = $conn->prepare("
            INSERT INTO scan_projects (property_id, scan_name, status, scanner_type, software_used, file_format, scan_date, raw_data_path, notes)
            VALUES (:pid, :name, :status, :scanner, :software, :format, :date, :path, :notes)
        ");
        $stmt->execute([
            'pid'      => $propertyId,
            'name'     => $name,
            'status'   => $input['status'] ?? 'draft',
            'scanner'  => $input['scanner_type'] ?? null,
            'software' => $input['software_used'] ?? null,
            'format'   => $input['file_format'] ?? null,
            'date'     => $input['scan_date'] ?? null,
            'path'     => $input['raw_data_path'] ?? null,
            'notes'    => $input['notes'] ?? null
        ]);
        echo json_encode(['success' => true, 'message' => 'Scan added', 'scan_id' => $conn->lastInsertId()]);

    } elseif ($action === 'update_scan') {
        $scanId = $input['scan_id'] ?? null;
        $name = $input['scan_name'] ?? '';
        if (!$scanId || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Scan ID and name are required']);
            exit;
        }
        $stmt = $conn->prepare("
            UPDATE scan_projects
            SET scan_name = :name, property_id = :pid, status = :status,
                scanner_type = :scanner, software_used = :software, file_format = :format,
                scan_date = :date, raw_data_path = :path, notes = :notes
            WHERE scan_id = :id
        ");
        $stmt->execute([
            'name'     => $name,
            'pid'      => $input['property_id'] ?? null,
            'status'   => $input['status'] ?? 'draft',
            'scanner'  => $input['scanner_type'] ?? null,
            'software' => $input['software_used'] ?? null,
            'format'   => $input['file_format'] ?? null,
            'date'     => $input['scan_date'] ?? null,
            'path'     => $input['raw_data_path'] ?? null,
            'notes'    => $input['notes'] ?? null,
            'id'       => $scanId
        ]);
        echo json_encode(['success' => true, 'message' => 'Scan updated']);

    } elseif ($action === 'delete_scan') {
        $scanId = $input['scan_id'] ?? null;
        if (!$scanId) {
            echo json_encode(['success' => false, 'message' => 'Scan ID required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM scan_projects WHERE scan_id = :id");
        $stmt->execute(['id' => $scanId]);
        echo json_encode(['success' => true, 'message' => 'Scan deleted']);

    } elseif ($action === 'publish_scan') {
        $scanId = $input['scan_id'] ?? null;
        if (!$scanId) {
            echo json_encode(['success' => false, 'message' => 'Scan ID required']);
            exit;
        }

        $conn->beginTransaction();

        // Update status to published
        $stmt = $conn->prepare("UPDATE scan_projects SET status = 'published' WHERE scan_id = :id");
        $stmt->execute(['id' => $scanId]);

        // Check if a tour config already exists with an embed_token
        $stmt2 = $conn->prepare("SELECT embed_token FROM tour_configurations WHERE scan_id = :id");
        $stmt2->execute(['id' => $scanId]);
        $existing = $stmt2->fetch();

        if ($existing && $existing['embed_token']) {
            $token = $existing['embed_token'];
        } else {
            $token = bin2hex(random_bytes(32));
        }

        // Insert or update tour_configurations with token and published_date
        $stmt3 = $conn->prepare("
            INSERT INTO tour_configurations (scan_id, embed_token, published_date)
            VALUES (:sid, :token, NOW())
            ON DUPLICATE KEY UPDATE
                embed_token = IF(embed_token IS NULL, :token2, embed_token),
                published_date = NOW()
        ");
        $stmt3->execute(['sid' => $scanId, 'token' => $token, 'token2' => $token]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Scan published', 'embed_token' => $token]);

    // ========== SCENES ==========

    } elseif ($action === 'get_scenes') {
        $scanId = $_GET['scan_id'] ?? ($input['scan_id'] ?? null);
        if (!$scanId) {
            echo json_encode(['success' => false, 'message' => 'Scan ID required']);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT * FROM scan_scenes WHERE scan_id = :sid ORDER BY sort_order, scene_id
        ");
        $stmt->execute(['sid' => $scanId]);
        echo json_encode(['success' => true, 'scenes' => $stmt->fetchAll()]);

    } elseif ($action === 'get_scene') {
        $sceneId = $_GET['scene_id'] ?? ($input['scene_id'] ?? null);
        if (!$sceneId) {
            echo json_encode(['success' => false, 'message' => 'Scene ID required']);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM scan_scenes WHERE scene_id = :id");
        $stmt->execute(['id' => $sceneId]);
        $scene = $stmt->fetch();
        if (!$scene) {
            echo json_encode(['success' => false, 'message' => 'Scene not found']);
            exit;
        }
        echo json_encode(['success' => true, 'scene' => $scene]);

    } elseif ($action === 'add_scene') {
        $name = $input['scene_name'] ?? '';
        $scanId = $input['scan_id'] ?? null;
        if (empty($name) || !$scanId) {
            echo json_encode(['success' => false, 'message' => 'Scene name and scan ID are required']);
            exit;
        }

        $isStart = !empty($input['is_start_scene']) ? 1 : 0;

        $conn->beginTransaction();

        // Get next sort_order
        $stmtMax = $conn->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM scan_scenes WHERE scan_id = :sid");
        $stmtMax->execute(['sid' => $scanId]);
        $nextSort = $stmtMax->fetchColumn();

        // If marking as start, clear flag on siblings
        if ($isStart) {
            $conn->prepare("UPDATE scan_scenes SET is_start_scene = 0 WHERE scan_id = :sid")->execute(['sid' => $scanId]);
        }

        $stmt = $conn->prepare("
            INSERT INTO scan_scenes (scan_id, scene_name, sort_order, is_start_scene, pos_x, pos_y, pos_z, rot_x, rot_y, rot_z, panorama_path, thumbnail_path)
            VALUES (:sid, :name, :sort, :start, :px, :py, :pz, :rx, :ry, :rz, :pano, :thumb)
        ");
        $stmt->execute([
            'sid'   => $scanId,
            'name'  => $name,
            'sort'  => $nextSort,
            'start' => $isStart,
            'px'    => $input['pos_x'] ?? 0,
            'py'    => $input['pos_y'] ?? 0,
            'pz'    => $input['pos_z'] ?? 0,
            'rx'    => $input['rot_x'] ?? 0,
            'ry'    => $input['rot_y'] ?? 0,
            'rz'    => $input['rot_z'] ?? 0,
            'pano'  => $input['panorama_path'] ?? null,
            'thumb' => $input['thumbnail_path'] ?? null
        ]);
        $sceneId = $conn->lastInsertId();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Scene added', 'scene_id' => $sceneId]);

    } elseif ($action === 'update_scene') {
        $sceneId = $input['scene_id'] ?? null;
        $name = $input['scene_name'] ?? '';
        $scanId = $input['scan_id'] ?? null;
        if (!$sceneId || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Scene ID and name are required']);
            exit;
        }

        $isStart = !empty($input['is_start_scene']) ? 1 : 0;

        $conn->beginTransaction();

        // If marking as start, clear flag on siblings first
        if ($isStart && $scanId) {
            $conn->prepare("UPDATE scan_scenes SET is_start_scene = 0 WHERE scan_id = :sid AND scene_id != :scid")
                ->execute(['sid' => $scanId, 'scid' => $sceneId]);
        }

        $stmt = $conn->prepare("
            UPDATE scan_scenes
            SET scene_name = :name, is_start_scene = :start,
                pos_x = :px, pos_y = :py, pos_z = :pz,
                rot_x = :rx, rot_y = :ry, rot_z = :rz,
                panorama_path = :pano, thumbnail_path = :thumb
            WHERE scene_id = :id
        ");
        $stmt->execute([
            'name'  => $name,
            'start' => $isStart,
            'px'    => $input['pos_x'] ?? 0,
            'py'    => $input['pos_y'] ?? 0,
            'pz'    => $input['pos_z'] ?? 0,
            'rx'    => $input['rot_x'] ?? 0,
            'ry'    => $input['rot_y'] ?? 0,
            'rz'    => $input['rot_z'] ?? 0,
            'pano'  => $input['panorama_path'] ?? null,
            'thumb' => $input['thumbnail_path'] ?? null,
            'id'    => $sceneId
        ]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Scene updated']);

    } elseif ($action === 'delete_scene') {
        $sceneId = $input['scene_id'] ?? null;
        if (!$sceneId) {
            echo json_encode(['success' => false, 'message' => 'Scene ID required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM scan_scenes WHERE scene_id = :id");
        $stmt->execute(['id' => $sceneId]);
        echo json_encode(['success' => true, 'message' => 'Scene deleted']);

    } elseif ($action === 'reorder_scenes') {
        $order = $input['order'] ?? [];
        if (empty($order)) {
            echo json_encode(['success' => false, 'message' => 'Order array required']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE scan_scenes SET sort_order = :sort WHERE scene_id = :id");
        foreach ($order as $index => $sceneId) {
            $stmt->execute(['sort' => $index, 'id' => $sceneId]);
        }
        echo json_encode(['success' => true, 'message' => 'Scenes reordered']);

    // ========== TOUR CONFIG ==========

    } elseif ($action === 'get_tour_config') {
        $scanId = $_GET['scan_id'] ?? ($input['scan_id'] ?? null);
        if (!$scanId) {
            echo json_encode(['success' => false, 'message' => 'Scan ID required']);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM tour_configurations WHERE scan_id = :id");
        $stmt->execute(['id' => $scanId]);
        $config = $stmt->fetch();
        if ($config) {
            $config['renderer_settings'] = $config['renderer_settings'] ? json_decode($config['renderer_settings'], true) : null;
            $config['controls_config']   = $config['controls_config']   ? json_decode($config['controls_config'], true)   : null;
        }
        echo json_encode(['success' => true, 'config' => $config]);

    } elseif ($action === 'save_tour_config') {
        $scanId = $input['scan_id'] ?? null;
        if (!$scanId) {
            echo json_encode(['success' => false, 'message' => 'Scan ID required']);
            exit;
        }
        $rendererSettings = isset($input['renderer_settings']) ? json_encode($input['renderer_settings']) : null;
        $controlsConfig   = isset($input['controls_config'])   ? json_encode($input['controls_config'])   : null;

        $stmt = $conn->prepare("
            INSERT INTO tour_configurations
                (scan_id, nav_style, primary_color, show_measurements, show_minimap, autoplay, autoplay_delay, renderer_settings, controls_config)
            VALUES
                (:sid, :nav, :color, :meas, :mini, :auto, :delay, :renderer, :controls)
            ON DUPLICATE KEY UPDATE
                nav_style          = :nav2,
                primary_color      = :color2,
                show_measurements  = :meas2,
                show_minimap       = :mini2,
                autoplay           = :auto2,
                autoplay_delay     = :delay2,
                renderer_settings  = :renderer2,
                controls_config    = :controls2
        ");
        $stmt->execute([
            'sid'       => $scanId,
            'nav'       => $input['nav_style'] ?? 'arrows',
            'color'     => $input['primary_color'] ?? '#2563eb',
            'meas'      => isset($input['show_measurements']) ? (int)$input['show_measurements'] : 1,
            'mini'      => isset($input['show_minimap']) ? (int)$input['show_minimap'] : 1,
            'auto'      => isset($input['autoplay']) ? (int)$input['autoplay'] : 0,
            'delay'     => $input['autoplay_delay'] ?? 5,
            'renderer'  => $rendererSettings,
            'controls'  => $controlsConfig,
            'nav2'      => $input['nav_style'] ?? 'arrows',
            'color2'    => $input['primary_color'] ?? '#2563eb',
            'meas2'     => isset($input['show_measurements']) ? (int)$input['show_measurements'] : 1,
            'mini2'     => isset($input['show_minimap']) ? (int)$input['show_minimap'] : 1,
            'auto2'     => isset($input['autoplay']) ? (int)$input['autoplay'] : 0,
            'delay2'    => $input['autoplay_delay'] ?? 5,
            'renderer2' => $rendererSettings,
            'controls2' => $controlsConfig
        ]);
        echo json_encode(['success' => true, 'message' => 'Tour configuration saved']);

    // ========== ANALYTICS ==========

    } elseif ($action === 'get_analytics') {
        $scanId = $_GET['scan_id'] ?? ($input['scan_id'] ?? null);
        if (!$scanId) {
            echo json_encode(['success' => false, 'message' => 'Scan ID required']);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT event_type, COUNT(*) as event_count, MAX(event_date) as last_event
            FROM tour_analytics
            WHERE scan_id = :id
            GROUP BY event_type
            ORDER BY event_count DESC
        ");
        $stmt->execute(['id' => $scanId]);
        echo json_encode(['success' => true, 'analytics' => $stmt->fetchAll()]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
