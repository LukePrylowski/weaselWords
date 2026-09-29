<?php
declare(strict_types=1);
require_once __DIR__ . '/game-stats.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Niedozwolona metoda.']);
    exit;
}
try {
    echo json_encode(['gamesPlayed' => gamesPlayed()]);
} catch (Throwable $error) {
    error_log('Game counter: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Licznik jest chwilowo niedostępny.']);
}
