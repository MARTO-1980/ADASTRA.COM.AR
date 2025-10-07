<?php
// get_comments.php - Retrieve comments for a tattoo

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function send_json($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function comments_file_path(string $tattooId): string {
    $hash = sha1($tattooId);
    return __DIR__ . '/data/c_' . $hash . '.json';
}

$tattooId = isset($_GET['tattoo_id']) ? (string)$_GET['tattoo_id'] : '';
if ($tattooId === '') {
    send_json([]); // empty list if missing
}

$file = comments_file_path($tattooId);
if (!file_exists($file)) {
    send_json([]);
}

$raw = file_get_contents($file);
if ($raw === false || $raw === '') {
    send_json([]);
}

$decoded = json_decode($raw, true);
if (!is_array($decoded)) {
    send_json([]);
}

// Sort by date ascending
usort($decoded, function($a, $b) {
    return strcmp($a['comment_date'] ?? '', $b['comment_date'] ?? '');
});

send_json($decoded);
