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

// Defaults
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

// Laravel API settings
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

// Clamp
$settings['NewsLimit'] = max(3, min(30, (int)$settings['NewsLimit']));
$settings['NewsPerSymbolLimit'] = max(1, min(10, (int)$settings['NewsPerSymbolLimit']));
$settings['NewsPortfolioTotalLimit'] = max(5, min(60, (int)$settings['NewsPortfolioTotalLimit']));
$settings['CalendarLimit'] = max(3, min(60, (int)$settings['CalendarLimit']));

// Portfolio tickers
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
<title>Pénzkút — Beállítások</title>

<link rel="stylesheet" href="app.css?v=2">

<style>
:root{
  --bg:#0f1724;
  --panel:#0b1220;
  --panel-2:#0d1628;
  --text:#e6eef8;
  --muted:#98a2b3;
  --glass: rgba(255,255,255,0.03);
  --green:#16a34a;
  --green-2:#68d391;
  --blue:#60a5fa;
  --amber:#f59e0b;
  --rose:#fb7185;
  --line: rgba(255,255,255,0.06);
  --line-strong: rgba(255,255,255,0.12);
  --shadow: 0 6px 18px rgba(2,6,23,0.55);
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

.sectionTitle{
  margin:22px 0 8px;
  font-size:16px;
  font-weight:800;
}
.sectionHint{
  margin:0 0 14px;
  color:var(--muted);
  font-size:13px;
}

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
  box-shadow:var(--shadow);
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
  background:linear-gradient(90deg,var(--green),var(--green-2));
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

/* ===== TUTORIAL ===== */
.tutorialShell{
  background: radial-gradient(circle at top left, rgba(96,165,250,0.07), transparent 28%), var(--panel);
  border:1px solid var(--line);
  border-radius:20px;
  padding:16px;
  box-shadow:var(--shadow);
  margin-bottom:18px;
}
.tutorialHead{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:14px;
  margin-bottom:14px;
}
.tutorialHeadLeft{
  min-width:0;
}
.tutorialTitle{
  margin:0;
  font-size:20px;
  font-weight:900;
  letter-spacing:.2px;
}
.tutorialSub{
  margin-top:6px;
  color:var(--muted);
  font-size:13px;
  line-height:1.45;
}
.tutorialHeadRight{
  display:flex;
  gap:10px;
  align-items:center;
  flex-wrap:wrap;
  justify-content:flex-end;
}
.tutorialGhostBtn,
.tutorialPrimaryBtn,
.tutorialMiniBtn,
.tutorialActionBtn{
  border-radius:12px;
  cursor:pointer;
  font-weight:900;
  transition:.18s ease;
}
.tutorialGhostBtn{
  background:var(--glass);
  border:1px solid var(--line);
  color:var(--text);
  padding:10px 12px;
}
.tutorialPrimaryBtn{
  background:linear-gradient(90deg, rgba(22,163,74,0.95), rgba(104,211,145,0.95));
  color:#03250a;
  border:0;
  padding:10px 12px;
}
.tutorialPrimaryBtn:hover,
.tutorialGhostBtn:hover,
.tutorialMiniBtn:hover,
.tutorialActionBtn:hover{
  transform:translateY(-1px);
}

.tutorialTopStats{
  display:grid;
  grid-template-columns: minmax(260px, 1.4fr) 1fr 1fr 1fr;
  gap:12px;
  margin-bottom:14px;
}
.tStat{
  border:1px solid var(--line);
  border-radius:16px;
  background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(0,0,0,0.05));
  padding:14px;
  min-height:90px;
}
.tStatLabel{
  color:var(--muted);
  font-size:12px;
  font-weight:800;
  margin-bottom:8px;
}
.tStatValue{
  font-size:26px;
  font-weight:900;
  line-height:1;
  margin-bottom:8px;
}
.tStatSmall{
  color:rgba(230,238,248,0.86);
  font-size:12px;
}
.progressOuter{
  width:100%;
  height:12px;
  border-radius:999px;
  background:rgba(255,255,255,0.05);
  border:1px solid var(--line);
  overflow:hidden;
}
.progressInner{
  height:100%;
  border-radius:999px;
  background:linear-gradient(90deg, #22c55e, #60a5fa);
  width:0%;
  transition:width .25s ease;
}
.levelProgress{
  margin-top:8px;
}
.levelProgress .progressOuter{
  height:8px;
}
.tutorialToolbar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  flex-wrap:wrap;
  margin-bottom:14px;
}
.tutorialTabs{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}
.tutorialTab{
  border:1px solid var(--line);
  background:var(--glass);
  color:var(--text);
  border-radius:999px;
  padding:9px 14px;
  cursor:pointer;
  font-weight:900;
  font-size:12px;
}
.tutorialTab.active{
  background:linear-gradient(90deg, rgba(96,165,250,0.18), rgba(34,197,94,0.16));
  border-color:rgba(96,165,250,0.35);
}
.tutorialToolbarInfo{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  align-items:center;
}
.infoChip{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:8px 12px;
  border-radius:999px;
  border:1px solid var(--line);
  background:var(--glass);
  font-size:12px;
  color:rgba(230,238,248,0.92);
  font-weight:900;
}

.tutorialList{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:12px;
}
.tutorialItem{
  border:1px solid var(--line);
  background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(0,0,0,0.04));
  border-radius:16px;
  padding:14px;
  display:flex;
  flex-direction:column;
  gap:12px;
  min-height:220px;
}
.tutorialItemTop{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
}
.tutorialItemTitle{
  margin:0;
  font-size:16px;
  font-weight:900;
  line-height:1.25;
}
.tutorialBadge{
  display:inline-flex;
  align-items:center;
  padding:7px 10px;
  border-radius:999px;
  font-size:11px;
  font-weight:900;
  border:1px solid var(--line);
  white-space:nowrap;
}
.badgeNotStarted{
  background:rgba(148,163,184,0.08);
  color:#cbd5e1;
}
.badgeInProgress{
  background:rgba(245,158,11,0.12);
  color:#fde68a;
}
.badgeCompleted{
  background:rgba(34,197,94,0.12);
  color:#bbf7d0;
}
.tutorialTags{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}
.tagChip{
  display:inline-flex;
  align-items:center;
  padding:6px 10px;
  border-radius:999px;
  background:rgba(255,255,255,0.03);
  border:1px solid var(--line);
  color:rgba(230,238,248,0.86);
  font-size:11px;
  font-weight:800;
}
.tutorialPreview{
  color:rgba(230,238,248,0.88);
  font-size:13px;
  line-height:1.55;
  flex:1;
}
.tutorialMetaRow{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  color:var(--muted);
  font-size:12px;
}
.tutorialActions{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}
.tutorialMiniBtn,
.tutorialActionBtn{
  padding:9px 12px;
  border:1px solid var(--line);
  background:var(--glass);
  color:var(--text);
}
.tutorialActionBtn.complete{
  background:linear-gradient(90deg, rgba(22,163,74,0.18), rgba(104,211,145,0.12));
  border-color:rgba(22,163,74,0.35);
}
.tutorialActionBtn.start{
  background:linear-gradient(90deg, rgba(96,165,250,0.16), rgba(59,130,246,0.10));
  border-color:rgba(96,165,250,0.35);
}
.tutorialEmpty{
  border:1px dashed var(--line-strong);
  border-radius:16px;
  padding:22px;
  color:var(--muted);
  font-size:14px;
  text-align:center;
  grid-column:1 / -1;
}

@media (max-width: 1100px){
  .tutorialTopStats{
    grid-template-columns:1fr 1fr;
  }
  .tutorialList{
    grid-template-columns:1fr;
  }
}

@media (max-width: 980px){
  .grid2{ grid-template-columns:1fr; }
  .card{ height:520px; }
}

@media (max-width: 760px){
  .topbar{
    flex-direction:column;
    align-items:flex-start;
  }
  .tutorialHead{
    flex-direction:column;
    align-items:flex-start;
  }
  .tutorialHeadRight{
    justify-content:flex-start;
  }
  .tutorialTopStats{
    grid-template-columns:1fr;
  }
  .tutorialToolbar{
    flex-direction:column;
    align-items:flex-start;
  }
}
</style>
</head>

<body>
<div class="wrap">
  <div class="topbar">
    <div>
      <h1 class="h1">Beállítások</h1>
      <div class="sub">Piaci hírek, piaci naptár és oktatóanyagok (Laravel API)</div>
    </div>
    <a class="back" href="index.php">← Vissza a főoldalra</a>
  </div>

  <div class="sectionTitle">Oktatóanyagok</div>
  <div class="sectionHint">A hírek előtt itt tudod követni a tanulási előrehaladást, szintenként végigmenni az anyagokon. Az interaktív tanulás külön oldalon nyílik meg.</div>

  <div class="tutorialShell">
    <div class="tutorialHead">
      <div class="tutorialHeadLeft">
        <h2 class="tutorialTitle">Pénzkút Oktatóközpont</h2>
        <div class="tutorialSub">
          A meglévő tutorial API-ra kötve. A státusz a <strong>tutorialprogress</strong> táblába íródik, az interaktív tananyag pedig külön lesson-player oldalon nyílik meg.
        </div>
      </div>

      <div class="tutorialHeadRight">
        <button class="tutorialGhostBtn" id="tutorialRefreshBtn">Frissítés</button>
        <button class="tutorialPrimaryBtn" id="tutorialOpenFirstBtn">Első lecke megnyitása</button>
      </div>
    </div>

    <div class="tutorialTopStats">
      <div class="tStat">
        <div class="tStatLabel">Összesített haladás</div>
        <div class="tStatValue" id="tutorialOverallPercent">0%</div>
        <div class="progressOuter">
          <div class="progressInner" id="tutorialOverallBar"></div>
        </div>
        <div class="tStatSmall" id="tutorialOverallText">0 / 0 kész</div>
      </div>

      <div class="tStat">
        <div class="tStatLabel">Kezdő szint</div>
        <div class="tStatValue" id="tutorialBeginnerPercent">0%</div>
        <div class="levelProgress">
          <div class="progressOuter">
            <div class="progressInner" id="tutorialBeginnerBar"></div>
          </div>
        </div>
        <div class="tStatSmall" id="tutorialBeginnerText">0 / 0 kész</div>
      </div>

      <div class="tStat">
        <div class="tStatLabel">Haladó szint</div>
        <div class="tStatValue" id="tutorialAdvancedPercent">0%</div>
        <div class="levelProgress">
          <div class="progressOuter">
            <div class="progressInner" id="tutorialAdvancedBar"></div>
          </div>
        </div>
        <div class="tStatSmall" id="tutorialAdvancedText">0 / 0 kész</div>
      </div>

      <div class="tStat">
        <div class="tStatLabel">Profi szint</div>
        <div class="tStatValue" id="tutorialProPercent">0%</div>
        <div class="levelProgress">
          <div class="progressOuter">
            <div class="progressInner" id="tutorialProBar"></div>
          </div>
        </div>
        <div class="tStatSmall" id="tutorialProText">0 / 0 kész</div>
      </div>
    </div>

    <div class="tutorialToolbar">
      <div class="tutorialTabs">
        <button class="tutorialTab active" data-level="1" id="tutorialTab1">Kezdő</button>
        <button class="tutorialTab" data-level="2" id="tutorialTab2">Haladó</button>
        <button class="tutorialTab" data-level="3" id="tutorialTab3">Profi</button>
        <button class="tutorialTab" data-level="0" id="tutorialTab0">Összes</button>
      </div>

      <div class="tutorialToolbarInfo">
        <span class="infoChip" id="tutorialLevelInfo">Aktív szint: Kezdő</span>
        <span class="infoChip" id="tutorialCountInfo">Betöltés…</span>
      </div>
    </div>

    <div class="tutorialList" id="tutorialList">
      <div class="tutorialEmpty">Betöltés…</div>
    </div>
  </div>

  <div class="sectionTitle">Piaci hírek és Piaci naptár</div>
  <div class="sectionHint"></div>

  <div class="grid2">

    <div class="card">
      <div class="cardHead">
        <div>
          <div class="cardTitle">Piaci naptár</div>
          <div class="cardMeta">Következő 14 nap • <span id="calPeriod">—</span></div>
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

const tutorialList = document.getElementById("tutorialList");
const tutorialRefreshBtn = document.getElementById("tutorialRefreshBtn");
const tutorialOpenFirstBtn = document.getElementById("tutorialOpenFirstBtn");
const tutorialCountInfo = document.getElementById("tutorialCountInfo");
const tutorialLevelInfo = document.getElementById("tutorialLevelInfo");

const tutorialOverallPercent = document.getElementById("tutorialOverallPercent");
const tutorialOverallBar = document.getElementById("tutorialOverallBar");
const tutorialOverallText = document.getElementById("tutorialOverallText");

const tutorialBeginnerPercent = document.getElementById("tutorialBeginnerPercent");
const tutorialBeginnerBar = document.getElementById("tutorialBeginnerBar");
const tutorialBeginnerText = document.getElementById("tutorialBeginnerText");

const tutorialAdvancedPercent = document.getElementById("tutorialAdvancedPercent");
const tutorialAdvancedBar = document.getElementById("tutorialAdvancedBar");
const tutorialAdvancedText = document.getElementById("tutorialAdvancedText");

const tutorialProPercent = document.getElementById("tutorialProPercent");
const tutorialProBar = document.getElementById("tutorialProBar");
const tutorialProText = document.getElementById("tutorialProText");

let newsMode = "portfolio";
let tutorialLevel = 1;
let tutorialCache = [];
let tutorialProgressCache = null;

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
function fmtAnyDate(v){
  if(!v) return "—";
  try{
    const d = new Date(v);
    if (isNaN(d.getTime())) return String(v);
    return d.toLocaleString("hu-HU");
  }catch(e){
    return String(v);
  }
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
function previewText(s, len = 180){
  const text = String(s || "").replace(/\s+/g, " ").trim();
  if (text.length <= len) return text;
  return text.slice(0, len).trimEnd() + "…";
}
function progressWidth(v){
  const n = Number(v || 0);
  return Math.max(0, Math.min(100, n));
}
function difficultyLabel(level){
  return level === 1 ? "Kezdő" : level === 2 ? "Haladó" : level === 3 ? "Profi" : "Összes";
}
function statusLabel(status){
  if (status === "completed") return "Kész";
  if (status === "in_progress") return "Folyamatban";
  return "Nincs elkezdve";
}
function statusClass(status){
  if (status === "completed") return "badgeCompleted";
  if (status === "in_progress") return "badgeInProgress";
  return "badgeNotStarted";
}
function tagHtml(tags){
  const arr = Array.isArray(tags) ? tags : [];
  if (!arr.length) return `<span class="tagChip">nincs tag</span>`;
  return arr.map(tag => `<span class="tagChip">${esc(tag)}</span>`).join("");
}
function setButtonBusy(btn, busy, busyText){
  if (!btn) return;
  if (busy) {
    btn.dataset.prevText = btn.textContent;
    btn.textContent = busyText;
    btn.disabled = true;
    btn.style.opacity = "0.7";
    btn.style.cursor = "wait";
  } else {
    btn.textContent = btn.dataset.prevText || btn.textContent;
    btn.disabled = false;
    btn.style.opacity = "";
    btn.style.cursor = "";
  }
}
function goToTutorialPage(tutorialId){
  if (!tutorialId) return;
  window.location.href = `tutorials.php?id=${tutorialId}`;
}

async function fetchJson(url, options = {}){
  const r = await fetch(url, options);
  let data = null;
  try{
    data = await r.json();
  }catch(e){
    throw new Error("Érvénytelen JSON válasz.");
  }
  if (!r.ok) {
    throw new Error(data?.message || data?.error || "Szerverhiba.");
  }
  return data;
}

// ===== TUTORIAL API =====
async function loadTutorialProgress(){
  try{
    const data = await fetchJson(`/api/tutorials/progress?user_id=${USER_ID}`, { cache: "no-store" });
    if (!data.ok || !data.data) throw new Error(data.message || "Nem sikerült betölteni a progress adatokat.");

    tutorialProgressCache = data.data;

    const total = data.data.total || 0;
    const completed = data.data.completed || 0;
    const percent = progressWidth(data.data.percent || 0);

    tutorialOverallPercent.textContent = `${percent}%`;
    tutorialOverallBar.style.width = `${percent}%`;
    tutorialOverallText.textContent = `${completed} / ${total} kész`;

    const beginner = data.data.levels?.beginner || { completed:0, total:0, percent:0 };
    const advanced = data.data.levels?.advanced || { completed:0, total:0, percent:0 };
    const pro      = data.data.levels?.pro || { completed:0, total:0, percent:0 };

    tutorialBeginnerPercent.textContent = `${progressWidth(beginner.percent)}%`;
    tutorialBeginnerBar.style.width = `${progressWidth(beginner.percent)}%`;
    tutorialBeginnerText.textContent = `${beginner.completed} / ${beginner.total} kész`;

    tutorialAdvancedPercent.textContent = `${progressWidth(advanced.percent)}%`;
    tutorialAdvancedBar.style.width = `${progressWidth(advanced.percent)}%`;
    tutorialAdvancedText.textContent = `${advanced.completed} / ${advanced.total} kész`;

    tutorialProPercent.textContent = `${progressWidth(pro.percent)}%`;
    tutorialProBar.style.width = `${progressWidth(pro.percent)}%`;
    tutorialProText.textContent = `${pro.completed} / ${pro.total} kész`;

  }catch(e){
    tutorialOverallPercent.textContent = "—";
    tutorialOverallText.textContent = "Hiba a progress betöltésekor";
  }
}

function setTutorialTab(level){
  tutorialLevel = level;
  document.querySelectorAll(".tutorialTab").forEach(btn => {
    btn.classList.toggle("active", Number(btn.dataset.level) === level);
  });
  tutorialLevelInfo.textContent = `Aktív szint: ${difficultyLabel(level)}`;
}

async function loadTutorials(level = tutorialLevel){
  tutorialList.innerHTML = `<div class="tutorialEmpty">Betöltés…</div>`;
  setTutorialTab(level);

  try{
    const url = new URL("/api/tutorials", window.location.origin);
    url.searchParams.set("user_id", String(USER_ID));
    if (level > 0) url.searchParams.set("level", String(level));

    const data = await fetchJson(url.toString(), { cache: "no-store" });
    if (!data.ok || !Array.isArray(data.data)) {
      throw new Error(data.message || "Nem sikerült betölteni a tutorial listát.");
    }

    tutorialCache = data.data.slice();
    tutorialCountInfo.textContent = `${tutorialCache.length} lecke látható`;

    if (!tutorialCache.length) {
      tutorialList.innerHTML = `<div class="tutorialEmpty">Ehhez a szinthez jelenleg nincs lecke.</div>`;
      return;
    }

    tutorialList.innerHTML = tutorialCache.map(item => {
      const started = item.started_at ? fmtAnyDate(item.started_at) : "—";
      const completed = item.completed_at ? fmtAnyDate(item.completed_at) : "—";

      return `
        <div class="tutorialItem">
          <div class="tutorialItemTop">
            <div>
              <h3 class="tutorialItemTitle">${esc(item.title || "Névtelen tutorial")}</h3>
            </div>
            <span class="tutorialBadge ${statusClass(item.status)}">${esc(statusLabel(item.status))}</span>
          </div>

          <div class="tutorialTags">${tagHtml(item.tags)}</div>

          <div class="tutorialPreview">${esc(previewText(item.content, 210))}</div>

          <div class="tutorialMetaRow">
            <span>Szint: <strong>${esc(item.difficulty || difficultyLabel(item.difficulty_code || 0))}</strong></span>
            <span>•</span>
            <span>Indítva: <strong>${esc(started)}</strong></span>
            <span>•</span>
            <span>Kész: <strong>${esc(completed)}</strong></span>
          </div>

          <div class="tutorialActions">
            <button class="tutorialActionBtn start" onclick="goToTutorialPage(${Number(item.id)})">Elindítom</button>
            <button class="tutorialActionBtn complete" onclick="completeTutorial(${Number(item.id)})">Befejeztem</button>
            <button class="tutorialMiniBtn" onclick="goToTutorialPage(${Number(item.id)})">Interaktív megnyitás</button>
          </div>
        </div>
      `;
    }).join("");

  }catch(e){
    tutorialCountInfo.textContent = "Hiba";
    tutorialList.innerHTML = `<div class="tutorialEmpty">${esc(e.message || "Hiba a tutorialok betöltésekor.")}</div>`;
  }
}

async function tutorialAction(endpoint, tutorialId, triggerBtn = null){
  if (!tutorialId) return;

  try{
    setButtonBusy(triggerBtn, true, endpoint.includes("complete") ? "Mentés…" : "Indítás…");

    const data = await fetchJson(`/api/tutorials/${endpoint}`, {
      method: "POST",
      headers: {
        "Content-Type":"application/json",
        "Accept":"application/json"
      },
      body: JSON.stringify({
        user_id: USER_ID,
        tutorial_id: tutorialId
      })
    });

    if (!data.ok) throw new Error(data.message || "Sikertelen művelet.");

    await Promise.all([
      loadTutorialProgress(),
      loadTutorials(tutorialLevel)
    ]);
  }catch(e){
    alert(e.message || "Nem sikerült a tutorial állapotának mentése.");
  }finally{
    setButtonBusy(triggerBtn, false, "");
  }
}

window.completeTutorial = async function(tutorialId){
  await tutorialAction("complete", tutorialId);
};

document.querySelectorAll(".tutorialTab").forEach(btn => {
  btn.addEventListener("click", () => {
    const level = Number(btn.dataset.level || "1");
    loadTutorials(level);
  });
});

tutorialRefreshBtn.onclick = async () => {
  tutorialCountInfo.textContent = "Frissítés…";
  await Promise.all([
    loadTutorialProgress(),
    loadTutorials(tutorialLevel)
  ]);
};

tutorialOpenFirstBtn.onclick = async () => {
  if (!tutorialCache.length) {
    await loadTutorials(tutorialLevel);
  }
  if (tutorialCache.length) {
    goToTutorialPage(Number(tutorialCache[0].id));
  }
};

// ===== NEWS =====
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
    let data = null;
    try{
      data = await r.json();
    }catch(e){
      newsList.innerHTML = `<div class="cardMeta err">Érvénytelen válasz.</div>`;
      return;
    }

    if(!r.ok || !data.ok){
      newsList.innerHTML = `<div class="cardMeta err">${esc(data?.error || data?.message || "Hiba")}</div>`;
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

// ===== CALENDAR =====
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
  calPeriod.textContent = "—";

  try{
    const want = parseInt(calLimitSel.value || "8", 10);
    const url = new URL("/api/calendar", window.location.origin);
    url.searchParams.set("user_id", String(USER_ID));
    url.searchParams.set("limit", String(want));

    const r = await fetch(url.toString(), { cache:"no-store" });

    let data = null;
    try{
      data = await r.json();
    }catch(e){
      calList.innerHTML = `<div class="cardMeta err">Érvénytelen calendar válasz.</div>`;
      return;
    }

    if(!r.ok || !data.ok){
      calList.innerHTML = `<div class="cardMeta err">${esc(data?.error || data?.message || "Calendar hiba")}</div>`;
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

// ===== SAVE SETTINGS =====
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

    let data = null;
    try{
      data = await r.json();
    }catch(e){
      saveToast.textContent = "Érvénytelen mentési válasz.";
      return;
    }

    if(!r.ok || !data.ok){
      saveToast.textContent = "Hiba: " + (data?.error || data?.message || "nem sikerült");
      return;
    }

    saveToast.textContent = "Mentve!";
    setTimeout(() => saveToast.textContent = "", 2000);

  }catch(e){
    saveToast.textContent = "Hálózati hiba.";
  }
};

(async function boot(){
  await Promise.all([
    loadTutorialProgress(),
    loadTutorials(1),
    loadNews(),
    loadCalendar()
  ]);
})();
</script>
</body>
</html>