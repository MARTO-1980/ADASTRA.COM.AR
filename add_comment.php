<?php
// add_comment.php - Save a new comment for a tattoo

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

function ensure_data_dir(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function comments_file_path(string $tattooId): string {
    // Avoid unsafe filenames by hashing the identifier
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

$tattooId   = isset($payload['tattoo_id']) ? (string)$payload['tattoo_id'] : '';
$userEmail  = isset($payload['user_email']) ? (string)$payload['user_email'] : '';
$userName   = isset($payload['user_name']) ? (string)$payload['user_name'] : '';
$commentTxt = isset($payload['comment_text']) ? (string)$payload['comment_text'] : '';

if ($tattooId === '' || $userEmail === '' || $userName === '' || $commentTxt === '') {
    send_json(['success' => false, 'error' => 'Missing required fields'], 400);
}

// Basic validation
if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    send_json(['success' => false, 'error' => 'Invalid email'], 400);
}

// Normalize strings
$userName = trim($userName);
$commentTxt = trim($commentTxt);

$dir = __DIR__ . '/data';
ensure_data_dir($dir);
$file = comments_file_path($tattooId);

$comment = [
    'id' => bin2hex(random_bytes(8)),
    'user_name' => $userName,
    'user_email' => $userEmail,
    'comment_text' => $commentTxt,
    'comment_date' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTime::ATOM),
];

$comments = [];
if (file_exists($file)) {
    $existing = file_get_contents($file);
    if ($existing !== false && $existing !== '') {
        $decoded = json_decode($existing, true);
        if (is_array($decoded)) {
            $comments = $decoded;
        }
    }
}

$comments[] = $comment;

// Persist with simple lock to reduce race risk
$tmp = json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($tmp === false) {
    send_json(['success' => false, 'error' => 'Failed to encode comments'], 500);
}

if (file_put_contents($file, $tmp, LOCK_EX) === false) {
    send_json(['success' => false, 'error' => 'Failed to save comment'], 500);
}

send_json(['success' => true, 'comment' => $comment]);
