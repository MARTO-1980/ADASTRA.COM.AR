<?php
declare(strict_types=1);
require __DIR__ . '/comments_store.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    send_json(['success' => false, 'error' => 'Method not allowed'], 405);
}

$tattoo_id = isset($_GET['tattoo_id']) ? trim((string)$_GET['tattoo_id']) : '';
if ($tattoo_id === '') {
    // Return empty array to simplify client logic
    send_json([]);
}

$store = read_store();
$comments = $store[$tattoo_id] ?? [];

usort($comments, function($a, $b) {
    return strcmp($a['comment_date'] ?? '', $b['comment_date'] ?? '');
});

send_json($comments);
