<?php
declare(strict_types=1);
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function fail(string $message, int $status = 400): never { http_response_code($status); echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE); exit; }
function catalog(): array {
    $result = [];
    foreach (glob(__DIR__ . '/data/categories/*.json') ?: [] as $path) {
        try { $item = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
        catch (Throwable $e) { error_log('Invalid category: ' . basename($path)); continue; }
        if (!is_array($item) || !is_string($item['name'] ?? null) || !is_array($item['words'] ?? null)) continue;
        $words = array_values(array_filter($item['words'], fn($w) => is_array($w) && is_string($w['word'] ?? null) && trim($w['word']) !== '' && is_string($w['hint'] ?? null) && trim($w['hint']) !== ''));
        if ($words) $result[pathinfo($path, PATHINFO_FILENAME)] = ['name' => $item['name'], 'emoji' => is_string($item['emoji'] ?? null) ? $item['emoji'] : '✦', 'words' => $words];
    }
    return $result;
}
$_SESSION['room'] ??= ['code' => strtoupper(bin2hex(random_bytes(3))), 'players' => [], 'categories' => [], 'phase' => 'setup'];
$room =& $_SESSION['room'];
$catalog = catalog();
$action = $_GET['action'] ?? 'state';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) fail('Odśwież stronę i spróbuj ponownie.', 403);
    try { $input = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR); } catch (Throwable $e) { fail('Nieprawidłowe dane.'); }
    if (!is_array($input)) fail('Nieprawidłowe dane.');
    if ($action === 'new') {
        $players = $input['players'] ?? null; $categories = $input['categories'] ?? null;
        if (!is_array($players) || count($players) < 3 || count($players) > 20) fail('Dodaj od 3 do 20 graczy.');
        foreach ($players as &$player) { if (!is_string($player) || strlen($player) > 120 || trim($player) === '') fail('Sprawdź imiona graczy.'); $player = trim($player); } unset($player);
        if (count(array_unique(array_map(fn($p) => function_exists('mb_strtolower') ? mb_strtolower($p) : strtolower($p), $players))) !== count($players)) fail('Każdy gracz musi mieć inne imię.');
        if (!is_array($categories) || !$categories) fail('Wybierz przynajmniej jedną kategorię.');
        $pool = [];
        foreach ($categories as $id) { if (!is_string($id) || !isset($catalog[$id])) fail('Lista kategorii się zmieniła. Odśwież stronę.'); foreach ($catalog[$id]['words'] as $word) $pool[] = $word; }
        if (!$pool) fail('Brak haseł w wybranych kategoriach.');
        $previous = $room['secret']['word'] ?? null;
        $fresh = array_values(array_filter($pool, fn($w) => $w['word'] !== $previous));
        if ($fresh) $pool = $fresh;
        // Draw a fresh dealing order for every round and keep it in the session.
        $players = array_values($players);
        for ($i = count($players) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$players[$i], $players[$j]] = [$players[$j], $players[$i]];
        }
        $room = ['code' => $room['code'], 'players' => array_values($players), 'categories' => array_values(array_unique($categories)), 'phase' => 'dealing', 'index' => 0, 'seen' => false, 'visible' => false, 'secret' => $pool[random_int(0, count($pool) - 1)], 'impostor' => random_int(0, count($players) - 1)];
    } elseif ($action === 'reveal') {
        if ($room['phase'] !== 'dealing') fail('Ta runda nie ma aktywnej karty.');
        $room['seen'] = true; $room['visible'] = true;
        echo json_encode($room['index'] === $room['impostor'] ? ['role' => 'impostor', 'word' => 'OSZUST', 'hint' => $room['secret']['hint']] : ['role' => 'player', 'word' => $room['secret']['word']], JSON_UNESCAPED_UNICODE); exit;
    } elseif ($action === 'hide') {
        if ($room['phase'] !== 'dealing') fail('Brak aktywnej karty.'); $room['visible'] = false;
    } elseif ($action === 'next') {
        if ($room['phase'] !== 'dealing' || !$room['seen'] || $room['visible']) fail('Najpierw przeczytaj i zakryj kartę.');
        $room['index']++; $room['seen'] = false;
        if ($room['index'] >= count($room['players'])) $room['phase'] = 'ready';
    } elseif ($action === 'start') {
        if ($room['phase'] !== 'ready') fail('Najpierw pokaż karty wszystkim graczom.');
        $room['phase'] = 'running'; $room['started'] = microtime(true);
    } elseif ($action === 'stop') {
        if ($room['phase'] !== 'running') fail('Licznik nie jest uruchomiony.');
        $room['elapsed'] = microtime(true) - $room['started']; $room['phase'] = 'stopped';
    } elseif ($action === 'setup') { $room['phase'] = 'setup'; unset($room['secret'], $room['impostor']); }
    else fail('Nieznana operacja.');
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET' || $action !== 'state') fail('Niedozwolona metoda.', 405);
// A reload always covers the current card. Secret data never appears in room state.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $room['phase'] === 'dealing') $room['visible'] = false;
$public = array_intersect_key($room, array_flip(['code', 'players', 'categories', 'phase', 'index', 'seen']));
$public['elapsed'] = $room['phase'] === 'running' ? microtime(true) - $room['started'] : ($room['elapsed'] ?? 0);
$public['catalog'] = [];
foreach ($catalog as $id => $item) $public['catalog'][] = ['id' => $id, 'name' => $item['name'], 'emoji' => $item['emoji'], 'count' => count($item['words'])];
echo json_encode($public, JSON_UNESCAPED_UNICODE);
