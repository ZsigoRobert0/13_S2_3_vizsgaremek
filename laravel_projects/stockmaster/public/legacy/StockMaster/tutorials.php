<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/user_service.php';

requireLogin();

$userId = currentUserId();
if ($userId <= 0) {
    legacy_redirect('login.php');
}

$initialTutorialId = isset($_GET['id']) ? (int) $_GET['id'] : 1;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>StockMaster — Oktatóanyagok 3.0</title>
<link rel="stylesheet" href="app.css?v=2">
<style>
:root{
  --bg:#0f1724; --bg2:#07111f; --panel:#0b1220; --panel2:#0e1728; --panel3:#101c31;
  --text:#e6eef8; --muted:#98a2b3; --line:rgba(255,255,255,.08); --glass:rgba(255,255,255,.03);
  --green:#22c55e; --green2:#86efac; --blue:#60a5fa; --blue2:#93c5fd; --blue3:#2563eb;
  --amber:#f59e0b; --rose:#fb7185; --cyan:#22d3ee; --shadow:0 10px 30px rgba(2,6,23,.55);
}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%;font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;background:
radial-gradient(circle at top left, rgba(96,165,250,.08), transparent 25%),
radial-gradient(circle at right top, rgba(167,139,250,.08), transparent 22%),
linear-gradient(180deg,var(--bg),var(--bg2));color:var(--text)}
body{overflow-x:hidden}
.wrap{max-width:1540px;margin:0 auto;padding:20px 18px 30px}
.topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:18px}
.h1{margin:0;font-size:36px;line-height:1.1;font-weight:900}
.sub{margin-top:8px;color:var(--muted);font-size:14px;line-height:1.7;max-width:860px}
.topActions{display:flex;gap:10px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;border-radius:14px;padding:11px 15px;font-weight:900;cursor:pointer;border:1px solid var(--line);background:var(--glass);color:var(--text);transition:.18s ease;box-shadow:0 8px 18px rgba(2,6,23,.18)}
.btn:hover{transform:translateY(-1px)}
.btnPrimary{background:linear-gradient(90deg,var(--green),var(--green2));color:#03210a;border:none}
.btnBlue{background:linear-gradient(90deg,rgba(96,165,250,.22),rgba(59,130,246,.12));border:1px solid rgba(96,165,250,.28)}
.btnAmber{background:linear-gradient(90deg,rgba(245,158,11,.20),rgba(245,158,11,.08));border:1px solid rgba(245,158,11,.25)}
.btnGhost{background:rgba(255,255,255,.025)}
.btnDanger{background:linear-gradient(90deg,rgba(244,63,94,.14),rgba(244,63,94,.06));border:1px solid rgba(244,63,94,.20)}
.btn:disabled{opacity:.45;cursor:not-allowed;transform:none}
.layout{display:grid;grid-template-columns:390px 1fr;gap:18px}
.sideCard,.mainCard{background:linear-gradient(180deg,rgba(11,18,32,.98),rgba(7,13,25,.98));border:1px solid var(--line);border-radius:24px;box-shadow:var(--shadow)}
.sideCard{padding:16px;display:flex;flex-direction:column;min-height:84vh}
.mainCard{padding:20px;min-height:84vh}
.statGrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.stat{border:1px solid var(--line);background:linear-gradient(180deg,rgba(255,255,255,.035),rgba(0,0,0,.04));border-radius:18px;padding:14px}
.statLabel,.smallMuted{color:var(--muted);font-size:12px;font-weight:800}
.statValue{font-size:26px;font-weight:900}
.progressOuter{width:100%;height:10px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.05);border:1px solid var(--line);margin-top:8px}
.progressInner{height:100%;width:0%;border-radius:999px;background:linear-gradient(90deg,var(--green),var(--blue));transition:width .25s ease}
.filterTabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.filterBtn{border:1px solid var(--line);background:var(--glass);color:var(--text);border-radius:999px;padding:9px 12px;cursor:pointer;font-weight:900}
.filterBtn.active{background:linear-gradient(90deg,rgba(96,165,250,.18),rgba(34,197,94,.15));border-color:rgba(96,165,250,.25)}
.lessonList{flex:1;overflow:auto;padding-right:4px}
.lessonItem{border:1px solid var(--line);border-radius:18px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(0,0,0,.04));padding:14px;margin-bottom:10px;cursor:pointer;transition:.18s ease}
.lessonItem:hover{transform:translateY(-1px)}
.lessonItem.active{border-color:rgba(96,165,250,.40);box-shadow:0 0 0 1px rgba(96,165,250,.18) inset;background:linear-gradient(180deg,rgba(96,165,250,.08),rgba(0,0,0,.04))}
.lessonTitle{font-weight:900;font-size:16px;margin-bottom:8px;line-height:1.35}
.lessonMeta{display:flex;gap:8px;flex-wrap:wrap}
.pill,.tag,.completionBadge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:var(--glass);border:1px solid var(--line);font-size:11px;font-weight:900}
.pillDone{color:#bbf7d0;background:rgba(34,197,94,.12)} .pillWork{color:#fde68a;background:rgba(245,158,11,.12)} .pillIdle{color:#cbd5e1;background:rgba(148,163,184,.08)}
.pillLevel1{color:#bfdbfe;background:rgba(96,165,250,.10)} .pillLevel2{color:#fde68a;background:rgba(245,158,11,.10)} .pillLevel3{color:#ddd6fe;background:rgba(167,139,250,.12)}
.playerHero,.stepBox,.sectionCard,.quizCard,.finishCard{border:1px solid var(--line);border-radius:24px;padding:20px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(0,0,0,.03));margin-bottom:18px}
.playerHead{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:14px}
.playerTitle{margin:0;font-size:32px;font-weight:900;line-height:1.15}
.playerLead{margin-top:12px;color:rgba(230,238,248,.90);line-height:1.8;font-size:15px;max-width:860px}
.playerSub{margin-top:12px;display:flex;gap:8px;flex-wrap:wrap}
.lessonStatus{min-width:270px;display:flex;flex-direction:column;gap:8px}
.inlineMetric{display:grid;grid-template-columns:repeat(5,minmax(110px,1fr));gap:10px;margin-top:16px}
.metricBox,.conceptMiniCard,.practiceMetaBox{padding:12px 14px;border:1px solid var(--line);border-radius:16px;background:rgba(255,255,255,.025)}
.metricLabel{color:var(--muted);font-size:11px;font-weight:800;margin-bottom:5px}
.metricValue{font-size:24px;font-weight:900}.metricValue.ok{color:#bbf7d0}.metricValue.warn{color:#fde68a}
.heroNotice,.successBanner,.warningBanner{margin-top:14px;border-radius:16px;padding:14px 16px;font-size:14px;line-height:1.7}
.heroNotice{border:1px solid rgba(96,165,250,.18);background:linear-gradient(180deg,rgba(96,165,250,.08),rgba(0,0,0,.03))}
.successBanner{border:1px solid rgba(34,197,94,.2);background:rgba(34,197,94,.08);color:#bbf7d0;font-weight:900}
.warningBanner{border:1px solid rgba(245,158,11,.22);background:rgba(245,158,11,.09);color:#fde68a;font-weight:800}
.stepTop,.stepNav{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.stepTitle{margin:0;font-size:25px;font-weight:900}
.stepIndicator{font-size:13px;color:var(--muted);font-weight:800}
.stepContent,.conceptMiniText,.practiceText,.summaryList,.quizExplain{color:rgba(230,238,248,.94);font-size:15px;line-height:1.9;white-space:pre-line}
.sectionCardTitle,.quizTitle,.finishTitle{margin:0 0 12px;font-size:20px;font-weight:900}
.summaryList{margin:0;padding-left:20px}.summaryList li+li{margin-top:4px}
.conceptGrid,.practiceMeta{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.conceptMiniTitle,.practiceMetaLabel,.notesLabel,.practiceNoteLabel{font-size:12px;font-weight:900;color:var(--muted);margin-bottom:8px}
.practiceTask,.quizQuestion{display:flex;flex-direction:column;gap:12px;padding:14px;border:1px solid var(--line);border-radius:18px;background:rgba(255,255,255,.02);margin-bottom:12px}
.practiceTask.done{border-color:rgba(34,197,94,.28);background:linear-gradient(180deg,rgba(34,197,94,.07),rgba(0,0,0,.03))}
.practiceHead{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.practiceNumber{width:34px;height:34px;min-width:34px;display:flex;align-items:center;justify-content:center;border-radius:999px;font-weight:900;background:rgba(96,165,250,.12);border:1px solid rgba(96,165,250,.18);color:#dbeafe}
.practiceCheck{display:flex;gap:10px;align-items:flex-start;font-size:14px;color:rgba(230,238,248,.94)}
.practiceCheck input{width:18px;height:18px;min-width:18px;accent-color:#22c55e}
.practiceHint,.notesHint{color:var(--muted);font-size:12px;line-height:1.6;margin-top:6px}
.practiceNoteArea,.notesArea{width:100%;min-height:92px;resize:vertical;border-radius:14px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--text);padding:10px 12px;font:inherit}
.notesArea{min-height:140px}
.quizOption{display:flex;gap:12px;align-items:flex-start;color:rgba(230,238,248,.94);font-size:14px;line-height:1.6;padding:12px 14px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.02);cursor:pointer;transition:.18s ease;margin-bottom:10px}
.quizOption:hover{border-color:rgba(96,165,250,.28);background:rgba(96,165,250,.05)}
.quizOption input[type="radio"]{accent-color:#60a5fa;margin-top:3px}
.quizOption.isCorrect{border-color:rgba(34,197,94,.32);background:rgba(34,197,94,.10)}
.quizOption.isWrong{border-color:rgba(244,63,94,.28);background:rgba(244,63,94,.09)}
.quizResult{margin-top:12px;font-size:14px;font-weight:900;min-height:22px}.quizOk{color:#bbf7d0}.quizBad{color:#fecaca}
.finishCard{border-color:rgba(34,197,94,.2);background:linear-gradient(180deg,rgba(34,197,94,.08),rgba(0,0,0,.04))}
.finishList{margin:0;padding-left:20px;line-height:1.9}.finishList li::marker{color:#86efac}
.completionBadgeRow{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.completionBadge.ok{background:rgba(34,197,94,.10);border-color:rgba(34,197,94,.20);color:#dcfce7}
.completionBadge.warn{background:rgba(245,158,11,.10);border-color:rgba(245,158,11,.18);color:#fef3c7}
.emptyState{border:1px dashed rgba(255,255,255,.15);border-radius:18px;padding:24px;color:var(--muted);font-size:15px;text-align:center}
@media (max-width:1180px){.layout{grid-template-columns:1fr}.inlineMetric{grid-template-columns:repeat(2,minmax(120px,1fr))}.conceptGrid,.practiceMeta{grid-template-columns:1fr}}
@media (max-width:760px){.playerHead{flex-direction:column}.playerTitle{font-size:24px}.statGrid{grid-template-columns:1fr}.inlineMetric{grid-template-columns:1fr}.topActions .btn{flex:1 1 100%}}
</style>
</head>
<body>
<div class="wrap">
  <div class="topbar">
    <div>
      <h1 class="h1">StockMaster Oktatóanyagok</h1>
      <div class="sub"></div>
      <div id="quickLessonInfo" class="smallMuted" style="margin-top:10px">Leckeinformációk betöltése…</div>
    </div>
    <div class="topActions">
      <a class="btn btnGhost" href="settings.php">← Vissza a beállításokhoz</a>
      <a class="btn btnBlue" href="index.php">Főoldal megnyitása</a>
      <button class="btn btnAmber" id="refreshAllBtn" type="button">Frissítés</button>
    </div>
  </div>

  <div class="layout">
    <aside class="sideCard">
      <div class="statGrid">
        <div class="stat">
          <div class="statLabel">Összes haladás</div>
          <div class="statValue" id="overallProgressText">0%</div>
          <div class="progressOuter"><div class="progressInner" id="overallProgressBar"></div></div>
        </div>
        <div class="stat">
          <div class="statLabel">Aktív lecke</div>
          <div class="statValue" id="activeLessonLevel">—</div>
          <div class="smallMuted" id="activeLessonStepText">Lépés 0 / 0</div>
        </div>
      </div>
      <div class="filterTabs">
        <button class="filterBtn active" type="button" data-filter="all">Összes</button>
        <button class="filterBtn" type="button" data-filter="1">Kezdő</button>
        <button class="filterBtn" type="button" data-filter="2">Haladó</button>
        <button class="filterBtn" type="button" data-filter="3">Profi</button>
        </div>
            <div style="margin-bottom:12px;">
            <input
                type="text"
                id="lessonSearch"
                placeholder="Lecke keresése..."
                style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--text);font:inherit;">
            </div>
        <div class="lessonList" id="lessonList">
    </aside>

    <main class="mainCard">
      <div class="playerHero">
        <div class="playerHead">
          <div>
            <h2 class="playerTitle" id="playerLessonTitle">Lecke betöltése…</h2>
            <div class="playerLead" id="playerLessonLead">A kiválasztott lecke részletesebb magyarázata, példái, gyakorlati feladatai és mini kvíze itt jelennek meg.</div>
            <div class="playerSub" id="playerTags"><span class="tag">Lecke adat betöltése…</span></div>
          </div>
          <div class="lessonStatus">
            <div class="smallMuted">Lecke haladás</div>
            <div style="font-size:40px;font-weight:900;" id="lessonHeroProgressValue">0%</div>
            <div class="progressOuter"><div class="progressInner" id="lessonHeroProgressBar"></div></div>
            <div class="smallMuted" id="lessonHeroDates">Indítva: —<br>Befejezve: —</div>
          </div>
        </div>
        <div class="inlineMetric">
          <div class="metricBox"><div class="metricLabel">Aktuális lépés</div><div class="metricValue" id="metricStep">0 / 0</div></div>
          <div class="metricBox"><div class="metricLabel">Gyakorlati feladat</div><div class="metricValue warn" id="metricPractice">Hiányzik</div></div>
          <div class="metricBox"><div class="metricLabel">Mini kvíz</div><div class="metricValue warn" id="metricQuiz">Hiányzik</div></div>
          <div class="metricBox"><div class="metricLabel">Jegyzet</div><div class="metricValue warn" id="metricNotes">Hiányzik</div></div>
          <div class="metricBox"><div class="metricLabel">Lecke státusz</div><div class="metricValue" id="metricStatus">Folyamatban</div></div>
        </div>
        <div class="heroNotice" id="heroNotice"></div>
      </div>

      <div class="sectionCard" id="jumpBarWrap">
        <h3 class="sectionCardTitle">Gyors navigáció</h3>
        <div id="jumpBarButtons" style="display:flex;gap:8px;flex-wrap:wrap;"></div>
        <div class="smallMuted" style="margin-top:10px;"></div>
      </div>

      <div class="stepBox">
        <div class="stepTop">
          <h3 class="stepTitle" id="stepTitle">Bevezetés</h3>
          <div class="stepIndicator" id="stepIndicator">1 / 1</div>
        </div>
        <div class="stepContent" id="stepContent">A lecke szöveges tartalma itt jelenik meg.</div>
        <div class="progressOuter"><div class="progressInner" id="stepProgressBar"></div></div>
        <div class="stepNav">
          <button class="btn btnGhost" id="prevStepBtn" type="button">← Előző lépés</button>
          <button class="btn btnBlue" id="nextStepBtn" type="button">Következő lépés →</button>
        </div>
      </div>

      <div class="sectionCard">
        <h3 class="sectionCardTitle">Mit vigyél magaddal ebből a részből?</h3>
        <ul class="summaryList" id="summaryList"><li>Összefoglaló betöltése…</li></ul>
      </div>

      <div class="sectionCard">
        <h3 class="sectionCardTitle">Kulcsfogalmak és gondolkodási pontok</h3>
        <div class="conceptGrid" id="conceptGrid"><div class="conceptMiniCard"><div class="conceptMiniTitle">Betöltés</div><div class="conceptMiniText">A lecke kulcskártyái itt fognak megjelenni.</div></div></div>
      </div>

      <div class="sectionCard">
        <h3 class="sectionCardTitle">Gyakorlati feladatok</h3>
        <div id="practiceList"><div class="emptyState">A gyakorlati feladatok betöltése folyamatban…</div></div>
      </div>

      <div class="quizCard">
        <div class="quizTitle">Mini kvíz</div>
        <div id="quizList"><div class="emptyState">A mini kvíz betöltése folyamatban…</div></div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
          <button class="btn btnBlue" id="checkQuizBtn" type="button">Kvíz ellenőrzése</button>
          <button class="btn btnGhost" id="resetQuizBtn" type="button">Kvíz visszaállítása</button>
        </div>
        <div class="quizResult" id="quizResultText"></div>
      </div>

      <div class="sectionCard">
        <h3 class="sectionCardTitle">Saját jegyzet</h3>
        <label class="notesLabel" for="lessonNotes">Ide írd le röviden, mit értettél meg, mit figyeltél meg a StockMaster felületén, és milyen tanulságot vinnél tovább.</label>
        <textarea class="notesArea" id="lessonNotes" placeholder="Példa: Az AAPL charton 1m és 1h között váltva látszott, hogy ugyanaz a mozgás teljesen más jelentést kap más nézőpontból…"></textarea>
        <div class="notesHint">Minimum pár értelmes mondatot hagyj itt. A lecke lezárásához nem elég egy üres vagy 1-2 szavas megjegyzés.</div>
      </div>

      <div class="finishCard">
        <div id="lessonMetaStrip" class="practiceMeta" style="margin-bottom:12px"></div>
        <h3 class="finishTitle">Lezárási feltételek</h3>
        <ul class="finishList">
          <li>Végig kell menni az összes lecke-lépésen.</li>
          <li>A gyakorlati feladatoknál a checkbox és a megfigyelési jegyzet is kötelező.</li>
          <li>A mini kvízt sikeresen teljesíteni kell.</li>
          <li>Értelmes saját jegyzetet kell írni a lecke végére.</li>
        </ul>
        <div id="completionBadgeRow" class="completionBadgeRow"></div>
        <div class="smallMuted" id="lockedNoteText" style="margin-top:12px">A lezárás addig zárolt, amíg minden kötelező rész nincs kész.</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
          <button class="btn btnPrimary" id="completeLessonBtn" type="button">Lecke lezárása</button>
          <button class="btn btnGhost" id="saveOnlyBtn" type="button">Állapot mentése</button>
          <button class="btn btnDanger" id="resetLessonStateBtn" type="button">Aktuális lecke állapot törlése</button>
        </div>
        <div id="lessonCelebrationBox" style="display:none"></div>
        <div id="statusBannerWrap"></div>
      </div>
    </main>
  </div>
</div>

<script>
const USER_ID = <?php echo (int) $userId; ?>;
const INITIAL_TUTORIAL_ID = <?php echo (int) $initialTutorialId; ?>;
const QUIZ_PASS_PERCENT = 75;
const NOTE_MIN_LENGTH = 30;

const LEVEL_LABELS = {1:'Kezdő',2:'Haladó',3:'Profi'};
const LEVEL_CLASS = {1:'pillLevel1',2:'pillLevel2',3:'pillLevel3'};

let lessonSearchTerm = '';
let tutorialIndex = [];
let tutorialProgressMap = {};
let progressSummary = null;
let activeFilter = 'all';
let activeLessonId = INITIAL_TUTORIAL_ID || 1;
let activeLesson = null;
let activeBlueprint = null;
let autosaveTimer = null;

function esc(v){return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
function pct(part,total){return total ? Math.round((part/total)*100) : 0;}
function clamp(num,min,max){return Math.max(min, Math.min(max, num));}
function setBar(el,v){ if(el) el.style.width = clamp(Number(v)||0,0,100) + '%'; }
function safeArray(v){return Array.isArray(v) ? v : [];}
function hardTrim(v){ return String(v ?? '').replace(/\s+/g,' ').trim(); }
function isTypingContext(target){ const tag=(target?.tagName||'').toLowerCase(); return tag==='textarea'||tag==='input'||target?.isContentEditable; }

function formatDateTime(value){
  if (!value) return '—';
  try{
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    const y = d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), day=String(d.getDate()).padStart(2,'0'),
          hh=String(d.getHours()).padStart(2,'0'), mm=String(d.getMinutes()).padStart(2,'0'), ss=String(d.getSeconds()).padStart(2,'0');
    return `${y}. ${m}. ${day}. ${hh}:${mm}:${ss}`;
  }catch(e){ return String(value); }
}

async function apiGetJson(url){
  const r = await fetch(url, {cache:'no-store', headers:{'Accept':'application/json'}});
  let data = null;
  try { data = await r.json(); } catch (e) { throw new Error('Érvénytelen JSON válasz'); }
  if (!r.ok) throw new Error(data?.message || data?.error || ('HTTP ' + r.status));
  return data;
}
async function apiPostJson(url, payload){
  const r = await fetch(url,{
    method:'POST',
    headers:{'Content-Type':'application/json','Accept':'application/json'},
    body: JSON.stringify(payload || {})
  });
  let data = null;
  try { data = await r.json(); } catch (e) {}
  if (!r.ok) throw new Error(data?.message || data?.error || ('HTTP ' + r.status));
  return data || {ok:true};
}

function storageKeyForLesson(id){ return `sm_tutorial_v3_state_${USER_ID}_${id}`; }
function defaultLessonState(lessonId){ return {lessonId,currentStep:0,visitedSteps:{},practice:{},quizAnswers:{},quizChecked:false,quizPassed:false,quizPercent:0,lessonNotes:'',updatedAt:null}; }
function readLessonState(lessonId){
  try{
    const raw = localStorage.getItem(storageKeyForLesson(lessonId));
    if(!raw) return defaultLessonState(lessonId);
    const parsed = JSON.parse(raw);
    return {...defaultLessonState(lessonId), ...parsed, visitedSteps: parsed.visitedSteps||{}, practice: parsed.practice||{}, quizAnswers: parsed.quizAnswers||{}};
  }catch(e){ return defaultLessonState(lessonId); }
}
function writeLessonState(lessonId, patch = {}){
  const prev = readLessonState(lessonId);
  const next = {
    ...prev, ...patch,
    visitedSteps: patch.visitedSteps ?? prev.visitedSteps,
    practice: patch.practice ?? prev.practice,
    quizAnswers: patch.quizAnswers ?? prev.quizAnswers,
    updatedAt: new Date().toISOString()
  };
  localStorage.setItem(storageKeyForLesson(lessonId), JSON.stringify(next));
  return next;
}
function clearLessonState(lessonId){ localStorage.removeItem(storageKeyForLesson(lessonId)); }

function statusLabelForLesson(progressItem, localState){
  if (progressItem && Number(progressItem.IsCompleted) === 1) return {text:'Kész', cls:'pillDone'};
  const touched = Object.keys(localState.visitedSteps||{}).length || Object.keys(localState.practice||{}).length || Object.keys(localState.quizAnswers||{}).length || hardTrim(localState.lessonNotes).length;
  if (touched) return {text:'Folyamatban', cls:'pillWork'};
  return {text:'Nincs elkezdve', cls:'pillIdle'};
}

function normalizeApiTutorialItem(item){
  return {
    id: Number(item.ID || item.id || 0),
    title: item.Title || item.title || 'Ismeretlen lecke',
    level: Number(item.DifficultyLevel || item.difficulty_code || item.level || 1),
    difficulty: item.difficulty || LEVEL_LABELS[Number(item.DifficultyLevel || item.difficulty_code || item.level || 1)] || 'Kezdő',
    tags: item.Tags || item.tags || [],
    content: item.Content || item.content || '',
    status: item.status || 'not_started',
    isCompleted: item.is_completed ? 1 : 0,
    startedAt: item.StartedAt || item.started_at || null,
    completedAt: item.CompletedAt || item.completed_at || null
  };
}

function levelFilterMatch(item){ return activeFilter === 'all' || String(item.level) === String(activeFilter); }

function extractBullets(text){
  const lines = String(text||'').split(/\r?\n/).map(s=>s.trim()).filter(Boolean);
  const bullets = lines.filter(x => /^[-•]/.test(x)).map(x => x.replace(/^[-•]\s*/, ''));
  return bullets.slice(0,4);
}
function splitParagraphs(text){
  return String(text||'').split(/\r?\n\r?\n/).map(s=>s.trim()).filter(Boolean);
}

function blueprintFromLesson(item){
  const paragraphs = splitParagraphs(item.content);
  const bullets = extractBullets(item.content);
  const lead = paragraphs[0] || 'A lecke részletesebb magyarázata itt jelenik meg.';
  const steps = [];
  const first = paragraphs[0] || item.content || 'A lecke tartalma itt jelenik meg.';
  const second = paragraphs[1] || paragraphs[0] || first;
  const third = paragraphs[2] || 'A gyakorlati oldalt és a kereskedési kontextust is érdemes végiggondolni.';
  steps.push({title:'Bevezetés', content:first});
  steps.push({title:'Mit jelent ez a gyakorlatban?', content:second});
  steps.push({title:'Mire figyelj a StockMasterben?', content:third});

  const summary = bullets.length ? bullets : [
    'Értsd meg a fogalom gyakorlati jelentését.',
    'Kösd össze a tanultakat a StockMaster felületével.',
    'Figyeld meg, hogyan jelenik meg ez a charton vagy a kereskedési logikában.',
    'Írd le a saját következtetésedet is.'
  ];

  const concepts = [
    {title:'Lecke fókusz', text: lead},
    {title:'Trading szemlélet', text: 'A cél nem a magolás, hanem hogy a fogalom kereskedési kontextusban is értelmet nyerjen.'}
  ];
  if (bullets[0]) concepts.push({title:'Kulcspont', text: bullets[0]});
  if (bullets[1]) concepts.push({title:'Második kulcspont', text: bullets[1]});

  const title = String(item.title||'').toLowerCase();
  let practice = [
    {text:'Nyisd meg az adott leckéhez kapcsolódó részt a StockMaster felületén vagy chartján, és figyeld meg, hogyan jelenik meg a tananyag a gyakorlatban.', hint:'A cél az, hogy a fogalom ne csak elmélet maradjon.'},
    {text:'Írj rövid megjegyzést arról, mi volt a legfontosabb felismerésed ebből a leckéből.', hint:'Minimum 1-2 értelmes mondat.'},
    {text:'Fogalmazd meg, hogyan használnád ezt a tudást egy későbbi trade vagy elemzés során.', hint:'Kösd össze a saját gondolkodásoddal.'},
  ];
  if (title.includes('candle')) practice[0].text = 'Nyiss meg egy chartot, és figyeld meg, hogyan néznek ki a gyertyák különböző timeframe-eken.';
  if (title.includes('részvény')) practice[0].text = 'Nyisd meg például az AAPL-t, és tudatosítsd magadban, hogy ez nem csak ticker, hanem valódi vállalati instrumentum.';
  if (title.includes('spread')) practice[0].text = 'Keresd meg a spread / bid-ask logikát a felületen, és írd le, miért számít a belépésnél.';
  if (title.includes('pozíció')) practice[0].text = 'Nézd meg a belépési panelt, és fogalmazd meg, mi alapján nyitnál pozíciót.';
  if (title.includes('risk')) practice[0].text = 'Írd le, mekkora kockázatot vállalnál egy trade-en, és miért pont annyit.';
  if (title.includes('trend') || title.includes('range')) practice[0].text = 'Nyiss meg egy chartot, és döntsd el, inkább trendelő vagy inkább range jellegű-e.';
  if (title.includes('hírek')) practice[0].text = 'Nézd meg a news / calendar blokkot, és írd le, miért lehet veszélyes hír előtt ugyanúgy tradelni.';
  if (title.includes('timeframe')) practice[0].text = 'Nézz meg legalább két timeframe-et ugyanazon az instrumentumon, és írd le a különbséget.';
  if (title.includes('napló')) practice[0].text = 'Írj rövid mini trade-journal jellegű bejegyzést a jegyzet részbe.';
  if (title.includes('drawdown')) practice[0].text = 'Fogalmazd meg, hogyan reagálnál tudatosan egy veszteségsorozat alatt.';
  if (title.includes('mentális')) practice[0].text = 'Írd le, nálad melyik mentális csapda a legerősebb tradelés közben.';
  if (title.includes('stratégia')) practice[0].text = 'Írj 3-5 mondatos mini stratégia-vázat a tanult elemekből.';

  const quiz = [
    {
      question: `Mi a lecke egyik legfontosabb üzenete a(z) "${item.title}" témában?`,
      explanation: 'A jó válasz mindig a tudatos, gyakorlati és kereskedési szemléletet emeli ki.',
      options: [
        {text:'Elég ránézésre vagy érzésből működni', correct:false, why:'A lecke célja pont a tudatosabb gondolkodás.'},
        {text:'A fogalmat érdemes a gyakorlatban és a StockMaster felületén is értelmezni', correct:true, why:'Ez kapcsolja össze az elméletet a használattal.'},
        {text:'Csak a kinézet számít, a logika nem', correct:false, why:'A rendszer lényege a gondolkodási minőség.'},
        {text:'Nem kell saját megfigyelést írni', correct:false, why:'A 3.0 rendszer pont erre is épül.'},
      ]
    },
    {
      question: 'Miért fontos a saját jegyzet és a gyakorlati megfigyelés?',
      explanation: 'A saját visszajelzés segít abban, hogy a lecke ne csak passzív olvasás maradjon.',
      options: [
        {text:'Mert így gyorsabban tölt be az oldal', correct:false, why:'Ennek nincs technikai köze hozzá.'},
        {text:'Mert ettől lesz a tananyag aktívan feldolgozott és személyesebb', correct:true, why:'Ez a jegyzetelés valódi haszna.'},
        {text:'Mert különben nem léteznek a lesson step-ek', correct:false, why:'Ez nem így működik.'},
        {text:'Mert a kvízt helyettesíti', correct:false, why:'A kvíz külön elem marad.'},
      ]
    },
    {
      question: 'Mi a helyes hozzáállás egy ilyen leckéhez?',
      explanation: 'A cél a megértés, az alkalmazás és a kontextusba helyezés.',
      options: [
        {text:'Gyorsan átkattintani rajta', correct:false, why:'A rendszer nem erre épül.'},
        {text:'Végigmenni a lépéseken, megfigyelni, jegyzetelni és visszacsatolni', correct:true, why:'Ez a 3.0 lesson-player logikája.'},
        {text:'Csak a zöld gombot keresni', correct:false, why:'A lezárás feltételekhez kötött.'},
        {text:'A gyakorlati részt kihagyni', correct:false, why:'A practice kötelező része a folyamatnak.'},
      ]
    }
  ];

  return {
    heroLead: lead,
    intro: paragraphs[0] || item.content || '',
    heroNotice: 'A cél itt az, hogy az elméletet összekösd a StockMaster használatával és a saját kereskedési gondolkodásoddal.',
    tags: safeArray(item.tags),
    summary,
    concepts,
    steps,
    practice,
    quiz
  };
}

const LESSON_OVERRIDES = {
  1: {
    heroLead: 'A részvény nem csak egy ticker vagy mozgó szám a charton. Amikor egy részvényt figyelsz, valójában egy konkrét vállalat piaci értékelését nézed, és ennek a megértése traderként is sokkal jobb döntési kontextust ad.',
    intro: 'Ebben a leckében azt tesszük rendbe, hogy mit is jelent valójában egy részvény, mi van a ticker mögött, és miért veszélyes pusztán chartobjektumként kezelni az instrumentumot.',
    heroNotice: 'A cél itt az, hogy ne csak szimbólumként lásd az instrumentumot, hanem mögöttes érték- és vállalatlogikával együtt.',
    tags: ['kezdő', 'alapok', 'részvény', 'vállalat'],
    summary: [
      'A részvény egy vállalat tulajdonrészét testesíti meg.',
      'A ticker mögött üzleti teljesítmény, piaci megítélés és várakozás áll.',
      'A jobb instrumentum-értés stabilabb kereskedési gondolkodást ad.',
      'A chartot érdemes mindig eszköz-kontextusban olvasni.'
    ],
    concepts: [
      {
        title: 'Tulajdonrész',
        text: 'A részvény nem pusztán spekulációs jelkép, hanem egy vállalathoz kötődő tulajdoni egység.'
      },
      {
        title: 'Piaci értékelés',
        text: 'Az ár nem csak a jelen helyzetet, hanem a jövőre vonatkozó várakozásokat is tükrözheti.'
      },
      {
        title: 'Ticker mögötti valóság',
        text: 'Egy AAPL vagy AMD mögött nem csak grafikon, hanem egy valódi cég, szektor és piaci sztori van.'
      },
      {
        title: 'Trader előny',
        text: 'Nem kell fundamentális elemzővé válnod, de jobb döntést hozol, ha tudod, mit tradelsz.'
      }
    ],
    steps: [
      {
        title: '1. Miért több a részvény, mint egy chart?',
        content: `Amikor a StockMasterben kiválasztasz egy instrumentumot, elsőre árat, mozgást, chartot és gombokat látsz. Ez a felület azonban csak a megjelenítése valaminek, ami mögött valódi piaci és vállalati értelmezés áll.

Egy részvény mögött egy cég van. Egy üzleti modell, bevétel, profit, növekedési sztori, piaci kockázat és befektetői várakozás. Ez azért fontos, mert így a mozgásokat sem puszta véletlenként fogod kezelni.`
      },
      {
        title: '2. Mit jelent ez traderként?',
        content: `Traderként sem hátrány, ha érted, hogy a ticker mögött milyen típusú instrumentum van. Másképp nézhetsz egy nagy, stabil technológiai céget, mint egy kisebb, hírérzékenyebb papírt.

A cél nem az, hogy minden trade előtt fél órán át fundamentumot olvass, hanem hogy legyen legalább minimális mentális képed arról, mit figyelsz. Ez csökkenti a vak kattintgatást és növeli a döntések érettségét.`
      },
      {
        title: '3. Mire figyelj a StockMasterben?',
        content: `Nyisd meg például az AAPL-t vagy egy másik ismertebb instrumentumot, és próbáld meg nem csak árként nézni. Gondold végig: mi emelhetné? mi nyomhatná le? mennyire lehet hírérzékeny? mennyire lehet hangulatvezérelt?

Ez a szemlélet már átvezet a következő leckékhez is: candle, buy/sell, spread, pozíciónyitás.`
      }
    ],
    practice: [
      {
        text: 'Nyisd meg az AAPL instrumentumot, és tudatosítsd magadban, hogy ez nem csak ticker, hanem egy valódi vállalat piaci leképezése.',
        hint: 'A cél szemléletváltás, nem pusztán kattintás.'
      },
      {
        text: 'Nézd meg még legalább egy másik részvény nevét is a listában, és írd le, szerinted miben lehet más a piaci karaktere.',
        hint: 'Elég röviden is, a lényeg a különbségek felismerése.'
      },
      {
        text: 'Írd le, miért veszélyes csak a chartot nézni anélkül, hogy tudnád, milyen eszközt tradelsz.',
        hint: 'Minimum 1-2 értelmes mondat.'
      }
    ],
    quiz: [
      {
        question: 'Mit jelent alapvetően egy részvény?',
        explanation: 'A helyes válasz a tulajdonosi és vállalati háttérre utal.',
        options: [
          { text: 'Csak egy charton mozgó számot', correct: false, why: 'A chart csak megjelenítés.' },
          { text: 'Egy vállalat tulajdonrészét', correct: true, why: 'Ez a részvény alapjelentése.' },
          { text: 'Egy automatikus profitforrást', correct: false, why: 'Profit sosem garantált.' },
          { text: 'Csak spekulációs játékot', correct: false, why: 'Lehet vele spekulálni, de nem csak ezt jelenti.' }
        ]
      },
      {
        question: 'Miért segít traderként is tudni, mi van a ticker mögött?',
        explanation: 'A jobb eszköz-értés jobb döntési kontextust ad.',
        options: [
          { text: 'Mert gyorsabb lesz az oldal', correct: false, why: 'Ez nem technikai kérdés.' },
          { text: 'Mert az instrumentum megértése jobb kereskedési kontextust ad', correct: true, why: 'Ez a tudatosabb megközelítés.' },
          { text: 'Mert különben nem működik a buy gomb', correct: false, why: 'Semmi köze hozzá.' },
          { text: 'Mert csak így lehet kvízt tölteni', correct: false, why: 'Nem ez a lényeg.' }
        ]
      },
      {
        question: 'Mi a legjobb szemlélet részvényeknél?',
        explanation: 'A jó trader nem csak a chartot nézi, hanem az instrumentum mögötti valóságot is.',
        options: [
          { text: 'A ticker mögött mindig van üzleti és piaci háttér is', correct: true, why: 'Ez a helyes szemlélet.' },
          { text: 'Minden részvény ugyanaz', correct: false, why: 'A karakterük eltérő lehet.' },
          { text: 'Csak a zöld-piros gyertya számít', correct: false, why: 'Ez túl szűk nézőpont.' },
          { text: 'A részvény csak hosszútávra érdekes', correct: false, why: 'Traderként is fontos.' }
        ]
      }
    ]
  },

  2: {
    heroLead: 'A candle a chart nyelve. Nem csak azt mutatja meg, hogy fel vagy le ment-e az ár, hanem azt is, hogyan zajlott az adott időszak csatája a vevők és az eladók között.',
    intro: 'Ebben a leckében a gyertya nem csak mint vizuális elem szerepel, hanem mint olvasható piaci információ.',
    heroNotice: 'A candle nem dísz a charton, hanem sűrített piaci történet.',
    tags: ['kezdő', 'candle', 'chart', 'gyertya'],
    summary: [
      'A candle négy alapadatot foglal össze.',
      'A test és a kanóc külön jelentést hordozhat.',
      'A gyertya színe önmagában kevés az értelmezéshez.',
      'A candle jelentését a környezet is befolyásolja.'
    ],
    concepts: [
      { title: 'OHLC', text: 'A candle open, high, low, close adatokat sűrít egyetlen egységbe.' },
      { title: 'Gyertyatest', text: 'A test mérete megmutathatja, mennyire volt egyirányú a periódus.' },
      { title: 'Kanóc', text: 'A kanóc gyakran visszautasításra vagy bizonytalanságra utalhat.' },
      { title: 'Kontextus', text: 'Ugyanaz a candle mást jelenthet trendben, range-ben vagy fontos szintnél.' }
    ],
    steps: [
      {
        title: '1. Mit foglal össze egy candle?',
        content: `A candle megmutatja, honnan indult az ár, meddig jutott el felfelé és lefelé, valamint hol zárt az adott időszak végén. Ez már önmagában több információ, mint egy sima árpont.

Aki megtanul gyertyát olvasni, az nem csak színt lát, hanem erőviszonyokat és viselkedést is.`
      },
      {
        title: '2. Mit mond a test és a kanóc?',
        content: `A nagy test gyakran erősebb irányítottságot jelezhet. A hosszú kanóc sokszor azt mutatja, hogy az ár ugyan próbált tovább menni, de ott reakció érte.

Ezért a gyertya szerkezete sokszor legalább annyira fontos, mint a színe.`
      },
      {
        title: '3. Mire figyelj a StockMasterben?',
        content: `Nyiss meg egy chartot, válts különböző timeframe-ek között, és keresd meg a nagy testű, illetve hosszú kanócos gyertyákat. Figyeld meg, mennyire más lesz a jelentésük más kontextusban.`
      }
    ],
    practice: [
      {
        text: 'Nyiss meg egy chartot és válts legalább két timeframe között.',
        hint: 'Például 1m és 1h.'
      },
      {
        text: 'Keress egy nagy testű gyertyát, és írd le, mit jelezhetett.',
        hint: 'Gondolkodj irányban és erőviszonyban.'
      },
      {
        text: 'Keress hosszú kanócos gyertyát is, és írd le, mit jelenthetett.',
        hint: 'A visszautasítás vagy bizonytalanság jó kiindulópont.'
      }
    ],
    quiz: [
      {
        question: 'Mit foglal össze egy candle?',
        explanation: 'A candle négy alapárat tömörít egy időablakba.',
        options: [
          { text: 'Csak a záróárat', correct: false, why: 'Ez túl kevés.' },
          { text: 'Open, high, low, close adatokat', correct: true, why: 'Ez a candle alapja.' },
          { text: 'Csak a spreadet', correct: false, why: 'Az külön fogalom.' },
          { text: 'Csak a napi minimumot', correct: false, why: 'Nem ezt jelenti.' }
        ]
      },
      {
        question: 'Miért fontos a kanóc?',
        explanation: 'A kanóc gyakran a visszautasítás vagy bizonytalanság jele lehet.',
        options: [
          { text: 'Mert csak dekoráció', correct: false, why: 'Egyáltalán nem az.' },
          { text: 'Mert visszajelzést adhat az ár reakcióiról', correct: true, why: 'Ez a gyakorlati értelme.' },
          { text: 'Mert ettől gyorsabb a chart', correct: false, why: 'Technikai tévedés.' },
          { text: 'Mert helyettesíti a trendet', correct: false, why: 'Nem helyettesíti.' }
        ]
      },
      {
        question: 'Mi a helyes candle-szemlélet?',
        explanation: 'A candle-t nem csak nézni kell, hanem értelmezni.',
        options: [
          { text: 'Csak a színe számít', correct: false, why: 'A test és a környezet is fontos.' },
          { text: 'A candle sűrített piaci történetet mutat', correct: true, why: 'Ez a jó megközelítés.' },
          { text: 'Mindig ugyanazt jelenti', correct: false, why: 'Kontextusfüggő.' },
          { text: 'Nem kell vele foglalkozni', correct: false, why: 'Pedig a chart alapja.' }
        ]
      }
    ]
  }
};

function getBlueprintForLesson(lessonId){
  const item = tutorialIndex.find(x => Number(x.id) === Number(lessonId));
  if (!item) return null;

  if (LESSON_OVERRIDES[lessonId]) {
    return LESSON_OVERRIDES[lessonId];
  }

  return blueprintFromLesson(item);
}

function computeLessonCompletionMetrics(item, blueprint, state){
  const stepsTotal = safeArray(blueprint.steps).length || 0;
  const visitedStepCount = Object.keys(state.visitedSteps || {}).filter(k => state.visitedSteps[k]).length;
  const fullStepDone = stepsTotal > 0 && visitedStepCount >= stepsTotal;

  const practiceItems = safeArray(blueprint.practice);
  let practiceDoneCount = 0;
  practiceItems.forEach((task, idx) => {
    const row = state.practice?.[idx] || {};
    if (!!row.checked && hardTrim(row.note).length >= 8) practiceDoneCount++;
  });
  const practiceDone = practiceItems.length ? practiceDoneCount === practiceItems.length : true;
  const practicePercent = practiceItems.length ? pct(practiceDoneCount, practiceItems.length) : 100;

  const noteDone = hardTrim(state.lessonNotes).length >= NOTE_MIN_LENGTH;
  const quizItems = safeArray(blueprint.quiz);
  const quizPercent = Number(state.quizPercent || 0);
  const quizDone = quizItems.length ? !!state.quizPassed : true;

  const globalPercent = Math.round(
    ((fullStepDone ? 25 : pct(visitedStepCount, Math.max(stepsTotal,1)) * 0.25)) +
    (practicePercent * 0.35) +
    ((quizItems.length ? quizPercent : 100) * 0.25) +
    (noteDone ? 15 : Math.min(hardTrim(state.lessonNotes).length, NOTE_MIN_LENGTH) / NOTE_MIN_LENGTH * 15)
  );

  return {stepsTotal, visitedStepCount, fullStepDone, practiceDoneCount, practiceTotal: practiceItems.length, practiceDone, practicePercent, noteDone, quizDone, quizPercent, globalPercent};
}

function showBanner(type, text){
  const wrap = document.getElementById('statusBannerWrap');
  if (!wrap) return;
  if (type === 'success') wrap.innerHTML = `<div class="successBanner">${esc(text)}</div>`;
  else if (type === 'warning') wrap.innerHTML = `<div class="warningBanner">${esc(text)}</div>`;
  else wrap.innerHTML = '';
}

function getCompletionWarnings(metrics, blueprint){
  const warnings = [];
  if (!metrics.fullStepDone) warnings.push('Nem mentél végig az összes lecke-lépésen.');
  if (!metrics.practiceDone) warnings.push(`A gyakorlati feladatok még hiányosak (${metrics.practiceDoneCount}/${metrics.practiceTotal}).`);
  if (!metrics.quizDone) warnings.push(`A mini kvíz még nincs meg a szükséges ${QUIZ_PASS_PERCENT}% szinten.`);
  if (!metrics.noteDone) warnings.push(`A saját jegyzet még túl rövid. Minimum ${NOTE_MIN_LENGTH} karakter kell.`);
  return warnings;
}

function renderFilterButtons(){
  document.querySelectorAll('.filterBtn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.filter === activeFilter);
    btn.onclick = () => { activeFilter = btn.dataset.filter || 'all'; renderFilterButtons(); renderSidebar(); };
  });
}

function renderOverallStats(){
  if (progressSummary && typeof progressSummary.percent !== 'undefined') {
    const percent = Math.round(Number(progressSummary.percent || 0));
    document.getElementById('overallProgressText').textContent = `${percent}%`;
    setBar(document.getElementById('overallProgressBar'), percent);
    return;
  }
  const total = tutorialIndex.length;
  const done = tutorialIndex.filter(x => tutorialProgressMap[x.id]?.IsCompleted === 1).length;
  const percent = total ? pct(done, total) : 0;
  document.getElementById('overallProgressText').textContent = `${percent}% (${done}/${total})`;
  setBar(document.getElementById('overallProgressBar'), percent);
}

function renderSidebar(){
        const wrap = document.getElementById('lessonList');
        const filtered = tutorialIndex.filter(item => {
        const levelOk = levelFilterMatch(item);
        const text = `${item.title} ${(item.tags || []).join(' ')}`.toLowerCase();
        const searchOk = !lessonSearchTerm || text.includes(lessonSearchTerm.toLowerCase());
        return levelOk && searchOk;
    });
  if (!filtered.length) { wrap.innerHTML = `<div class="emptyState">Ehhez a szűrőhöz most nincs lecke.</div>`; return; }

  wrap.innerHTML = filtered.map(item => {
    const localState = readLessonState(item.id);
    const status = statusLabelForLesson(tutorialProgressMap[item.id], localState);
    const metrics = computeLessonCompletionMetrics(item, getBlueprintForLesson(item.id), localState);
    return `
      <div class="lessonItem ${Number(item.id) === Number(activeLessonId) ? 'active' : ''}" data-lesson-id="${item.id}">
        <div class="lessonTitle">${esc(item.title)}</div>
        <div class="lessonMeta" style="margin-bottom:10px">
          <span class="pill ${LEVEL_CLASS[item.level] || ''}">${esc(LEVEL_LABELS[item.level] || 'Ismeretlen')}</span>
          <span class="pill ${status.cls}">${esc(status.text)}</span>
          <span class="pill">${metrics.globalPercent}%</span>
        </div>
        <div class="smallMuted">${metrics.visitedStepCount}/${metrics.stepsTotal} step · ${metrics.practiceDoneCount}/${metrics.practiceTotal} practice · ${metrics.quizPercent}% quiz</div>
      </div>
    `;
  }).join('');

  wrap.querySelectorAll('.lessonItem').forEach(node => {
    node.addEventListener('click', () => switchLesson(Number(node.dataset.lessonId || 0)));
  });
}

function renderQuickLessonInfo(){
  const node = document.getElementById('quickLessonInfo');
  if (!node || !activeLesson || !activeBlueprint) return;
  const state = readLessonState(activeLessonId);
  const metrics = computeLessonCompletionMetrics(activeLesson, activeBlueprint, state);
  node.innerHTML = `Aktív lecke ID: <strong>${esc(activeLesson.id)}</strong> · Szint: <strong>${esc(LEVEL_LABELS[activeLesson.level] || '—')}</strong> · Step: <strong>${metrics.visitedStepCount}/${metrics.stepsTotal}</strong> · Practice: <strong>${metrics.practiceDoneCount}/${metrics.practiceTotal}</strong> · Quiz: <strong>${metrics.quizPercent}%</strong>`;
}

function renderHero(item, blueprint, state, metrics){
  document.getElementById('playerLessonTitle').textContent = item.title;
  document.getElementById('playerLessonLead').textContent = blueprint.heroLead || blueprint.intro || '';
  document.getElementById('playerTags').innerHTML = [`<span class="tag">${esc(LEVEL_LABELS[item.level] || 'Ismeretlen')}</span>`, ...safeArray(blueprint.tags).map(tag => `<span class="tag">${esc(tag)}</span>`)].join('');
  document.getElementById('lessonHeroProgressValue').textContent = `${metrics.globalPercent}%`;
  setBar(document.getElementById('lessonHeroProgressBar'), metrics.globalPercent);
  const progressRow = tutorialProgressMap[item.id] || {};
  document.getElementById('lessonHeroDates').innerHTML = `Indítva: ${esc(formatDateTime(progressRow.StartedAt || item.startedAt))}<br>Befejezve: ${esc(formatDateTime(progressRow.CompletedAt || item.completedAt))}`;
  document.getElementById('heroNotice').textContent = blueprint.heroNotice || '';
  document.getElementById('activeLessonLevel').textContent = LEVEL_LABELS[item.level] || '—';
  document.getElementById('activeLessonStepText').textContent = `Lépés ${Math.min((state.currentStep||0)+1, Math.max(metrics.stepsTotal,1))} / ${metrics.stepsTotal}`;
  document.getElementById('metricStep').textContent = `${Math.min((state.currentStep||0)+1, Math.max(metrics.stepsTotal,1))} / ${metrics.stepsTotal}`;
  document.getElementById('metricPractice').textContent = metrics.practiceDone ? `Kész (${metrics.practiceDoneCount}/${metrics.practiceTotal})` : `Hiányzik (${metrics.practiceDoneCount}/${metrics.practiceTotal})`;
  document.getElementById('metricPractice').className = `metricValue ${metrics.practiceDone ? 'ok' : 'warn'}`;
  document.getElementById('metricQuiz').textContent = metrics.quizDone ? `Kész (${metrics.quizPercent}%)` : `Hiányzik (${metrics.quizPercent}%)`;
  document.getElementById('metricQuiz').className = `metricValue ${metrics.quizDone ? 'ok' : 'warn'}`;
  document.getElementById('metricNotes').textContent = metrics.noteDone ? 'Kész' : `${hardTrim(state.lessonNotes).length}/${NOTE_MIN_LENGTH}`;
  document.getElementById('metricNotes').className = `metricValue ${metrics.noteDone ? 'ok' : 'warn'}`;
  const done = tutorialProgressMap[item.id]?.IsCompleted === 1;
  document.getElementById('metricStatus').textContent = done ? 'Lezárva' : 'Folyamatban';
  document.getElementById('metricStatus').className = `metricValue ${done ? 'ok' : 'warn'}`;
}

function renderSummary(blueprint){
  const node = document.getElementById('summaryList');
  node.innerHTML = safeArray(blueprint.summary).length ? blueprint.summary.map(line => `<li>${esc(line)}</li>`).join('') : `<li>Nincs külön összefoglaló ehhez a leckéhez.</li>`;
}
function renderConcepts(blueprint){
  const node = document.getElementById('conceptGrid');
  node.innerHTML = safeArray(blueprint.concepts).length ? blueprint.concepts.map(card => `<div class="conceptMiniCard"><div class="conceptMiniTitle">${esc(card.title||'')}</div><div class="conceptMiniText">${esc(card.text||'')}</div></div>`).join('') : `<div class="conceptMiniCard"><div class="conceptMiniTitle">Nincs külön bontás</div><div class="conceptMiniText">Ehhez a leckéhez jelenleg nincs külön kulcsfogalom-kártya.</div></div>`;
}
function markCurrentStepVisited(){
  const state = readLessonState(activeLessonId);
  const visited = {...(state.visitedSteps || {})};
  visited[state.currentStep || 0] = true;
  writeLessonState(activeLessonId, {visitedSteps: visited});
}
function renderCurrentStep(item, blueprint){
  const state = readLessonState(activeLessonId);
  const steps = safeArray(blueprint.steps);
  const idx = clamp(Number(state.currentStep || 0), 0, Math.max(steps.length - 1, 0));
  const step = steps[idx] || {title:item.title, content: blueprint.intro || item.content || ''};
  if (idx !== state.currentStep) writeLessonState(activeLessonId, {currentStep: idx});
  markCurrentStepVisited();
  document.getElementById('stepTitle').textContent = step.title || 'Lecke';
  document.getElementById('stepContent').textContent = step.content || '';
  document.getElementById('stepIndicator').textContent = `${idx+1} / ${Math.max(steps.length, 1)}`;
  setBar(document.getElementById('stepProgressBar'), steps.length ? pct(idx + 1, steps.length) : 100);
  const prevBtn = document.getElementById('prevStepBtn'), nextBtn = document.getElementById('nextStepBtn');
  prevBtn.disabled = idx <= 0; nextBtn.disabled = idx >= steps.length - 1;
  prevBtn.onclick = () => { writeLessonState(activeLessonId, {currentStep: Math.max(0, idx - 1)}); openLesson(activeLessonId, false); };
  nextBtn.onclick = () => { writeLessonState(activeLessonId, {currentStep: Math.min(steps.length - 1, idx + 1)}); openLesson(activeLessonId, false); };
  renderJumpBar(steps, idx);
}
function renderJumpBar(steps, current){
  const node = document.getElementById('jumpBarButtons');
  node.innerHTML = steps.map((step, idx) => `<button type="button" class="btn" data-step-jump="${idx}" style="padding:9px 12px;border-radius:999px;background:${idx===current?'rgba(96,165,250,.12)':'rgba(255,255,255,.03)'};border:1px solid ${idx===current?'rgba(96,165,250,.35)':'var(--line)'};box-shadow:none">${idx+1}. ${esc((step.title||'Lépés').slice(0,26))}</button>`).join('');
  node.querySelectorAll('[data-step-jump]').forEach(btn => btn.onclick = () => { writeLessonState(activeLessonId, {currentStep:Number(btn.dataset.stepJump||0)}); openLesson(activeLessonId, false); });
}
function getPracticeStateRow(idx){ return readLessonState(activeLessonId).practice?.[idx] || {checked:false,note:''}; }
function updatePracticeRow(idx, patch){
  const state = readLessonState(activeLessonId);
  const practice = {...(state.practice || {})};
  practice[idx] = {checked: !!practice[idx]?.checked, note: String(practice[idx]?.note || ''), ...patch};
  writeLessonState(activeLessonId, {practice});
}
function renderPractice(blueprint){
  const node = document.getElementById('practiceList');
  const items = safeArray(blueprint.practice);
  if (!items.length) { node.innerHTML = `<div class="emptyState">Ehhez a leckéhez nincs külön gyakorlati blokk.</div>`; return; }
  node.innerHTML = items.map((task, idx) => {
    const row = getPracticeStateRow(idx), note = String(row.note || ''), checked = !!row.checked, noteLen = hardTrim(note).length, isDone = checked && noteLen >= 8;
    return `<div class="practiceTask ${isDone ? 'done' : ''}">
      <div class="practiceHead"><div class="practiceNumber">${idx+1}</div><div><div class="practiceText">${esc(task.text||'')}</div><div class="practiceHint">${esc(task.hint||'')}</div></div></div>
      <label class="practiceCheck"><input type="checkbox" ${checked?'checked':''} data-practice-check="${idx}"><span>Megcsináltam vagy tudatosan végigmentem rajta</span></label>
      <div><div class="practiceNoteLabel">Rövid megfigyelés / saját visszajelzés</div><textarea class="practiceNoteArea" data-practice-note="${idx}" placeholder="Mit figyeltél meg? Mi volt a tanulság?">${esc(note)}</textarea></div>
      <div class="practiceMeta">
        <div class="practiceMetaBox"><div class="practiceMetaLabel">Checkbox</div><div>${checked?'Kész':'Hiányzik'}</div></div>
        <div class="practiceMetaBox"><div class="practiceMetaLabel">Jegyzet hossza</div><div>${noteLen} karakter</div></div>
      </div>
    </div>`;
  }).join('');
  node.querySelectorAll('[data-practice-check]').forEach(el => el.onchange = () => { updatePracticeRow(Number(el.dataset.practiceCheck||0), {checked: el.checked}); renderMetricsOnly(); openLesson(activeLessonId,false); });
  node.querySelectorAll('[data-practice-note]').forEach(el => {
    el.oninput = () => { updatePracticeRow(Number(el.dataset.practiceNote||0), {note: el.value}); renderMetricsOnly(); };
    el.onblur = () => openLesson(activeLessonId,false);
  });
}
function renderQuiz(blueprint){
  const node = document.getElementById('quizList');
  const state = readLessonState(activeLessonId);
  const items = safeArray(blueprint.quiz);
  if (!items.length) { node.innerHTML = `<div class="emptyState">Ehhez a leckéhez nincs mini kvíz.</div>`; document.getElementById('checkQuizBtn').disabled = true; document.getElementById('resetQuizBtn').disabled = true; document.getElementById('quizResultText').textContent=''; return; }
  document.getElementById('checkQuizBtn').disabled = false; document.getElementById('resetQuizBtn').disabled = false;
  node.innerHTML = items.map((q, qIdx) => {
    const selected = state.quizAnswers?.[qIdx], correctIndex = q.options.findIndex(o => !!o.correct), checked = !!state.quizChecked;
    const optionsHtml = q.options.map((opt, optIdx) => `<label class="quizOption ${checked && optIdx===correctIndex ? 'isCorrect' : ''} ${checked && Number(selected)===Number(optIdx) && optIdx!==correctIndex ? 'isWrong' : ''}">
      <input type="radio" name="quiz_${qIdx}" value="${optIdx}" data-quiz-q="${qIdx}" data-quiz-opt="${optIdx}" ${Number(selected)===Number(optIdx)?'checked':''} ${checked?'disabled':''}>
      <span>${esc(opt.text||'')}</span>
    </label>`).join('');
    const explain = checked ? `<div class="quizExplain"><strong>${Number(selected)===Number(correctIndex)?'Helyes válasz.':'Nem ez volt a helyes válasz.'}</strong><br>${esc(q.explanation||'')}<br><br><strong>Miért?</strong> ${esc((q.options[correctIndex]||{}).why || '')}</div>` : '';
    return `<div class="quizQuestion"><div class="sectionCardTitle" style="font-size:18px;margin-bottom:10px">${qIdx+1}. ${esc(q.question || '')}</div>${optionsHtml}${explain}</div>`;
  }).join('');
  node.querySelectorAll('[data-quiz-q]').forEach(el => {
    el.onchange = () => {
      const stateNow = readLessonState(activeLessonId);
      const quizAnswers = {...(stateNow.quizAnswers || {})};
      quizAnswers[Number(el.dataset.quizQ||0)] = Number(el.dataset.quizOpt||0);
      writeLessonState(activeLessonId, {quizAnswers, quizChecked:false, quizPassed:false, quizPercent:0});
      renderQuiz(blueprint); renderMetricsOnly();
    };
  });
  const resultNode = document.getElementById('quizResultText');
  if (state.quizChecked) {
    resultNode.className = `quizResult ${state.quizPassed ? 'quizOk' : 'quizBad'}`;
    resultNode.textContent = state.quizPassed ? `Sikeres mini kvíz (${state.quizPercent}%).` : `A mini kvíz még nem érte el a szükséges ${QUIZ_PASS_PERCENT}% szintet. Jelenlegi eredmény: ${state.quizPercent}%.`;
  } else { resultNode.className='quizResult'; resultNode.textContent='A mini kvíz még nincs ellenőrizve.'; }
}
function evaluateQuiz(blueprint){
  const state = readLessonState(activeLessonId), items = safeArray(blueprint.quiz);
  if (!items.length) { writeLessonState(activeLessonId,{quizChecked:true,quizPassed:true,quizPercent:100}); return; }
  let correct = 0;
  items.forEach((q,i)=>{ if (Number(state.quizAnswers?.[i]) === q.options.findIndex(o=>!!o.correct)) correct++; });
  const percent = pct(correct, items.length);
  writeLessonState(activeLessonId,{quizChecked:true, quizPassed: percent >= QUIZ_PASS_PERCENT, quizPercent: percent});
}
function resetQuizState(){ writeLessonState(activeLessonId,{quizAnswers:{},quizChecked:false,quizPassed:false,quizPercent:0}); if(activeBlueprint){renderQuiz(activeBlueprint); renderMetricsOnly();} }

function renderLessonNotes(){
  document.getElementById('lessonNotes').value = String(readLessonState(activeLessonId).lessonNotes || '');
}
function bindLessonNotes(){
  const notes = document.getElementById('lessonNotes');
  notes.oninput = () => { writeLessonState(activeLessonId,{lessonNotes: notes.value}); renderMetricsOnly(); };
}
function renderLessonMetaStrip(metrics){
  const node = document.getElementById('lessonMetaStrip');
  const state = readLessonState(activeLessonId);
  node.innerHTML = `
    <div class="practiceMetaBox"><div class="practiceMetaLabel">Aktív lecke ID</div><div>${esc(activeLesson.id)}</div></div>
    <div class="practiceMetaBox"><div class="practiceMetaLabel">Step haladás</div><div>${esc(metrics.visitedStepCount)}/${esc(metrics.stepsTotal)}</div></div>
    <div class="practiceMetaBox"><div class="practiceMetaLabel">Utoljára mentve</div><div>${esc(formatDateTime(state.updatedAt))}</div></div>
    <div class="practiceMetaBox"><div class="practiceMetaLabel">Helyi állapot</div><div>${hardTrim(state.lessonNotes)||Object.keys(state.practice||{}).length||Object.keys(state.quizAnswers||{}).length ? 'Van mentett állapot' : 'Még nincs lokális aktivitás'}</div></div>
  `;
}
function renderCompletionBadges(metrics){
  const done = tutorialProgressMap[activeLesson.id]?.IsCompleted === 1;
  document.getElementById('completionBadgeRow').innerHTML = [
    {ok:metrics.fullStepDone, text: metrics.fullStepDone ? `Step-ek végigjárva (${metrics.visitedStepCount}/${metrics.stepsTotal})` : `Step hiányzik (${metrics.visitedStepCount}/${metrics.stepsTotal})`},
    {ok:metrics.practiceDone, text: metrics.practiceDone ? `Practice kész (${metrics.practiceDoneCount}/${metrics.practiceTotal})` : `Practice hiányos (${metrics.practiceDoneCount}/${metrics.practiceTotal})`},
    {ok:metrics.quizDone, text: metrics.quizDone ? `Quiz kész (${metrics.quizPercent}%)` : `Quiz hiányzik (${metrics.quizPercent}%)`},
    {ok:metrics.noteDone, text: metrics.noteDone ? 'Jegyzet kész' : 'Jegyzet hiányzik'},
    {ok:done, text: done ? 'Backend lezárva' : 'Még nincs lezárva'},
  ].map(b => `<div class="completionBadge ${b.ok ? 'ok' : 'warn'}">${esc(b.text)}</div>`).join('');
  const box = document.getElementById('lessonCelebrationBox');
  if (done) {
    box.style.display = 'block';
    box.className = 'successBanner';
    box.innerHTML = `<div style="font-size:22px;font-weight:900;margin-bottom:8px;">✔ Lecke sikeresen lezárva</div><div style="font-size:14px;">Minden kötelező elem teljesült: step-ek, practice, mini kvíz és saját jegyzet.</div>`;
  } else { box.style.display = 'none'; box.innerHTML = ''; }
}
function renderMetricsOnly(){
  if (!activeLesson || !activeBlueprint) return null;
  const state = readLessonState(activeLessonId);
  const metrics = computeLessonCompletionMetrics(activeLesson, activeBlueprint, state);
  renderHero(activeLesson, activeBlueprint, state, metrics);
  renderCompletionBadges(metrics);
  renderLessonMetaStrip(metrics);
  renderQuickLessonInfo();
  renderSidebar();
  renderOverallStats();
  const warnings = getCompletionWarnings(metrics, activeBlueprint);
  if (tutorialProgressMap[activeLesson.id]?.IsCompleted === 1) showBanner('success', 'Ez a lecke már lezárásra került a backendben.');
  else if (warnings.length) showBanner('warning', warnings.join(' '));
  else showBanner('success', 'Minden feltétel teljesült, a lecke lezárható.');
  document.getElementById('lockedNoteText').textContent = warnings.length ? `Még nem zárható le: ${warnings.join(' ')}` : 'Minden kötelező feltétel teljesült, a lecke lezárható.';
  return metrics;
}

async function ensureLessonStarted(item){
  const progressItem = tutorialProgressMap[item.id];
  if (progressItem && progressItem.StartedAt) return;
  try{
    const data = await apiPostJson('/api/tutorials/start', {user_id: USER_ID, tutorial_id: item.id});
    tutorialProgressMap[item.id] = {TutorialID:item.id, IsCompleted:0, StartedAt:data?.data?.started_at || new Date().toISOString(), CompletedAt:null};
  }catch(err){ console.warn('Lecke start hiba:', err); }
}

async function openLesson(lessonId, ensureStart = true){
  activeLessonId = Number(lessonId);
  const item = tutorialIndex.find(x => Number(x.id) === Number(activeLessonId));
  if (!item) return;
  activeLesson = item;
  activeBlueprint = getBlueprintForLesson(item.id);
  if (ensureStart) await ensureLessonStarted(item);
  const state = readLessonState(activeLessonId);
  const metrics = computeLessonCompletionMetrics(item, activeBlueprint, state);
  renderHero(item, activeBlueprint, state, metrics);
  renderSummary(activeBlueprint);
  renderConcepts(activeBlueprint);
  renderCurrentStep(item, activeBlueprint);
  renderPractice(activeBlueprint);
  renderQuiz(activeBlueprint);
  renderLessonNotes();
  bindLessonNotes();
  renderMetricsOnly();
  renderFinishState();
}

function renderFinishState(){
  const btn = document.getElementById('completeLessonBtn');
  if (tutorialProgressMap[activeLessonId]?.IsCompleted === 1) { btn.textContent='Lecke már lezárva'; btn.disabled=true; }
  else { btn.textContent='Lecke lezárása'; btn.disabled=false; }
}

async function completeActiveLesson(){
  if (!activeLesson || !activeBlueprint) return;
  const metrics = renderMetricsOnly();
  const warnings = getCompletionWarnings(metrics, activeBlueprint);
  if (warnings.length) { showBanner('warning', warnings.join(' ')); return; }
  const btn = document.getElementById('completeLessonBtn');
  btn.disabled = true; btn.textContent = 'Lezárás folyamatban…';
  try{
    const data = await apiPostJson('/api/tutorials/complete', {user_id: USER_ID, tutorial_id: activeLesson.id});
    tutorialProgressMap[activeLesson.id] = {TutorialID: activeLesson.id, IsCompleted:1, StartedAt:tutorialProgressMap[activeLesson.id]?.StartedAt || data?.data?.started_at || new Date().toISOString(), CompletedAt:data?.data?.completed_at || new Date().toISOString()};
    renderFinishState();
    renderMetricsOnly();
    showBanner('success', 'A lecke sikeresen lezárva. A backend progress is frissült.');
  }catch(err){
    showBanner('warning', `Nem sikerült lezárni a leckét: ${err.message || err}`);
    renderFinishState();
  }
}

function switchLesson(lessonId){
  if (!lessonId) return;
  activeLessonId = Number(lessonId);
  const url = new URL(window.location.href);
  url.searchParams.set('id', String(activeLessonId));
  history.replaceState({}, '', url.toString());
  renderSidebar();
  openLesson(activeLessonId, true);
}

function bindButtons(){
  document.getElementById('checkQuizBtn').onclick = () => { if (!activeBlueprint) return; evaluateQuiz(activeBlueprint); renderQuiz(activeBlueprint); renderMetricsOnly(); };
  document.getElementById('resetQuizBtn').onclick = () => { resetQuizState(); };
  document.getElementById('saveOnlyBtn').onclick = () => { const notes = document.getElementById('lessonNotes').value || ''; writeLessonState(activeLessonId, {lessonNotes: notes}); renderMetricsOnly(); showBanner('success', 'Az aktuális lecke állapota elmentve a böngészőben.'); };
  document.getElementById('resetLessonStateBtn').onclick = () => { if (!confirm('Biztosan törölni akarod az aktuális lecke helyi állapotát?')) return; clearLessonState(activeLessonId); openLesson(activeLessonId,false); showBanner('warning', 'Az aktuális lecke helyi állapota törölve lett.'); };
  document.getElementById('completeLessonBtn').onclick = () => completeActiveLesson();
  document.getElementById('refreshAllBtn').onclick = async () => { await loadAllTutorialData(); renderFilterButtons(); renderSidebar(); await openLesson(activeLessonId,false); showBanner('success', 'Az oktatóanyag lista és a progress frissítve lett.'); };
  document.addEventListener('keydown', e => {
    if (isTypingContext(e.target) || !activeBlueprint) return;
    const state = readLessonState(activeLessonId), max = safeArray(activeBlueprint.steps).length - 1;
    if (e.key === 'ArrowLeft' && Number(state.currentStep||0) > 0) { e.preventDefault(); writeLessonState(activeLessonId,{currentStep:Number(state.currentStep||0)-1}); openLesson(activeLessonId,false); }
    if (e.key === 'ArrowRight' && Number(state.currentStep||0) < max) { e.preventDefault(); writeLessonState(activeLessonId,{currentStep:Number(state.currentStep||0)+1}); openLesson(activeLessonId,false); }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); writeLessonState(activeLessonId,{lessonNotes: document.getElementById('lessonNotes').value || ''}); renderMetricsOnly(); showBanner('success', 'Állapot elmentve.'); }
  }, {once:false});
}

function startAutosave(){
  if (autosaveTimer) clearInterval(autosaveTimer);
  autosaveTimer = setInterval(() => {
    if (!activeLesson) return;
    writeLessonState(activeLessonId, {lessonNotes: document.getElementById('lessonNotes').value || ''});
    renderMetricsOnly();
  }, 15000);
}

async function loadAllTutorialData(){
  const tutorialRaw = await apiGetJson(`/api/tutorials?user_id=${encodeURIComponent(USER_ID)}`);
  const progressRaw = await apiGetJson(`/api/tutorials/progress?user_id=${encodeURIComponent(USER_ID)}`);

  const tutorialRows = Array.isArray(tutorialRaw) ? tutorialRaw : (tutorialRaw.data || tutorialRaw.tutorials || []);
  tutorialIndex = tutorialRows.map(normalizeApiTutorialItem).filter(x => x.id > 0).sort((a,b) => a.level !== b.level ? a.level - b.level : a.id - b.id);

  tutorialProgressMap = {};
  tutorialIndex.forEach(item => {
    tutorialProgressMap[item.id] = {
      TutorialID: item.id,
      IsCompleted: Number(item.isCompleted || 0),
      StartedAt: item.startedAt || null,
      CompletedAt: item.completedAt || null
    };
  });

  progressSummary = (!Array.isArray(progressRaw) && progressRaw && progressRaw.data) ? progressRaw.data : null;

  if (!tutorialIndex.length) throw new Error('Nem érkezett lecke az API-ból.');

  if (!tutorialIndex.some(x => Number(x.id) === Number(activeLessonId))) {
    activeLessonId = Number(tutorialIndex[0].id);
  }
}

async function initTutorialsPage(){
  bindButtons();
  renderFilterButtons();

  const lessonSearch = document.getElementById('lessonSearch');
  if (lessonSearch) {
    lessonSearch.addEventListener('input', () => {
      lessonSearchTerm = lessonSearch.value || '';
      renderSidebar();
    });
  }

  try{
    await loadAllTutorialData();
    renderOverallStats();
    renderSidebar();
    await openLesson(activeLessonId, true);
    startAutosave();
  }catch(err){
    console.error(err);
    document.getElementById('lessonList').innerHTML = `<div class="emptyState">${esc(err.message || String(err))}</div>`;
    document.getElementById('playerLessonTitle').textContent = 'Betöltési hiba';
    document.getElementById('playerLessonLead').textContent = 'A tutorial API jelenleg nem elérhető vagy hibát ad vissza.';
    showBanner('warning', err.message || String(err));
  }
}

window.addEventListener('beforeunload', () => {
  try{
    writeLessonState(activeLessonId, {lessonNotes: document.getElementById('lessonNotes')?.value || ''});
  }catch(e){}
});
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'hidden') {
    try { writeLessonState(activeLessonId, {lessonNotes: document.getElementById('lessonNotes')?.value || ''}); } catch(e){}
  }
});
document.addEventListener('DOMContentLoaded', initTutorialsPage);
</script>
</body>
</html>
