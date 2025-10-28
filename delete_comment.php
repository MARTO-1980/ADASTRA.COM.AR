<?php
declare(strict_types=1);
require __DIR__ . '/comments_store.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    send_json(['success' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    send_json(['success' => false, 'error' => 'Invalid JSON body'], 400);
}

$tattoo_id = isset($data['tattoo_id']) ? trim((string)$data['tattoo_id']) : '';
$comment_id = isset($data['comment_id']) ? trim((string)$data['comment_id']) : '';
$user_email = isset($data['user_email']) ? trim((string)$data['user_email']) : '';

if ($tattoo_id === '' || $comment_id === '' || $user_email === '') {
    send_json(['success' => false, 'error' => 'Missing required fields'], 400);
}

$store = read_store();
if (!isset($store[$tattoo_id]) || !is_array($store[$tattoo_id])) {
    send_json(['success' => false, 'error' => 'Comment not found'], 404);
}

$comments = $store[$tattoo_id];
$found = false;
$new = [];

foreach ($comments as $c) {
    if (($c['id'] ?? '') === $comment_id) {
        if (($c['user_email'] ?? '') !== $user_email) {
            send_json(['success' => false, 'error' => 'Not authorized to delete this comment'], 403);
        }
        $found = true;
        // skip delete
        continue;
    }
    $new[] = $c;
}

if (!$found) {
    send_json(['success' => false, 'error' => 'Comment not found'], 404);
}

$store[$tattoo_id] = $new;
if (!write_store($store)) {
    send_json(['success' => false, 'error' => 'Failed to persist deletion'], 500);
}

send_json(['success' => true]);
