<?php
declare(strict_types=1);
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#171426">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf']) ?>">
  <title>Kto kręci? — imprezowa gra w oszusta</title>
  <link rel="stylesheet" href="assets/style.css">
  <script src="assets/app.js" defer></script>
</head>
<body>
  <main class="app">
    <header><a class="brand" href="./" aria-label="Kto kręci? Strona główna"><span class="brand-icon">✳</span> kto kręci<span class="lime">?</span></a><span class="room" id="room">● POKÓJ</span></header>
    <div id="notice" role="alert" hidden></div>
    <section id="setup" hidden>
      <div class="intro"><span class="eyebrow">JEDEN TELEFON. WIELE PODEJRZEŃ.</span><h1>Ktoś tutaj<br><span class="lime">nie zna hasła.</span> 👀</h1><p>Podaj telefon, rzuć skojarzenie i zdemaskuj oszusta. Tylko nie zdradź za dużo!</p></div>
      <div class="panel"><div class="section-heading"><h2><span>01</span> Wybierz klimat</h2><button class="text-button" id="select-all">Zaznacz wszystkie</button></div><p class="muted">Z jakich kategorii losujemy hasło?</p><div id="categories" class="categories"></div></div>
      <div class="panel"><div class="section-heading"><h2><span>02</span> Zbierz ekipę</h2><span class="count" id="player-count">0 / 20</span></div><p class="muted">Minimum 3 osoby. Im więcej, tym więcej podejrzeń.</p><form id="player-form"><label class="sr-only" for="player-name">Imię gracza</label><input id="player-name" maxlength="30" placeholder="Jak masz na imię?" autocomplete="off" required><button class="add-button" aria-label="Dodaj gracza">＋</button></form><ul id="players"></ul></div>
      <button class="primary" id="new-game">Nowa gra <span>↗</span></button><p class="footnote">1 oszust · tajne karty · zero zaufania</p>
    </section>
    <section id="dealing" class="play-screen" hidden>
      <div class="play-heading"><span class="eyebrow" id="progress"></span><h1 id="player-title"></h1><p id="card-instruction">Przekaż telefon tej osobie. Reszta nie podgląda!</p></div>
      <button class="secret-card" id="card" aria-label="Odkryj swoją kartę"><span id="card-content"></span></button>
      <div class="play-bottom"><button class="primary" id="next-player" disabled>Następny gracz <span>→</span></button><p class="footnote" id="card-hint">Odkryj kartę, zapamiętaj i zakryj.</p></div>
    </section>
    <section id="ready" class="play-screen centered" hidden><div class="big-symbol">✳</div><span class="eyebrow">WSZYSCY ZNAJĄ SWOJĄ ROLĘ</span><h1>Zaczyna się<br><span class="lime">podejrzewanie.</span></h1><p>Po kolei podajcie skojarzenie z hasłem.<br>Oszust improwizuje. Kto się zdradzi?</p><button class="primary" id="start">START <span>▶</span></button></section>
    <section id="timer" class="play-screen centered" hidden><span class="eyebrow" id="timer-status">GRA TRWA · OBSERWUJ EKIPĘ</span><h1>Kto kręci?</h1><div class="clock" id="clock" role="timer">00:00</div><p id="timer-copy">Słuchaj skojarzeń. Wypatruj blefu.</p><button class="primary" id="stop">Stop <span>■</span></button><button class="secondary" id="another-game">Nowa gra <span>↻</span></button><button class="text-button" id="edit-room">Zmień graczy lub kategorie</button></section>
    <footer>MAŁA GRA. WIELKIE PODEJRZENIA. <span>✦</span></footer>
  </main>
</body>
</html>
