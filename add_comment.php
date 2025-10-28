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
$user_email = isset($data['user_email']) ? trim((string)$data['user_email']) : '';
$user_name = isset($data['user_name']) ? trim((string)$data['user_name']) : '';
$comment_text = isset($data['comment_text']) ? trim((string)$data['comment_text']) : '';

if ($tattoo_id === '' || $user_email === '' || $user_name === '' || $comment_text === '') {
    send_json(['success' => false, 'error' => 'Missing required fields'], 400);
}

if (mb_strlen($comment_text) > 1000) {
    send_json(['success' => false, 'error' => 'Comment too long'], 400);
}

$store = read_store();
if (!isset($store[$tattoo_id]) || !is_array($store[$tattoo_id])) {
    $store[$tattoo_id] = [];
}

$comment = [
    'id' => generate_comment_id(),
    'user_email' => $user_email,
    'user_name' => $user_name,
    'comment_text' => $comment_text,
    'comment_date' => gmdate('c'),
];

$store[$tattoo_id][] = $comment;

if (!write_store($store)) {
    send_json(['success' => false, 'error' => 'Failed to persist comment'], 500);
}

send_json(['success' => true, 'comment' => $comment]);
