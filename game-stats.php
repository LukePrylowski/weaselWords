<?php
declare(strict_types=1);

function gamesPlayed(bool $increment = false): int {
    $path = __DIR__ . '/data/games-played.json';
    $lock = fopen($path . '.lock', 'c');
    if ($lock === false) throw new RuntimeException('Cannot open game counter lock.');
    $temporary = null;
    try {
        if (!flock($lock, $increment ? LOCK_EX : LOCK_SH)) throw new RuntimeException('Cannot lock game counter.');
        $count = 0;
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_int($data['gamesPlayed'] ?? null) || $data['gamesPlayed'] < 0) throw new RuntimeException('Invalid game counter.');
            $count = $data['gamesPlayed'];
        }
        if ($increment) {
            $count++;
            $temporary = tempnam(dirname($path), 'games-played-');
            if ($temporary === false) throw new RuntimeException('Cannot create game counter.');
            $json = json_encode(['gamesPlayed' => $count], JSON_THROW_ON_ERROR) . "\n";
            if (file_put_contents($temporary, $json) !== strlen($json) || !rename($temporary, $path)) {
                throw new RuntimeException('Cannot save game counter.');
            }
        }
        return $count;
    } finally {
        if (is_string($temporary) && file_exists($temporary)) unlink($temporary);
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
