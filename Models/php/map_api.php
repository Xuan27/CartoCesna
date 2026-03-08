<?php
header('Content-Type: application/json');
require_once '../../Private/db_config.php';

// ── Open Location Code implementation ──────────────────────────────────────
// Pair resolutions (degrees) for each of the 5 encoding pairs.
// These are FIXED values, not derived from total lat/lon range.
const OLC_ALPHABET  = '23456789CFGHJMPQRVWX';
const OLC_PAIR_RES  = [20.0, 1.0, 0.05, 0.0025, 0.000125];
const OLC_GRID_ROWS = 5;
const OLC_GRID_COLS = 4;

function olcIsShort(string $code): bool {
    $plusPos = strpos($code, '+');
    return $plusPos !== false && $plusPos < 8;
}

function olcIsFull(string $code): bool {
    $plusPos = strpos($code, '+');
    return $plusPos !== false && $plusPos === 8;
}

/**
 * Decode a full OLC code (with or without '+') to lat/lon center.
 */
function olcDecode(string $code): ?array {
    $code = strtoupper(str_replace('+', '', $code));
    $len  = strlen($code);
    if ($len < 2) return null;

    $alpha  = OLC_ALPHABET;
    $latLo  = -90.0;
    $lonLo  = -180.0;

    // Pair-coded section (up to 10 chars = 5 pairs)
    $pairLen = min($len, 10);
    for ($i = 0; $i < $pairLen; $i += 2) {
        $res = OLC_PAIR_RES[intdiv($i, 2)];
        $d1  = strpos($alpha, $code[$i]);
        $d2  = strpos($alpha, $code[$i + 1]);
        if ($d1 === false || $d2 === false) return null;
        $latLo += (float)$d1 * $res;
        $lonLo += (float)$d2 * $res;
    }

    // Resolution of the last pair
    $pairRes = OLC_PAIR_RES[min(intdiv($pairLen, 2), 4)];
    $latHi   = $latLo + $pairRes;
    $lonHi   = $lonLo + $pairRes;

    // Grid-coded section (chars after position 10)
    if ($len > 10) {
        $latRes = $pairRes;
        $lonRes = $pairRes;
        for ($i = 10; $i < $len; $i++) {
            $latRes /= OLC_GRID_ROWS;
            $lonRes /= OLC_GRID_COLS;
            $d   = strpos($alpha, $code[$i]);
            if ($d === false) return null;
            $row = intdiv((int)$d, OLC_GRID_COLS);
            $col = (int)$d % OLC_GRID_COLS;
            $latLo += (float)$row * $latRes;
            $lonLo += (float)$col * $lonRes;
        }
        $latHi = $latLo + $latRes;
        $lonHi = $lonLo + $lonRes;
    }

    return [
        'lat' => ($latLo + $latHi) / 2.0,
        'lon' => ($lonLo + $lonHi) / 2.0,
    ];
}

/**
 * Encode a reference lat/lon to a $numPairs-pair prefix string (no '+').
 */
function olcPrefix(float $lat, float $lon, int $numPairs): string {
    $alpha  = OLC_ALPHABET;
    $latS   = min(90.0 - 1e-10, max(-90.0, $lat)) + 90.0;   // shift to [0, 180)
    $lonS   = fmod($lon + 180.0, 360.0);                     // shift to [0, 360)
    if ($lonS < 0.0) $lonS += 360.0;

    $prefix = '';
    for ($i = 0; $i < $numPairs; $i++) {
        $res  = OLC_PAIR_RES[$i];
        $dLat = (int)floor($latS / $res) % 20;
        $dLon = (int)floor($lonS / $res) % 20;
        $prefix .= $alpha[$dLat] . $alpha[$dLon];
        $latS = fmod($latS, $res);
        $lonS = fmod($lonS, $res);
    }
    return $prefix;
}

/**
 * Recover a short OLC code to a full code using a reference lat/lon.
 */
function olcRecover(string $shortCode, float $refLat, float $refLon): ?string {
    $plusPos = strpos($shortCode, '+');
    if ($plusPos === false || $plusPos >= 8) return null;

    $padLen   = 8 - $plusPos;       // chars to prepend (always even)
    $numPairs = intdiv($padLen, 2);  // pairs to prepend
    $res      = OLC_PAIR_RES[$numPairs - 1]; // resolution of the last prepended pair

    $prefix   = olcPrefix($refLat, $refLon, $numPairs);
    $fullCode = $prefix . strtoupper($shortCode);

    // If decoded point is more than half a resolution away, shift by one resolution
    $decoded = olcDecode($fullCode);
    if (!$decoded) return null;

    if ($decoded['lat'] - $refLat > $res / 2)  { $adjLat = $refLat + $res; $prefix = olcPrefix($adjLat, $refLon, $numPairs); }
    elseif ($refLat - $decoded['lat'] > $res / 2) { $adjLat = $refLat - $res; $prefix = olcPrefix($adjLat, $refLon, $numPairs); }

    if ($decoded['lon'] - $refLon > $res / 2)  { $adjLon = $refLon + $res; $prefix = olcPrefix($refLat, $adjLon, $numPairs); }
    elseif ($refLon - $decoded['lon'] > $res / 2) { $adjLon = $refLon - $res; $prefix = olcPrefix($refLat, $adjLon, $numPairs); }

    return $prefix . strtoupper($shortCode);
}

// ── Nominatim geocoding (server-side, locality only) ───────────────────────
function geocodeLocality(string $locality): ?array {
    $url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($locality) . '&format=json&limit=1';
    $ctx = stream_context_create(['http' => [
        'header'  => "User-Agent: CartoCesna-SurveyPro/1.0\r\nAccept: application/json\r\n",
        'timeout' => 6
    ]]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;
    $data = json_decode($json, true);
    if (empty($data)) return null;
    return ['lat' => (float)$data[0]['lat'], 'lon' => (float)$data[0]['lon']];
}

// ── Main ───────────────────────────────────────────────────────────────────
try {
    $db   = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->query("SELECT project_id, project_name, project_status, location, plus_code FROM survey_projects ORDER BY created_date DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $localityCache = [];
    $result        = [];

    foreach ($projects as $p) {
        $entry = [
            'projectId'     => $p['project_id'],
            'projectName'   => $p['project_name'],
            'projectStatus' => $p['project_status'],
            'location'      => $p['location'],
            'plus_code'     => $p['plus_code'],
            'lat'           => null,
            'lon'           => null,
        ];

        $raw = trim($p['plus_code'] ?? '');
        if ($raw !== '') {
            // Split into OLC code and locality
            $spacePos = strpos($raw, ' ');
            $code     = $spacePos !== false ? substr($raw, 0, $spacePos) : $raw;
            $locality = $spacePos !== false ? trim(substr($raw, $spacePos + 1)) : '';
            $code     = strtoupper($code);

            $fullCode = $code;

            if (olcIsShort($code)) {
                if ($locality !== '') {
                    if (!isset($localityCache[$locality])) {
                        $localityCache[$locality] = geocodeLocality($locality);
                        usleep(1100000); // Nominatim: 1 req/sec
                    }
                    $ref = $localityCache[$locality];
                    if ($ref) {
                        $fullCode = olcRecover($code, $ref['lat'], $ref['lon']);
                    }
                }
            }

            if ($fullCode && olcIsFull($fullCode)) {
                $decoded = olcDecode($fullCode);
                if ($decoded) {
                    $entry['lat'] = round($decoded['lat'], 7);
                    $entry['lon'] = round($decoded['lon'], 7);
                }
            }
        }

        $result[] = $entry;
    }

    echo json_encode(['success' => true, 'projects' => $result]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
