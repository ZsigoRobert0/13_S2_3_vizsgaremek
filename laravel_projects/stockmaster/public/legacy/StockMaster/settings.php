<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . 'user_service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

requireLogin();

$userId = currentUserId();
$user = getUser($userId);

if ($userId <= 0) { header("Location: login.php"); exit; }

// --- usersettings defaultok ---
$settings = [
  "AutoLogin" => 0,
  "ReceiveNotifications" => 1,
  "PreferredChartTheme" => "dark",
  "PreferredChartInterval" => "1m",

  // ÚJ: limitek (default)
  "NewsLimit" => 8,                 // general mód max
  "NewsPerSymbolLimit" => 3,         // portfolio módban ticker-enként
  "NewsPortfolioTotalLimit" => 20,   // portfolio módban összesen
  "CalendarLimit" => 8,              // naptár max
];

// --- usersettings betöltése (ha nincs oszlop még, akkor fallback: csak a régi mezők) ---
$stmt = @$conn->prepare("SELECT
  AutoLogin, ReceiveNotifications, PreferredChartTheme, PreferredChartInterval,
  NewsLimit, NewsPerSymbolLimit, NewsPortfolioTotalLimit, CalendarLimit
  FROM usersettings WHERE UserID = ? LIMIT 1"
);

if ($stmt) {
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $settings["AutoLogin"] = (int)($row["AutoLogin"] ?? 0);
    $settings["ReceiveNotifications"] = (int)($row["ReceiveNotifications"] ?? 1);
    $settings["PreferredChartTheme"] = (string)($row["PreferredChartTheme"] ?? "dark");
    $settings["PreferredChartInterval"] = (string)($row["PreferredChartInterval"] ?? "1m");

    $settings["NewsLimit"] = (int)($row["NewsLimit"] ?? $settings["NewsLimit"]);
    $settings["NewsPerSymbolLimit"] = (int)($row["NewsPerSymbolLimit"] ?? $settings["NewsPerSymbolLimit"]);
    $settings["NewsPortfolioTotalLimit"] = (int)($row["NewsPortfolioTotalLimit"] ?? $settings["NewsPortfolioTotalLimit"]);
    $settings["CalendarLimit"] = (int)($row["CalendarLimit"] ?? $settings["CalendarLimit"]);
  }
  $stmt->close();
} else {
  // fallback (régi oszlopok)
  $stmt2 = $conn->prepare("SELECT AutoLogin, ReceiveNotifications, PreferredChartTheme, PreferredChartInterval FROM usersettings WHERE UserID = ? LIMIT 1");
  $stmt2->bind_param("i", $userId);
  $stmt2->execute();
  $res2 = $stmt2->get_result();
  if ($row = $res2->fetch_assoc()) {
    $settings["AutoLogin"] = (int)($row["AutoLogin"] ?? 0);
    $settings["ReceiveNotifications"] = (int)($row["ReceiveNotifications"] ?? 1);
    $settings["PreferredChartTheme"] = (string)($row["PreferredChartTheme"] ?? "dark");
    $settings["PreferredChartInterval"] = (string)($row["PreferredChartInterval"] ?? "1m");
  }
  $stmt2->close();
}

// clamp (biztonság)
$settings["NewsLimit"] = max(3, min(30, (int)$settings["NewsLimit"]));
$settings["NewsPerSymbolLimit"] = max(1, min(10, (int)$settings["NewsPerSymbolLimit"]));
$settings["NewsPortfolioTotalLimit"] = max(5, min(60, (int)$settings["NewsPortfolioTotalLimit"]));
$settings["CalendarLimit"] = max(3, min(60, (int)$settings["CalendarLimit"]));

// --- portfólió tickerek (nyitott pozik) a hírek szűréshez ---
$tickers = [];
$q = $conn->prepare("
  SELECT DISTINCT a.Symbol
  FROM positions p
  JOIN assets a ON a.ID = p.AssetID
  WHERE p.UserID = ? AND p.IsOpen = 1
  ORDER BY a.Symbol
  LIMIT 30
");
$q->bind_param("i", $userId);
$q->execute();
$r = $q->get_result();
while ($rr = $r->fetch_assoc()) $tickers[] = $rr["Symbol"];
$q->close();

$tickerStr = implode(", ", $tickers);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>StockMaster — Beállítások</title>

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

  /* Fix magasság: ne tolja le a Mentést */
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

/* --- Dark select fix + nyíl --- */
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
.limitSelect:hover{ border-color: rgba(255,255,255,0.14); }
.limitSelect:focus{
  outline:none;
  border-color: rgba(255,255,255,0.22);
  box-shadow: 0 0 0 3px rgba(255,255,255,0.06), 0 10px 24px rgba(2,6,23,0.45);
}
.limitSelect option{ background:#0b1220; color:#e6eef8; }

.list{
  flex:1;
  overflow:auto;
  padding-right:6px;
}
.list::-webkit-scrollbar{ width:6px; }
.list::-webkit-scrollbar-thumb{ background:rgba(148,163,184,0.5); border-radius:999px; }
.list::-webkit-scrollbar-thumb:hover{ background:rgba(148,163,184,0.9); }
.list::-webkit-scrollbar-track{ background:transparent; }

.newsItem{
  border:1px solid var(--line);
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.03));
  border-radius:14px;
  padding:12px;
  margin-bottom:10px;
}
.newsTitle{ font-weight:900; font-size:13px; line-height:1.25; margin:0 0 8px; }
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
.newsDesc{
  color:rgba(230,238,248,0.85);
  font-size:12px;
  line-height:1.45;
  margin:0;
}

.eventCard{
  border:1px solid var(--line);
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.03));
  border-radius:14px;
  padding:12px;
  margin-bottom:10px;
}
.eventTitle{ font-weight:900; font-size:13px; margin:0 0 8px; }
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
      <div class="sub">Mentés a <strong>usersettings</strong> táblába</div>
    </div>
    <a class="back" href="index.php">← Vissza a főoldalra</a>
  </div>

  <div class="sectionTitle">Piaci hírek és Piaci naptár</div>
  <div class="sectionHint"></div>

  <div class="grid2">

    <!-- ✅ CALENDAR (bal) -->
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
                    $sel = ((int)$settings["CalendarLimit"] === $v) ? "selected" : "";
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

    <!-- ✅ NEWS (jobb) -->
    <div class="card">
      <div class="cardHead">
        <div>
          <div class="cardTitle">Piaci hírek</div>
          <div class="cardMeta">Portfólió tickerek: <span class="ok"><?php echo htmlspecialchars($tickerStr ?: "—"); ?></span></div>
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
                    // general módban NewsLimit, portfolio módban NewsPortfolioTotalLimit
                    // UI induljon portfolio értékkel, mert az az alap mód
                    $sel = ((int)$settings["NewsPortfolioTotalLimit"] === $v) ? "selected" : "";
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
                    $sel = ((int)$settings["NewsPerSymbolLimit"] === $v) ? "selected" : "";
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

// ---- helpers ----
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

// ---- NEWS ----
async function loadNews(){
  newsList.innerHTML = `<div class="cardMeta">Betöltés…</div>`;
  try{
    const want = parseInt(newsLimitSel.value || "20", 10);
    const perSymbol = parseInt(newsPerSymbolSel.value || "3", 10);

    const url = new URL("get_market_news.php", window.location.href);
    url.searchParams.set("mode", newsMode);

    // ✅ backend paraméterezés
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
      const sym  = it.related || it.symbol || "";

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

  // portfolio módban jellemzően több elem kell → hagyjuk a mostani értéket
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

// ---- CALENDAR ----
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

    const url = new URL("get_market_calendar.php", window.location.href);

    // ✅ backend paraméterezés
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

// ---- SAVE SETTINGS ----
saveBtn.onclick = async () => {
  saveToast.textContent = "Mentés…";
  try{
    const payload = {
      AutoLogin: <?php echo (int)$settings["AutoLogin"]; ?>,
      ReceiveNotifications: <?php echo (int)$settings["ReceiveNotifications"]; ?>,
      PreferredChartTheme: <?php echo json_encode($settings["PreferredChartTheme"]); ?>,
      PreferredChartInterval: <?php echo json_encode($settings["PreferredChartInterval"]); ?>,

      // ✅ ÚJ: limitek mentése usersettingsbe
      NewsLimit: <?php echo (int)$settings["NewsLimit"]; ?>, // general alap
      NewsPerSymbolLimit: parseInt(newsPerSymbolSel.value || "3", 10),
      NewsPortfolioTotalLimit: parseInt(newsLimitSel.value || "20", 10),
      CalendarLimit: parseInt(calLimitSel.value || "8", 10)
    };

    // Ha general módban vagy, akkor a "Látszódjon" érték legyen a NewsLimit (general)
    if (newsMode === "general") {
      payload.NewsLimit = parseInt(newsLimitSel.value || "8", 10);
    }

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

    // ha backend warningot küld (pl. hiányzó oszlopok), azt is jelezzük
    if (data.warning) {
      saveToast.textContent = "Mentve, de: " + data.warning;
      setTimeout(()=> saveToast.textContent="", 4200);
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
