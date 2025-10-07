<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function cors(): void {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Vary: Origin');
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

cors();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const STORE_FILE = __DIR__ . '/comments.json';

function read_store(): array {
    if (!file_exists(STORE_FILE)) {
        return [];
    }
    $contents = file_get_contents(STORE_FILE);
    if ($contents === false || $contents === '') {
        return [];
    }
    $data = json_decode($contents, true);
    if (!is_array($data)) {
        return [];
    }
    return $data;
}

function write_store(array $data): bool {
    $tmp = tempnam(sys_get_temp_dir(), 'comments_');
    if ($tmp === false) {
        return false;
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        @unlink($tmp);
        return false;
    }
    $bytes = file_put_contents($tmp, $json, LOCK_EX);
    if ($bytes === false) {
        @unlink($tmp);
        return false;
    }
    $ok = rename($tmp, STORE_FILE);
    if (!$ok) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function generate_comment_id(): string {
    try {
        return bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        return uniqid('c_', true);
    }
}

function send_json($payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
