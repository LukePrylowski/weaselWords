const $ = (id) => document.getElementById(id);
let state, players = [], selected = new Set(), visible = false, busy = false, timerBase = 0, timerAt = 0;
async function api(action = 'state', body) {
  const response = await fetch(`api.php?action=${action}`, { method: body === undefined ? 'GET' : 'POST', cache: 'no-store', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }, body: body === undefined ? undefined : JSON.stringify(body) });
  const data = await response.json();
  if (!response.ok) throw new Error(data.error || 'Coś poszło nie tak. Spróbuj ponownie.');
  return data;
}
async function run(fn) {
  if (busy) return;
  busy = true; $('notice').hidden = true; document.body.classList.add('busy');
  try { await fn(); } catch (error) { $('notice').textContent = error.message; $('notice').hidden = false; }
  finally { busy = false; document.body.classList.remove('busy'); }
}
function element(tag, text, className) { const node = document.createElement(tag); node.textContent = text; if (className) node.className = className; return node; }
function renderSetup() {
  $('categories').replaceChildren();
  state.catalog.forEach(category => {
    const button = element('button', '', 'category'); button.type = 'button';
    button.setAttribute('aria-pressed', selected.has(category.id));
    button.append(element('span', category.emoji, 'category-emoji'), element('span', category.name), element('span', selected.has(category.id) ? '✓' : '+', 'check'));
    button.onclick = () => { selected.has(category.id) ? selected.delete(category.id) : selected.add(category.id); saveDraft(); renderSetup(); };
    $('categories').append(button);
  });
  $('players').replaceChildren();
  players.forEach((name, index) => {
    const li = element('li', ''); li.append(element('span', String(index + 1).padStart(2, '0'), 'player-number'), element('span', name, 'player-label'));
    const remove = element('button', '×'); remove.setAttribute('aria-label', `Usuń gracza ${name}`); remove.onclick = () => { players.splice(index, 1); saveDraft(); renderSetup(); }; li.append(remove); $('players').append(li);
  });
  $('player-count').textContent = `${players.length} / 20`;
  $('new-game').disabled = players.length < 3 || selected.size === 0;
  $('select-all').textContent = selected.size === state.catalog.length ? 'Odznacz wszystkie' : 'Zaznacz wszystkie';
}
function saveDraft() { try { sessionStorage.setItem('party-draft', JSON.stringify({ players, categories: [...selected] })); } catch {} }
function cover() {
  visible = false; $('card').classList.remove('revealed', 'impostor'); $('card').setAttribute('aria-label', 'Odkryj swoją kartę');
  $('card-content').replaceChildren(element('span', '✳', 'card-symbol'), element('strong', 'Twoja tajna karta'), element('span', 'Dotknij, żeby odkryć', 'card-caption'));
}
function render() {
  for (const id of ['setup', 'dealing', 'ready', 'timer']) $(id).hidden = true;
  $('room').textContent = `● POKÓJ ${state.code}`;
  document.body.classList.toggle('playing', state.phase !== 'setup');
  if (state.phase === 'setup') { $('setup').hidden = false; renderSetup(); }
  if (state.phase === 'dealing') {
    $('dealing').hidden = false; $('progress').textContent = `TAJNA KARTA · ${state.index + 1} / ${state.players.length}`; $('player-title').textContent = state.players[state.index]; cover();
    $('next-player').disabled = !state.seen; $('next-player').firstChild.textContent = state.index === state.players.length - 1 ? 'Wszyscy gotowi ' : 'Następny gracz ';
  }
  if (state.phase === 'ready') $('ready').hidden = false;
  if (['running', 'stopped'].includes(state.phase)) {
    $('timer').hidden = false; timerBase = state.elapsed; timerAt = performance.now(); tick();
    $('stop').hidden = state.phase === 'stopped'; $('timer-status').textContent = state.phase === 'running' ? 'GRA TRWA · OBSERWUJ EKIPĘ' : 'CZAS ZATRZYMANY';
    $('timer-copy').textContent = state.phase === 'running' ? 'Słuchaj skojarzeń. Wypatruj blefu.' : 'Czas na werdykt. Kogo podejrzewacie?';
  }
}
function tick() {
  if (!state || !['running', 'stopped'].includes(state.phase)) return;
  const seconds = Math.floor(timerBase + (state.phase === 'running' ? (performance.now() - timerAt) / 1000 : 0));
  $('clock').textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
}
async function change(action, payload = {}) { state = await api(action, payload); render(); }
$('player-form').onsubmit = (event) => { event.preventDefault(); const name = $('player-name').value.trim(); if (!name) return; if (players.length >= 20 || players.some(p => p.toLocaleLowerCase('pl') === name.toLocaleLowerCase('pl'))) { $('player-name').setCustomValidity('Wpisz inne imię. Maksymalnie 20 graczy.'); $('player-name').reportValidity(); return; } players.push(name); $('player-name').value = ''; saveDraft(); renderSetup(); $('player-name').focus(); };
$('player-name').oninput = () => $('player-name').setCustomValidity('');
$('select-all').onclick = () => { selected = selected.size === state.catalog.length ? new Set() : new Set(state.catalog.map(c => c.id)); saveDraft(); renderSetup(); };
const newGame = () => run(() => change('new', { players, categories: [...selected] }));
$('new-game').onclick = newGame; $('another-game').onclick = newGame;
$('card').onclick = () => run(async () => {
  if (visible) { await change('hide'); return; }
  const secret = await api('reveal', {}); visible = true; state.seen = true;
  $('card').classList.add('revealed'); $('card').classList.toggle('impostor', secret.role === 'impostor'); $('card').setAttribute('aria-label', 'Zakryj swoją kartę');
  $('card-content').replaceChildren(element('span', secret.role === 'impostor' ? '🤫' : '✦', 'card-symbol'), element('span', secret.role === 'impostor' ? 'BLEFUJ. NIE DAJ SIĘ ZŁAPAĆ.' : 'TWOJE HASŁO', 'eyebrow'), element('strong', secret.word, 'secret-word'));
  if (secret.hint) $('card-content').append(element('span', `Podpowiedź: ${secret.hint}`, 'secret-hint'));
  $('card-content').append(element('span', 'Dotknij, żeby zakryć', 'card-caption')); $('next-player').disabled = true;
});
$('next-player').onclick = () => run(() => change('next'));
$('start').onclick = () => run(() => change('start'));
$('stop').onclick = () => run(() => change('stop'));
$('edit-room').onclick = () => run(() => change('setup'));
document.addEventListener('visibilitychange', () => { if (document.hidden && visible) { cover(); $('next-player').disabled = true; } });
setInterval(tick, 250);
run(async () => {
  state = await api(); players = [...state.players]; selected = new Set(state.categories);
  if (state.phase === 'setup') { try { const draft = JSON.parse(sessionStorage.getItem('party-draft')); if (draft && Array.isArray(draft.players) && Array.isArray(draft.categories)) { players = draft.players.filter(p => typeof p === 'string').slice(0, 20); selected = new Set(draft.categories); } } catch {} }
  selected = new Set([...selected].filter(id => state.catalog.some(c => c.id === id)));
  if (!state.catalog.length) throw new Error('Brak kategorii. Dodaj poprawne pliki do data/categories i odśwież stronę.');
  render();
});
