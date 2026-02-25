<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/user_service.php';

requireLogin();

$userId = currentUserId();
if ($userId <= 0) legacy_redirect('login.php');

function baseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    return $scheme . '://' . $host;
}

function apiGetJson(string $url): ?array {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'timeout' => 3,
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;
    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

// Defaults (legacy kulcsok, hogy a meglévő UI/JS stabil legyen)
$settings = [
  'AutoLogin' => 0,
  'ReceiveNotifications' => 1,
  'PreferredChartTheme' => 'dark',
  'PreferredChartInterval' => '1m',
  'NewsLimit' => 8,
  'NewsPerSymbolLimit' => 3,
  'NewsPortfolioTotalLimit' => 20,
  'CalendarLimit' => 8,
];

// Laravel API settings betöltés
$apiBase = baseUrl();
$apiSettings = apiGetJson($apiBase . "/api/settings?user_id=" . intval($userId));

if ($apiSettings && !empty($apiSettings['ok']) && is_array($apiSettings['data'] ?? null)) {
    $s = $apiSettings['data'];
    $settings['AutoLogin'] = (int)($s['auto_login'] ?? $settings['AutoLogin']);
    $settings['ReceiveNotifications'] = (int)($s['receive_notifications'] ?? $settings['ReceiveNotifications']);
    $settings['PreferredChartTheme'] = (string)($s['chart_theme'] ?? $settings['PreferredChartTheme']);
    $settings['PreferredChartInterval'] = (string)($s['chart_interval'] ?? $settings['PreferredChartInterval']);

    $settings['NewsLimit'] = (int)($s['news_limit'] ?? $settings['NewsLimit']);
    $settings['NewsPerSymbolLimit'] = (int)($s['news_per_symbol_limit'] ?? $settings['NewsPerSymbolLimit']);
    $settings['NewsPortfolioTotalLimit'] = (int)($s['news_portfolio_total_limit'] ?? $settings['NewsPortfolioTotalLimit']);
    $settings['CalendarLimit'] = (int)($s['calendar_limit'] ?? $settings['CalendarLimit']);
}

// clamp
$settings['NewsLimit'] = max(3, min(30, (int)$settings['NewsLimit']));
$settings['NewsPerSymbolLimit'] = max(1, min(10, (int)$settings['NewsPerSymbolLimit']));
$settings['NewsPortfolioTotalLimit'] = max(5, min(60, (int)$settings['NewsPortfolioTotalLimit']));
$settings['CalendarLimit'] = max(3, min(60, (int)$settings['CalendarLimit']));

// Portfolio tickerek (Laravel /api/state)
$tickers = [];
$apiState = apiGetJson($apiBase . "/api/state?user_id=" . intval($userId));
if ($apiState && !empty($apiState['ok'])) {
    $pos = $apiState['positions'] ?? ($apiState['data']['positions'] ?? null);
    if (is_array($pos)) {
        foreach ($pos as $p) {
            $sym = strtoupper((string)($p['Symbol'] ?? $p['symbol'] ?? ''));
            if ($sym !== '') $tickers[$sym] = true;
        }
    }
}
$tickerStr = implode(', ', array_keys($tickers));
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>StockMaster — Beállítások</title>

<link rel="stylesheet" href="app.css?v=1">

<style>
:root{
  --bg:#0f1724;
  --panel:#0b1220;
  --text:#e6eef8;
  --muted:#98a2b3;
  --glass: rgba(255,255,255,0.03);
  --green:#16a34a;
  --line: rgba(255,255,255,0.06);
}
html,body{
  height:100%;
  margin:0;
  font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;
  background:linear-gradient(180deg,var(--bg),#041025);
  color:var(--text);
}
.wrap{
  max-width:1200px;
  margin:0 auto;
  padding:22px 18px 40px;
}
.topbar{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:14px;
}
.h1{ font-size:28px; font-weight:800; margin:0; letter-spacing:.2px; }
.sub{ margin-top:6px; color:var(--muted); font-size:13px; }
.back{
  background:var(--glass);
  border:1px solid var(--line);
  color:var(--text);
  text-decoration:none;
  padding:10px 12px;
  border-radius:12px;
  display:inline-flex;
  align-items:center;
  gap:8px;
  cursor:pointer;
}

.sectionTitle{ margin:22px 0 8px; font-size:16px; font-weight:800; }
.sectionHint{ margin:0 0 14px; color:var(--muted); font-size:13px; }

.grid2{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:14px;
}
.card{
  background:var(--panel);
  border:1px solid var(--line);
  border-radius:16px;
  padding:14px;
  box-shadow:0 6px 18px rgba(2,6,23,0.55);
  height: 520px;
  display:flex;
  flex-direction:column;
}
.cardHead{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:10px;
  margin-bottom:10px;
}
.cardTitle{ font-weight:800; font-size:13px; letter-spacing:.2px; }
.cardMeta{ color:var(--muted); font-size:12px; margin-top:4px; }

.tabs{
  display:flex;
  gap:8px;
  align-items:center;
  flex-wrap:wrap;
  justify-content:flex-end;
}
.tabBtn{
  background:var(--glass);
  border:1px solid var(--line);
  color:var(--text);
  padding:8px 10px;
  border-radius:12px;
  cursor:pointer;
  font-weight:700;
  font-size:12px;
}
.tabBtn.active{
  outline: 2px solid rgba(255,255,255,0.08);
  background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(0,0,0,0.06));
}
.refreshBtn{
  background:var(--glass);
  border:1px solid var(--line);
  color:var(--text);
  padding:9px 12px;
  border-radius:12px;
  cursor:pointer;
  font-weight:900;
  font-size:12px;
}
.limitBox{
  display:flex;
  align-items:center;
  gap:8px;
}
.limitLabel{
  color:var(--muted);
  font-weight:900;
  font-size:12px;
}
.limitWrap{
  position: relative;
  display:inline-flex;
  align-items:center;
}
.limitWrap::after{
  content: "▾";
  position:absolute;
  right:12px;
  pointer-events:none;
  color: rgba(230,238,248,0.85);
  font-size:12px;
  font-weight:900;
}
.limitSelect{
  -webkit-appearance:none;
  -moz-appearance:none;
  appearance:none;
  background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(0,0,0,0.08));
  border: 1px solid var(--line);
  color: var(--text);
  padding:8px 34px 8px 12px;
  border-radius:12px;
  font-weight:900;
  font-size:12px;
  line-height:1;
  box-shadow: 0 8px 18px rgba(2,6,23,0.35);
  cursor:pointer;
  backdrop-filter: blur(6px);
}
.limitSelect option{ background:#0b1220; color:#e6eef8; }

.list{
  flex:1;
  overflow:auto;
  padding-right:6px;
}
.newsItem,.eventCard{
  border:1px solid var(--line);
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.03));
  border-radius:14px;
  padding:12px;
  margin-bottom:10px;
}
.newsTitle,.eventTitle{ font-weight:900; font-size:13px; line-height:1.25; margin:0 0 8px; }
.newsRow{
  display:flex;
  gap:10px;
  align-items:center;
  color:var(--muted);
  font-size:12px;
  margin-bottom:8px;
  flex-wrap:wrap;
}
.newsLink{
  color:var(--text);
  font-weight:900;
  text-decoration:none;
  border-bottom:1px dashed rgba(255,255,255,0.25);
}
.newsDesc{ color:rgba(230,238,248,0.85); font-size:12px; line-height:1.45; margin:0; }
.eventMeta{ display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px; }
.pill{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:6px 10px;
  border-radius:999px;
  border:1px solid var(--line);
  background:var(--glass);
  font-size:12px;
  color:rgba(230,238,248,0.92);
  font-weight:900;
}
.lines{ color:rgba(230,238,248,0.86); font-size:12px; line-height:1.5; }
.lines .muted{ color:var(--muted); }

.actionsRow{
  display:flex;
  gap:10px;
  align-items:center;
  justify-content:flex-start;
  margin-top:14px;
}
.saveBtn{
  background:linear-gradient(90deg,var(--green),#68d391);
  color:#04260b;
  border:0;
  padding:10px 14px;
  border-radius:12px;
  cursor:pointer;
  font-weight:900;
}
.toast{ margin-left:10px; color:var(--muted); font-size:12px; }
.err{ color:#ffb4b4; font-weight:900; }
.ok{ color:#b7ffcf; font-weight:900; }

@media (max-width: 980px){
  .grid2{ grid-template-columns:1fr; }
  .card{ height: 520px; }
}
</style>
</head>

<body>
<div class="wrap">
  <div class="topbar">
    <div>
      <h1 class="h1">Beállítások</h1>
      <div class="sub">Piaci hírek és Piaci naptár (Laravel API)</div>
    </div>
    <a class="back" href="index.php">← Vissza a főoldalra</a>
  </div>

  <div class="sectionTitle">Piaci hírek és Piaci naptár</div>
  <div class="sectionHint"></div>

  <div class="grid2">

    <div class="card">
      <div class="cardHead">
        <div>
          <div class="cardTitle">Piaci naptár</div>
          <div class="cardMeta">Következő 14 nap • <span class="muted" id="calPeriod">—</span></div>
        </div>

        <div class="tabs">
          <div class="limitBox">
            <span class="limitLabel">Látszódjon:</span>
            <div class="limitWrap">
              <select class="limitSelect" id="calLimit">
                <?php
                $opts = [6,8,10,15,20,30,60];
                foreach ($opts as $v) {
                  $sel = ((int)$settings['CalendarLimit'] === $v) ? 'selected' : '';
                  echo "<option value=\"{$v}\" {$sel}>{$v}</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <button class="refreshBtn" id="calRefresh">Frissítés</button>
        </div>
      </div>

      <div class="list" id="calList">
        <div class="cardMeta">Betöltés…</div>
      </div>
    </div>

    <div class="card">
      <div class="cardHead">
        <div>
          <div class="cardTitle">Piaci hírek</div>
          <div class="cardMeta">Portfólió tickerek: <span class="ok"><?php echo htmlspecialchars($tickerStr ?: '—', ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>

        <div class="tabs">
          <button class="tabBtn active" id="newsModePortfolio">Portfolio</button>
          <button class="tabBtn" id="newsModeGeneral">General</button>

          <div class="limitBox">
            <span class="limitLabel">Látszódjon:</span>
            <div class="limitWrap">
              <select class="limitSelect" id="newsLimit">
                <?php
                $opts = [6,8,10,15,20,30,60];
                foreach ($opts as $v) {
                  $sel = ((int)$settings['NewsPortfolioTotalLimit'] === $v) ? 'selected' : '';
                  echo "<option value=\"{$v}\" {$sel}>{$v}</option>";
                }
                ?>
              </select>
            </div>
          </div>

          <div class="limitBox">
            <span class="limitLabel">ticker:</span>
            <div class="limitWrap">
              <select class="limitSelect" id="newsPerSymbol">
                <?php
                $opts = [1,2,3,4,5,6,8,10];
                foreach ($opts as $v) {
                  $sel = ((int)$settings['NewsPerSymbolLimit'] === $v) ? 'selected' : '';
                  echo "<option value=\"{$v}\" {$sel}>{$v}</option>";
                }
                ?>
              </select>
            </div>
          </div>

          <button class="refreshBtn" id="newsRefresh">Frissítés</button>
        </div>
      </div>

      <div class="list" id="newsList">
        <div class="cardMeta">Betöltés…</div>
      </div>
    </div>

  </div>

  <div class="actionsRow">
    <button class="saveBtn" id="saveBtn">Mentés</button>
    <span class="toast" id="saveToast"></span>
  </div>

</div>

<script>
const USER_ID = <?= (int)$userId ?>;

const newsList = document.getElementById("newsList");
const calList  = document.getElementById("calList");
const calPeriod = document.getElementById("calPeriod");

const newsModePortfolio = document.getElementById("newsModePortfolio");
const newsModeGeneral   = document.getElementById("newsModeGeneral");
const newsRefresh       = document.getElementById("newsRefresh");
const calRefresh        = document.getElementById("calRefresh");

const newsLimitSel = document.getElementById("newsLimit");
const newsPerSymbolSel = document.getElementById("newsPerSymbol");
const calLimitSel  = document.getElementById("calLimit");

const saveBtn   = document.getElementById("saveBtn");
const saveToast = document.getElementById("saveToast");

let newsMode = "portfolio";

function esc(s){
  return String(s ?? "").replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
function fmtTime(ts){
  if(!ts) return "";
  try{
    const d = new Date(ts * 1000);
    return d.toLocaleString("hu-HU");
  }catch(e){ return ""; }
}
function fmtNum(x, digits=2){
  if (x === null || x === undefined || x === "") return "—";
  const n = Number(x);
  if (Number.isNaN(n)) return "—";
  return n.toFixed(digits);
}
function pill(text){
  return `<span class="pill">${esc(text)}</span>`;
}

// ===== NEWS via Laravel API =====
async function loadNews(){
  newsList.innerHTML = `<div class="cardMeta">Betöltés…</div>`;
  try{
    const want = parseInt(newsLimitSel.value || "20", 10);
    const perSymbol = parseInt(newsPerSymbolSel.value || "3", 10);

    const url = new URL("/api/news", window.location.origin);
    url.searchParams.set("user_id", String(USER_ID));
    url.searchParams.set("mode", newsMode);
    url.searchParams.set("limit", String(want));
    url.searchParams.set("perSymbol", String(perSymbol));

    const r = await fetch(url.toString(), { cache:"no-store" });
    const data = await r.json();

    if(!data.ok){
      newsList.innerHTML = `<div class="cardMeta err">${esc(data.error || "Hiba")}</div>`;
      return;
    }

    const items = Array.isArray(data.items) ? data.items : [];
    if(items.length === 0){
      newsList.innerHTML = `<div class="cardMeta">Nincs találat.</div>`;
      return;
    }

    newsList.innerHTML = items.map(it => {
      const title = it.headline || it.title || "—";
      const src = it.source || "—";
      const dt = it.datetime ? fmtTime(it.datetime) : (it.datetimeStr || "");
      const link = it.url || it.link || "#";
      const desc = it.summary || it.description || "";
      const sym  = it.symbol || it.related || "";

      return `
        <div class="newsItem">
          <div class="newsTitle">${esc(sym ? `[${sym}] ` : "")}${esc(title)}</div>
          <div class="newsRow">
            <span>${esc(src)}</span>
            <span>•</span>
            <span>${esc(dt)}</span>
            <a class="newsLink" href="${esc(link)}" target="_blank" rel="noopener">Megnyitás</a>
          </div>
          <p class="newsDesc">${esc(desc).slice(0, 320)}${desc.length > 320 ? "…" : ""}</p>
        </div>
      `;
    }).join("");

  }catch(e){
    newsList.innerHTML = `<div class="cardMeta err">Hálózati hiba.</div>`;
  }
}

newsModePortfolio.onclick = () => {
  newsMode = "portfolio";
  newsModePortfolio.classList.add("active");
  newsModeGeneral.classList.remove("active");
  loadNews();
};
newsModeGeneral.onclick = () => {
  newsMode = "general";
  newsModeGeneral.classList.add("active");
  newsModePortfolio.classList.remove("active");
  loadNews();
};
newsRefresh.onclick = loadNews;
newsLimitSel.onchange = loadNews;
newsPerSymbolSel.onchange = loadNews;

// ===== CALENDAR via Laravel API =====
function renderCalendarItem(it){
  if (it.type === "earnings" || it.symbol) {
    const timeLabel = it.time ? String(it.time).toUpperCase() : "—";
    const spr = (it.epsSurprisePercent !== null && it.epsSurprisePercent !== undefined && it.epsSurprisePercent !== "")
      ? `${Number(it.epsSurprisePercent).toFixed(2)}%`
      : "—";

    const epsLine = `EPS: <span class="muted">actual</span> ${fmtNum(it.epsActual)}  •  <span class="muted">est</span> ${fmtNum(it.epsEstimate)}`;
    const revLine = `REV: <span class="muted">actual</span> ${fmtNum(it.revenueActual, 0)}  •  <span class="muted">est</span> ${fmtNum(it.revenueEstimate, 0)}`;

    return `
      <div class="eventCard">
        <div class="eventTitle">${esc(it.date)} • ${esc(it.title || (it.symbol + " earnings"))}</div>
        <div class="eventMeta">
          ${pill("Time: " + timeLabel)}
          ${pill("Surprise: " + spr)}
          ${it.quarter ? pill("Q: " + it.quarter) : ""}
        </div>
        <div class="lines">
          <div>${epsLine}</div>
          <div>${revLine}</div>
        </div>
      </div>
    `;
  }

  const title = `${it.date || "—"} • ${it.country || "—"} • ${it.event || it.title || "—"}`;
  const impact = it.impact || "—";
  const a = (it.actual ?? "—");
  const f = (it.forecast ?? "—");
  const p = (it.previous ?? "—");

  return `
    <div class="eventCard">
      <div class="eventTitle">${esc(title)}</div>
      <div class="eventMeta">
        ${pill("Impact: " + impact)}
      </div>
      <div class="lines">
        <div><span class="muted">Actual</span>: ${esc(a)}  •  <span class="muted">Forecast</span>: ${esc(f)}  •  <span class="muted">Prev</span>: ${esc(p)}</div>
      </div>
    </div>
  `;
}

async function loadCalendar(){
  calList.innerHTML = `<div class="cardMeta">Betöltés…</div>`;
  try{
    const want = parseInt(calLimitSel.value || "8", 10);

    const url = new URL("/api/calendar", window.location.origin);
    url.searchParams.set("user_id", String(USER_ID));
    url.searchParams.set("limit", String(want));

    const r = await fetch(url.toString(), { cache:"no-store" });
    const data = await r.json();

    if(!data.ok){
      calList.innerHTML = `<div class="cardMeta err">${esc(data.error || "Hiba")}</div>`;
      calPeriod.textContent = "—";
      return;
    }

    calPeriod.textContent = `${data.from || "—"} → ${data.to || "—"} (${data.mode || "—"})`;

    const items = Array.isArray(data.items) ? data.items : [];
    if(items.length === 0){
      calList.innerHTML = `<div class="cardMeta">Nincs esemény.</div>`;
      return;
    }

    calList.innerHTML = items.map(renderCalendarItem).join("");

  }catch(e){
    calList.innerHTML = `<div class="cardMeta err">Hálózati hiba.</div>`;
    calPeriod.textContent = "—";
  }
}

calRefresh.onclick = loadCalendar;
calLimitSel.onchange = loadCalendar;

// ===== SAVE SETTINGS via legacy proxy (-> Laravel /api/settings) =====
saveBtn.onclick = async () => {
  saveToast.textContent = "Mentés…";
  try{
    const payload = {
      NewsPerSymbolLimit: parseInt(newsPerSymbolSel.value || "3", 10),
      NewsPortfolioTotalLimit: parseInt(newsLimitSel.value || "20", 10),
      CalendarLimit: parseInt(calLimitSel.value || "8", 10),
      PreferredChartTheme: <?= json_encode($settings['PreferredChartTheme']) ?>,
      PreferredChartInterval: <?= json_encode($settings['PreferredChartInterval']) ?>,
      AutoLogin: <?= (int)$settings['AutoLogin'] ?>,
      ReceiveNotifications: <?= (int)$settings['ReceiveNotifications'] ?>,
    };

    const r = await fetch("save_usersettings.php", {
      method: "POST",
      headers: { "Content-Type":"application/json" },
      body: JSON.stringify(payload)
    });

    const data = await r.json();
    if(!data.ok){
      saveToast.textContent = "Hiba: " + (data.error || "nem sikerült");
      return;
    }

    saveToast.textContent = "Mentve!";
    setTimeout(()=> saveToast.textContent="", 2000);

  }catch(e){
    saveToast.textContent = "Hálózati hiba.";
  }
};

loadNews();
loadCalendar();
</script>
</body>
</html>