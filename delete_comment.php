<?php
// delete_comment.php - Delete a comment by id for a tattoo if requester matches email

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

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

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    send_json(['success' => false, 'error' => 'Empty request body'], 400);
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    send_json(['success' => false, 'error' => 'Invalid JSON'], 400);
}

$tattooId  = isset($payload['tattoo_id']) ? (string)$payload['tattoo_id'] : '';
$commentId = isset($payload['comment_id']) ? (string)$payload['comment_id'] : '';
$requester = isset($payload['user_email']) ? (string)$payload['user_email'] : '';

if ($tattooId === '' || $commentId === '' || $requester === '') {
    send_json(['success' => false, 'error' => 'Missing required fields'], 400);
}

if (!filter_var($requester, FILTER_VALIDATE_EMAIL)) {
    send_json(['success' => false, 'error' => 'Invalid email'], 400);
}

$file = comments_file_path($tattooId);
if (!file_exists($file)) {
    send_json(['success' => false, 'error' => 'Not found'], 404);
}

$rawComments = file_get_contents($file);
$comments = json_decode($rawComments, true);
if (!is_array($comments)) {
    send_json(['success' => false, 'error' => 'Corrupted data'], 500);
}

$found = false;
$updated = [];
foreach ($comments as $c) {
    if (($c['id'] ?? '') === $commentId) {
        if (strcasecmp($c['user_email'] ?? '', $requester) !== 0) {
            send_json(['success' => false, 'error' => 'Forbidden'], 403);
        }
        $found = true;
        continue; // skip to delete
    }
    $updated[] = $c;
}

if (!$found) {
    send_json(['success' => false, 'error' => 'Comment not found'], 404);
}

$tmp = json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($tmp === false) {
    send_json(['success' => false, 'error' => 'Failed to encode comments'], 500);
}

if (file_put_contents($file, $tmp, LOCK_EX) === false) {
    send_json(['success' => false, 'error' => 'Failed to persist changes'], 500);
}

send_json(['success' => true]);
