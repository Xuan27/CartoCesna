<?php
function sendJsonResponse($data, int $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Normalize a "sections" payload (ingredients or directions) into the
 * canonical [{header: string|null, items: [string,...]}, ...] shape,
 * dropping empty sections/items and trimming whitespace.
 */
function normalizeSections($raw): array {
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $section) {
        $header = null;
        $items = [];
        if (is_array($section)) {
            if (isset($section['header']) && trim((string)$section['header']) !== '') {
                $header = trim((string)$section['header']);
            }
            if (isset($section['items']) && is_array($section['items'])) {
                foreach ($section['items'] as $item) {
                    $item = trim((string)$item);
                    if ($item !== '') {
                        $items[] = $item;
                    }
                }
            }
        }
        if (!empty($items)) {
            $out[] = ['header' => $header, 'items' => $items];
        }
    }
    return $out;
}

function sectionsToSearchText(array $sections): string {
    $parts = [];
    foreach ($sections as $section) {
        if (!empty($section['header'])) {
            $parts[] = $section['header'];
        }
        foreach ($section['items'] as $item) {
            $parts[] = $item;
        }
    }
    return implode("\n", $parts);
}

function buildSearchText(array $recipe, array $ingredients, array $directions): string {
    $parts = [
        $recipe['title'] ?? '',
        $recipe['serves'] ?? '',
        $recipe['prep_time'] ?? '',
        $recipe['cook_time'] ?? '',
        $recipe['note'] ?? '',
        $recipe['source'] ?? '',
        sectionsToSearchText($ingredients),
        sectionsToSearchText($directions),
    ];
    return implode("\n", array_filter($parts, fn($p) => $p !== null && $p !== ''));
}

function requireFields(array $data, array $fields): ?string {
    foreach ($fields as $f) {
        if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
            return "Missing required field: {$f}";
        }
    }
    return null;
}
