<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/user_service.php';

requireLogin();

$userId = currentUserId();
if ($userId <= 0) {
    legacy_redirect('login.php');
}

$initialTutorialId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>StockMaster — Oktatóanyagok</title>
<link rel="stylesheet" href="app.css?v=1">
<style>
:root{
  --bg:#0f1724;
  --panel:#0b1220;
  --panel2:#0e1728;
  --text:#e6eef8;
  --muted:#98a2b3;
  --line:rgba(255,255,255,.08);
  --glass:rgba(255,255,255,.03);
  --green:#22c55e;
  --green2:#86efac;
  --blue:#60a5fa;
  --blue2:#93c5fd;
  --amber:#f59e0b;
  --shadow:0 10px 30px rgba(2,6,23,.55);
}
*{box-sizing:border-box}
html,body{
  margin:0;
  min-height:100%;
  font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;
  background:linear-gradient(180deg,var(--bg),#041025);
  color:var(--text);
}
.wrap{
  max-width:1400px;
  margin:0 auto;
  padding:20px 18px 28px;
}
.topbar{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:14px;
  margin-bottom:16px;
}
.h1{
  margin:0;
  font-size:30px;
  font-weight:900;
}
.sub{
  margin-top:6px;
  color:var(--muted);
  font-size:13px;
}
.topActions{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  text-decoration:none;
  border-radius:12px;
  padding:10px 14px;
  font-weight:900;
  cursor:pointer;
  border:1px solid var(--line);
  background:var(--glass);
  color:var(--text);
}
.btnPrimary{
  background:linear-gradient(90deg,var(--green),var(--green2));
  color:#03210a;
  border:none;
}
.btnBlue{
  background:linear-gradient(90deg,rgba(96,165,250,.2),rgba(59,130,246,.1));
  border:1px solid rgba(96,165,250,.25);
}
.layout{
  display:grid;
  grid-template-columns:360px 1fr;
  gap:16px;
}
.sideCard,.mainCard{
  background:var(--panel);
  border:1px solid var(--line);
  border-radius:20px;
  box-shadow:var(--shadow);
}
.sideCard{
  padding:16px;
  display:flex;
  flex-direction:column;
  min-height:78vh;
}
.mainCard{
  padding:18px;
  min-height:78vh;
}
.statGrid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
  margin-bottom:14px;
}
.stat{
  border:1px solid var(--line);
  background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(0,0,0,.04));
  border-radius:16px;
  padding:12px;
}
.statLabel{
  color:var(--muted);
  font-size:12px;
  font-weight:800;
  margin-bottom:6px;
}
.statValue{
  font-size:26px;
  font-weight:900;
}
.progressOuter{
  width:100%;
  height:10px;
  border-radius:999px;
  overflow:hidden;
  background:rgba(255,255,255,.05);
  border:1px solid var(--line);
  margin-top:8px;
}
.progressInner{
  height:100%;
  width:0%;
  border-radius:999px;
  background:linear-gradient(90deg,var(--green),var(--blue));
  transition:width .2s ease;
}
.filterTabs{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  margin-bottom:14px;
}
.filterBtn{
  border:1px solid var(--line);
  background:var(--glass);
  color:var(--text);
  border-radius:999px;
  padding:9px 12px;
  cursor:pointer;
  font-weight:900;
}
.filterBtn.active{
  background:linear-gradient(90deg,rgba(96,165,250,.18),rgba(34,197,94,.15));
  border-color:rgba(96,165,250,.25);
}
.lessonList{
  flex:1;
  overflow:auto;
  padding-right:4px;
}
.lessonItem{
  border:1px solid var(--line);
  border-radius:16px;
  background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(0,0,0,.04));
  padding:12px;
  margin-bottom:10px;
  cursor:pointer;
  transition:.18s ease;
}
.lessonItem:hover{
  transform:translateY(-1px);
}
.lessonItem.active{
  border-color:rgba(96,165,250,.35);
  box-shadow:0 0 0 1px rgba(96,165,250,.18) inset;
}
.lessonTitle{
  font-weight:900;
  font-size:14px;
  margin-bottom:8px;
  line-height:1.3;
}
.lessonMeta{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}
.pill{
  display:inline-flex;
  align-items:center;
  padding:6px 10px;
  border-radius:999px;
  background:var(--glass);
  border:1px solid var(--line);
  font-size:11px;
  font-weight:900;
}
.pillDone{ color:#bbf7d0; background:rgba(34,197,94,.12); }
.pillWork{ color:#fde68a; background:rgba(245,158,11,.12); }
.pillIdle{ color:#cbd5e1; background:rgba(148,163,184,.08); }

.playerHead{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:14px;
  margin-bottom:12px;
}
.playerTitle{
  margin:0;
  font-size:32px;
  font-weight:900;
  line-height:1.15;
}
.playerSub{
  margin-top:8px;
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}
.tag{
  display:inline-flex;
  align-items:center;
  padding:7px 10px;
  border-radius:999px;
  border:1px solid var(--line);
  background:var(--glass);
  font-size:12px;
  font-weight:900;
}
.lessonStatus{
  min-width:180px;
  display:flex;
  flex-direction:column;
  gap:8px;
}
.smallMuted{
  font-size:12px;
  color:var(--muted);
}
.stepBox{
  margin-top:18px;
  border:1px solid var(--line);
  background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(0,0,0,.04));
  border-radius:18px;
  padding:18px;
}
.stepTop{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  flex-wrap:wrap;
  margin-bottom:12px;
}
.stepTitle{
  margin:0;
  font-size:20px;
  font-weight:900;
}
.stepContent{
  color:rgba(230,238,248,.92);
  font-size:18px;
  line-height:1.8;
  white-space:pre-line;
}
.checkpoint{
  margin-top:18px;
  border:1px solid rgba(96,165,250,.18);
  background:linear-gradient(180deg,rgba(96,165,250,.08),rgba(0,0,0,.04));
  border-radius:16px;
  padding:14px;
}
.checkpointTitle{
  font-weight:900;
  margin-bottom:10px;
}
.checkRow{
  display:flex;
  flex-direction:column;
  gap:10px;
}
.checkItem{
  display:flex;
  gap:10px;
  align-items:flex-start;
  color:rgba(230,238,248,.92);
  font-size:14px;
}
.notesBox{
  margin-top:18px;
}
.notesLabel{
  display:block;
  font-size:13px;
  color:var(--muted);
  font-weight:800;
  margin-bottom:8px;
}
.notesArea{
  width:100%;
  min-height:110px;
  resize:vertical;
  border-radius:14px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.03);
  color:var(--text);
  padding:12px 14px;
  font:inherit;
}
.playerActions{
  margin-top:18px;
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.stepNav{
  margin-top:18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  flex-wrap:wrap;
}
.emptyState{
  border:1px dashed rgba(255,255,255,.15);
  border-radius:16px;
  padding:24px;
  color:var(--muted);
  font-size:15px;
  text-align:center;
}
.stepIndicator{
  font-size:13px;
  color:var(--muted);
  font-weight:800;
}
.finishCard{
  margin-top:18px;
  border:1px solid rgba(34,197,94,.2);
  background:linear-gradient(180deg,rgba(34,197,94,.08),rgba(0,0,0,.04));
  border-radius:18px;
  padding:18px;
}
.finishTitle{
  margin:0 0 10px;
  font-size:22px;
  font-weight:900;
}
.finishList{
  margin:0;
  padding-left:18px;
  color:rgba(230,238,248,.92);
  line-height:1.8;
  font-size:15px;
}
@media (max-width: 1100px){
  .layout{ grid-template-columns:1fr; }
  .sideCard,.mainCard{ min-height:auto; }
}
@media (max-width: 720px){
  .playerHead{ flex-direction:column; }
  .playerTitle{ font-size:26px; }
  .statGrid{ grid-template-columns:1fr; }
}
</style>
</head>
<body>
<div class="wrap">
  <div class="topbar">
    <div>
      <h1 class="h1">StockMaster Oktatóanyagok</h1>
      <div class="sub">Itt már nem csak státuszt látsz, hanem végig is tudsz menni a leckéken lépésről lépésre.</div>
    </div>
    <div class="topActions">
      <a class="btn" href="settings.php">← Vissza a beállításokhoz</a>
      <button class="btn btnBlue" id="refreshAllBtn">Frissítés</button>
    </div>
  </div>

  <div class="layout">
    <aside class="sideCard">
      <div class="statGrid">
        <div class="stat">
          <div class="statLabel">Összes haladás</div>
          <div class="statValue" id="overallPercent">0%</div>
          <div class="progressOuter"><div class="progressInner" id="overallBar"></div></div>
        </div>
        <div class="stat">
          <div class="statLabel">Aktív lecke</div>
          <div class="statValue" id="activeLevelLabel">—</div>
          <div class="smallMuted" id="activeStepLabel">Válassz leckét</div>
        </div>
      </div>

      <div class="filterTabs">
        <button class="filterBtn active" data-level="0">Összes</button>
        <button class="filterBtn" data-level="1">Kezdő</button>
        <button class="filterBtn" data-level="2">Haladó</button>
        <button class="filterBtn" data-level="3">Profi</button>
      </div>

      <div class="lessonList" id="lessonList">
        <div class="emptyState">Betöltés…</div>
      </div>
    </aside>

    <main class="mainCard">
      <div id="playerRoot">
        <div class="emptyState">Betöltés…</div>
      </div>
    </main>
  </div>
</div>

<script>
const USER_ID = <?= (int) $userId ?>;
const INITIAL_TUTORIAL_ID = <?= (int) $initialTutorialId ?>;

const lessonList = document.getElementById('lessonList');
const playerRoot = document.getElementById('playerRoot');
const overallPercent = document.getElementById('overallPercent');
const overallBar = document.getElementById('overallBar');
const activeLevelLabel = document.getElementById('activeLevelLabel');
const activeStepLabel = document.getElementById('activeStepLabel');
const refreshAllBtn = document.getElementById('refreshAllBtn');

let tutorials = [];
let progressData = null;
let activeTutorial = null;
let currentFilterLevel = 0;
let currentStepIndex = 0;

function esc(s){
  return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
function difficultyLabel(level){
  return level === 1 ? 'Kezdő' : level === 2 ? 'Haladó' : level === 3 ? 'Profi' : 'Összes';
}
function statusLabel(status){
  if (status === 'completed') return 'Kész';
  if (status === 'in_progress') return 'Folyamatban';
  return 'Nincs elkezdve';
}
function statusClass(status){
  if (status === 'completed') return 'pillDone';
  if (status === 'in_progress') return 'pillWork';
  return 'pillIdle';
}
function percent(v){
  return Math.max(0, Math.min(100, Number(v || 0)));
}
function fmtAny(v){
  if (!v) return '—';
  try{
    const d = new Date(v);
    if (isNaN(d.getTime())) return String(v);
    return d.toLocaleString('hu-HU');
  }catch(e){
    return String(v);
  }
}
function notesKey(id){ return `sm_tutorial_notes_${USER_ID}_${id}`; }
function stepKey(id){ return `sm_tutorial_step_${USER_ID}_${id}`; }

function splitContentToSteps(content){
  const raw = String(content || '').trim();
  if (!raw) return ['Nincs tartalom.'];

  let parts = raw
    .split(/\n\s*\n/)
    .map(s => s.trim())
    .filter(Boolean);

  if (parts.length < 2) {
    const sentences = raw
      .split(/(?<=[\.\!\?])\s+/)
      .map(s => s.trim())
      .filter(Boolean);

    parts = [];
    let bucket = [];
    for (const sentence of sentences) {
      bucket.push(sentence);
      if (bucket.length >= 2) {
        parts.push(bucket.join(' '));
        bucket = [];
      }
    }
    if (bucket.length) parts.push(bucket.join(' '));
  }

  if (parts.length > 6) {
    parts = parts.slice(0, 6);
  }

  return parts.length ? parts : [raw];
}

async function fetchJson(url, options = {}){
  const r = await fetch(url, options);
  const data = await r.json();
  if (!r.ok || data?.ok === false) {
    throw new Error(data?.message || data?.error || 'Szerverhiba.');
  }
  return data;
}

async function loadProgress(){
  const data = await fetchJson(`/api/tutorials/progress?user_id=${USER_ID}`, { cache: 'no-store' });
  progressData = data.data || null;

  const p = percent(progressData?.percent || 0);
  overallPercent.textContent = `${p}%`;
  overallBar.style.width = `${p}%`;
}

async function loadTutorials(){
  const data = await fetchJson(`/api/tutorials?user_id=${USER_ID}`, { cache: 'no-store' });
  tutorials = Array.isArray(data.data) ? data.data.slice() : [];
}

function renderLessonList(){
  const filtered = tutorials.filter(t => currentFilterLevel === 0 || Number(t.difficulty_code) === currentFilterLevel);

  if (!filtered.length) {
    lessonList.innerHTML = `<div class="emptyState">Ehhez a szinthez nincs lecke.</div>`;
    return;
  }

  lessonList.innerHTML = filtered.map(t => `
    <div class="lessonItem ${activeTutorial && Number(activeTutorial.id) === Number(t.id) ? 'active' : ''}" onclick="openTutorial(${Number(t.id)})">
      <div class="lessonTitle">${esc(t.title)}</div>
      <div class="lessonMeta">
        <span class="pill">${esc(t.difficulty)}</span>
        <span class="pill ${statusClass(t.status)}">${esc(statusLabel(t.status))}</span>
      </div>
    </div>
  `).join('');
}

function updateTopLessonMeta(stepsCount){
  if (!activeTutorial) {
    activeLevelLabel.textContent = '—';
    activeStepLabel.textContent = 'Válassz leckét';
    return;
  }
  activeLevelLabel.textContent = activeTutorial.difficulty || difficultyLabel(Number(activeTutorial.difficulty_code || 0));
  activeStepLabel.textContent = `Lépés ${currentStepIndex + 1} / ${stepsCount}`;
}

async function startTutorialIfNeeded(id){
  const t = tutorials.find(x => Number(x.id) === Number(id));
  if (!t) return;

  if (t.status === 'not_started') {
    await fetchJson('/api/tutorials/start', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        user_id: USER_ID,
        tutorial_id: id
      })
    });

    await Promise.all([loadTutorials(), loadProgress()]);
  }
}

function getCurrentTutorialIndex(){
  if (!activeTutorial) return -1;
  return tutorials.findIndex(t => Number(t.id) === Number(activeTutorial.id));
}

function renderPlayer(){
  if (!activeTutorial) {
    playerRoot.innerHTML = `<div class="emptyState">Válassz egy leckét a bal oldali listából.</div>`;
    return;
  }

  const steps = splitContentToSteps(activeTutorial.content);
  if (currentStepIndex < 0) currentStepIndex = 0;
  if (currentStepIndex >= steps.length) currentStepIndex = steps.length - 1;

  const lessonProgress = steps.length > 0 ? Math.round(((currentStepIndex + 1) / steps.length) * 100) : 0;
  const stepText = steps[currentStepIndex] || '';
  const tags = Array.isArray(activeTutorial.tags) ? activeTutorial.tags : [];
  const savedNotes = localStorage.getItem(notesKey(activeTutorial.id)) || '';
  const isLast = currentStepIndex === steps.length - 1;

  updateTopLessonMeta(steps.length);

  playerRoot.innerHTML = `
    <div class="playerHead">
      <div>
        <h2 class="playerTitle">${esc(activeTutorial.title)}</h2>
        <div class="playerSub">
          <span class="tag">${esc(activeTutorial.difficulty || '')}</span>
          <span class="tag ${statusClass(activeTutorial.status)}">${esc(statusLabel(activeTutorial.status))}</span>
          ${tags.map(tag => `<span class="tag">${esc(tag)}</span>`).join('')}
        </div>
      </div>

      <div class="lessonStatus">
        <div class="smallMuted">Lecke haladás</div>
        <div style="font-size:28px;font-weight:900;">${lessonProgress}%</div>
        <div class="progressOuter"><div class="progressInner" style="width:${lessonProgress}%"></div></div>
        <div class="smallMuted">Indítva: ${esc(fmtAny(activeTutorial.started_at))}</div>
        <div class="smallMuted">Befejezve: ${esc(fmtAny(activeTutorial.completed_at))}</div>
      </div>
    </div>

    <div class="stepBox">
      <div class="stepTop">
        <h3 class="stepTitle">Lecke rész ${currentStepIndex + 1}</h3>
        <div class="stepIndicator">${currentStepIndex + 1} / ${steps.length}</div>
      </div>

      <div class="stepContent">${esc(stepText)}</div>

      <div class="checkpoint">
        <div class="checkpointTitle">Mini checkpoint</div>
        <div class="checkRow">
          <label class="checkItem"><input type="checkbox"> Értem ennek a résznek a fő mondanivalóját.</label>
          <label class="checkItem"><input type="checkbox"> Élő charton / kereskedési helyzetben is fel tudnám ismerni.</label>
          <label class="checkItem"><input type="checkbox"> Tudom, mi lenne itt a tipikus beginner hiba.</label>
        </div>
      </div>

      <div class="notesBox">
        <label class="notesLabel" for="lessonNotes">Saját jegyzet / tanulság</label>
        <textarea class="notesArea" id="lessonNotes" placeholder="Ide írhatod a saját tanulságodat, példát, hibát, amit el akarsz kerülni...">${esc(savedNotes)}</textarea>
      </div>

      <div class="stepNav">
        <button class="btn" ${currentStepIndex === 0 ? 'disabled style="opacity:.45;cursor:not-allowed;"' : ''} onclick="prevStep()">← Előző</button>
        <button class="btn btnBlue" onclick="saveLessonNotes()">Jegyzet mentése</button>
        <button class="btn" ${isLast ? 'disabled style="opacity:.45;cursor:not-allowed;"' : ''} onclick="nextStep()">Következő →</button>
      </div>

      ${isLast ? `
        <div class="finishCard">
          <h4 class="finishTitle">Lecke lezárása</h4>
          <ul class="finishList">
            <li>Futottál végig az egész anyagon.</li>
            <li>Leírtad a saját jegyzetedet.</li>
            <li>Tudod, hogyan kapcsolódik ez a gyakorlati kereskedéshez.</li>
          </ul>
          <div class="playerActions">
            <button class="btn btnPrimary" onclick="completeActiveTutorial()">Lecke befejezése</button>
            <button class="btn" onclick="openNextTutorial()">Következő lecke</button>
          </div>
        </div>
      ` : ''}
    </div>
  `;
}

window.saveLessonNotes = function(){
  if (!activeTutorial) return;
  const area = document.getElementById('lessonNotes');
  if (!area) return;
  localStorage.setItem(notesKey(activeTutorial.id), area.value || '');
};

window.prevStep = function(){
  if (!activeTutorial) return;
  saveLessonNotes();
  if (currentStepIndex > 0) {
    currentStepIndex--;
    localStorage.setItem(stepKey(activeTutorial.id), String(currentStepIndex));
    renderPlayer();
  }
};

window.nextStep = function(){
  if (!activeTutorial) return;
  saveLessonNotes();
  const steps = splitContentToSteps(activeTutorial.content);
  if (currentStepIndex < steps.length - 1) {
    currentStepIndex++;
    localStorage.setItem(stepKey(activeTutorial.id), String(currentStepIndex));
    renderPlayer();
  }
};

window.openTutorial = async function(id){
  const tutorial = tutorials.find(t => Number(t.id) === Number(id));
  if (!tutorial) return;

  await startTutorialIfNeeded(id);
  await Promise.all([loadTutorials(), loadProgress()]);

  activeTutorial = tutorials.find(t => Number(t.id) === Number(id)) || tutorial;

  const savedStep = parseInt(localStorage.getItem(stepKey(activeTutorial.id)) || '0', 10);
  currentStepIndex = Number.isFinite(savedStep) ? Math.max(0, savedStep) : 0;

  history.replaceState({}, '', `tutorials.php?id=${activeTutorial.id}`);
  renderLessonList();
  renderPlayer();
};

window.completeActiveTutorial = async function(){
  if (!activeTutorial) return;

  saveLessonNotes();

  await fetchJson('/api/tutorials/complete', {
    method: 'POST',
    headers: {
      'Content-Type':'application/json',
      'Accept':'application/json'
    },
    body: JSON.stringify({
      user_id: USER_ID,
      tutorial_id: activeTutorial.id
    })
  });

  await Promise.all([loadTutorials(), loadProgress()]);
  activeTutorial = tutorials.find(t => Number(t.id) === Number(activeTutorial.id)) || activeTutorial;
  renderLessonList();
  renderPlayer();
};

window.openNextTutorial = async function(){
  if (!activeTutorial) return;
  const idx = getCurrentTutorialIndex();
  if (idx < 0) return;

  const next = tutorials[idx + 1];
  if (next) {
    openTutorial(next.id);
  }
};

function bindFilterTabs(){
  document.querySelectorAll('.filterBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      currentFilterLevel = Number(btn.dataset.level || '0');
      document.querySelectorAll('.filterBtn').forEach(x => x.classList.remove('active'));
      btn.classList.add('active');
      renderLessonList();

      if (activeTutorial && currentFilterLevel !== 0 && Number(activeTutorial.difficulty_code) !== currentFilterLevel) {
        const candidate = tutorials.find(t => Number(t.difficulty_code) === currentFilterLevel);
        if (candidate) {
          openTutorial(candidate.id);
        }
      }
    });
  });
}

refreshAllBtn.addEventListener('click', async () => {
  await boot();
});

async function boot(){
  playerRoot.innerHTML = `<div class="emptyState">Betöltés…</div>`;
  lessonList.innerHTML = `<div class="emptyState">Betöltés…</div>`;

  await Promise.all([loadTutorials(), loadProgress()]);
  renderLessonList();

  let first = null;

  if (INITIAL_TUTORIAL_ID > 0) {
    first = tutorials.find(t => Number(t.id) === INITIAL_TUTORIAL_ID) || null;
  }

  if (!first && tutorials.length) {
    first = tutorials[0];
  }

  if (first) {
    await openTutorial(first.id);
  } else {
    renderPlayer();
  }
}

bindFilterTabs();
boot();
</script>
</body>
</html>