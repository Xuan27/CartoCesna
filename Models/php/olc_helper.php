<?php
/**
 * Open Location Code helpers + Nominatim geocoding with DB cache.
 * Include this file wherever OLC encoding/decoding is needed.
 *
 * Pair resolutions are FIXED values per the OLC spec — not derived from range:
 *   Pair 0 → 20°, Pair 1 → 1°, Pair 2 → 0.05°, Pair 3 → 0.0025°, Pair 4 → 0.000125°
 */

const OLC_ALPHABET  = '23456789CFGHJMPQRVWX';
const OLC_PAIR_RES  = [20.0, 1.0, 0.05, 0.0025, 0.000125];
const OLC_GRID_ROWS = 5;
const OLC_GRID_COLS = 4;

function olcIsShort(string $code): bool {
    $pos = strpos($code, '+');
    return $pos !== false && $pos < 8;
}

function olcIsFull(string $code): bool {
    $pos = strpos($code, '+');
    return $pos !== false && $pos === 8;
}

/** Decode a full OLC code (with or without '+') → ['lat'=>…,'lon'=>…] */
function olcDecode(string $code): ?array {
    $code = strtoupper(str_replace('+', '', $code));
    $len  = strlen($code);
    if ($len < 2) return null;

    $latLo = -90.0;
    $lonLo = -180.0;

    $pairLen = min($len, 10);
    for ($i = 0; $i < $pairLen; $i += 2) {
        $res = OLC_PAIR_RES[intdiv($i, 2)];
        $d1  = strpos(OLC_ALPHABET, $code[$i]);
        $d2  = strpos(OLC_ALPHABET, $code[$i + 1]);
        if ($d1 === false || $d2 === false) return null;
        $latLo += (float)$d1 * $res;
        $lonLo += (float)$d2 * $res;
    }

    $pairRes = OLC_PAIR_RES[min(intdiv($pairLen, 2), 4)];
    $latHi   = $latLo + $pairRes;
    $lonHi   = $lonLo + $pairRes;

    if ($len > 10) {
        $latRes = $pairRes;
        $lonRes = $pairRes;
        for ($i = 10; $i < $len; $i++) {
            $latRes /= OLC_GRID_ROWS;
            $lonRes /= OLC_GRID_COLS;
            $d   = strpos(OLC_ALPHABET, $code[$i]);
            if ($d === false) return null;
            $latLo += (float)intdiv((int)$d, OLC_GRID_COLS) * $latRes;
            $lonLo += (float)((int)$d % OLC_GRID_COLS) * $lonRes;
        }
        $latHi = $latLo + $latRes;
        $lonHi = $lonLo + $lonRes;
    }

    return ['lat' => ($latLo + $latHi) / 2.0, 'lon' => ($lonLo + $lonHi) / 2.0];
}

/** Build a prefix of $numPairs pairs from a reference lat/lon (no '+') */
function olcPrefix(float $lat, float $lon, int $numPairs): string {
    $latS = min(90.0 - 1e-10, max(-90.0, $lat)) + 90.0;
    $lonS = fmod($lon + 180.0, 360.0);
    if ($lonS < 0.0) $lonS += 360.0;

    $prefix = '';
    for ($i = 0; $i < $numPairs; $i++) {
        $res  = OLC_PAIR_RES[$i];
        $dLat = (int)floor($latS / $res) % 20;
        $dLon = (int)floor($lonS / $res) % 20;
        $prefix .= OLC_ALPHABET[$dLat] . OLC_ALPHABET[$dLon];
        $latS = fmod($latS, $res);
        $lonS = fmod($lonS, $res);
    }
    return $prefix;
}

/** Recover a short OLC code to full using a reference lat/lon */
function olcRecover(string $shortCode, float $refLat, float $refLon): ?string {
    $plusPos  = strpos($shortCode, '+');
    if ($plusPos === false || $plusPos >= 8) return null;

    $padLen   = 8 - $plusPos;
    $numPairs = intdiv($padLen, 2);
    $res      = OLC_PAIR_RES[$numPairs - 1];

    $prefix   = olcPrefix($refLat, $refLon, $numPairs);
    $fullCode = $prefix . strtoupper($shortCode);
    $decoded  = olcDecode($fullCode);
    if (!$decoded) return null;

    // Shift by one resolution step if decoded point is too far from reference
    $adjLat = $refLat;
    $adjLon = $refLon;
    if ($decoded['lat'] - $refLat >  $res / 2) $adjLat = $refLat + $res;
    if ($refLat - $decoded['lat'] >  $res / 2) $adjLat = $refLat - $res;
    if ($decoded['lon'] - $refLon >  $res / 2) $adjLon = $refLon + $res;
    if ($refLon - $decoded['lon'] >  $res / 2) $adjLon = $refLon - $res;

    if ($adjLat !== $refLat || $adjLon !== $refLon) {
        $prefix   = olcPrefix($adjLat, $adjLon, $numPairs);
        $fullCode = $prefix . strtoupper($shortCode);
    }

    return $fullCode;
}

/**
 * Parse a raw plus code string (e.g. "2PVH+W3 AUSTIN, TEXAS, USA")
 * Returns ['code' => '2PVH+W3', 'locality' => 'AUSTIN, TEXAS, USA']
 */
function olcParse(string $raw): array {
    $raw      = trim($raw);
    $spacePos = strpos($raw, ' ');
    return [
        'code'     => $spacePos !== false ? substr($raw, 0, $spacePos)        : $raw,
        'locality' => $spacePos !== false ? trim(substr($raw, $spacePos + 1)) : '',
    ];
}

/**
 * Geocode a locality string to lat/lon, using the DB cache first.
 * Falls back to Nominatim only on a cache miss. Stores the result in cache.
 * Returns ['lat'=>…,'lon'=>…] or null on failure.
 */
function geocodeWithCache(PDO $conn, string $locality): ?array {
    $locality = trim($locality);
    if ($locality === '') return null;

    // 1. Check DB cache
    $stmt = $conn->prepare("SELECT lat, lon FROM geocode_cache WHERE locality = :loc");
    $stmt->execute([':loc' => $locality]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return ['lat' => (float)$row['lat'], 'lon' => (float)$row['lon']];
    }

    // 2. Call Nominatim
    $url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($locality) . '&format=json&limit=1';
    $ctx = stream_context_create(['http' => [
        'header'  => "User-Agent: CartoCesna-SurveyPro/1.0\r\nAccept: application/json\r\n",
        'timeout' => 6,
    ]]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;
    $data = json_decode($json, true);
    if (empty($data)) return null;

    $result = ['lat' => (float)$data[0]['lat'], 'lon' => (float)$data[0]['lon']];

    // 3. Store in cache
    $ins = $conn->prepare("INSERT IGNORE INTO geocode_cache (locality, lat, lon) VALUES (:loc, :lat, :lon)");
    $ins->execute([':loc' => $locality, ':lat' => $result['lat'], ':lon' => $result['lon']]);

    // Respect Nominatim 1 req/sec policy
    usleep(1100000);

    return $result;
}

/**
 * Given a raw plus code string, return ['full_code'=>…, 'lat'=>…, 'lon'=>…].
 * Expands short codes using the geocode cache. Returns null if decoding fails.
 */
function olcProcessRaw(PDO $conn, string $raw): ?array {
    ['code' => $code, 'locality' => $locality] = olcParse($raw);
    $code = strtoupper($code);

    $fullCode = $code;

    if (olcIsShort($code)) {
        if ($locality === '') return null;
        $ref = geocodeWithCache($conn, $locality);
        if (!$ref) return null;
        $fullCode = olcRecover($code, $ref['lat'], $ref['lon']);
        if (!$fullCode) return null;
    }

    if (!olcIsFull($fullCode)) return null;

    $decoded = olcDecode($fullCode);
    if (!$decoded) return null;

    // Preserve locality in the stored full code so context is not lost
    $stored = $locality !== '' ? $fullCode . ' ' . $locality : $fullCode;

    return [
        'full_code' => $stored,
        'lat'       => round($decoded['lat'], 7),
        'lon'       => round($decoded['lon'], 7),
    ];
}
