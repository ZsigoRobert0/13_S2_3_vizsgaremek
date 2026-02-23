<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
requireLogin();
require_once __DIR__ . '/user_service.php';

$conn   = legacy_db();
$userId = currentUserId();
$user   = getUser($userId);

// Assets lista (tradable)
$assets = [];
$res = $conn->query("
    SELECT Symbol, Name
    FROM assets
    WHERE IsTradable = 1
    ORDER BY Symbol
    LIMIT 500
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $assets[] = [
            'symbol' => $row['Symbol'],
            'name'   => $row['Name'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>StockMaster — Főoldal</title>

<link rel="stylesheet" href="style.css">

<!-- Lightweight Charts (candlestick) -->
<script src="https://unpkg.com/lightweight-charts@4.2.0/dist/lightweight-charts.standalone.production.js"></script>


<style>
:root{
    --bg:#0f1724;
    --panel:#0b1220;
    --text:#e6eef8;
    --muted:#98a2b3;
    --green:#16a34a;
    --red:#ef4444;
    --accent:#334155;
    --glass: rgba(255,255,255,0.03);
}
[data-theme="light"]{
    --bg:#f5f7fb;
    --panel:#ffffff;
    --text:#0b1220;
    --muted:#64748b;
    --accent:#e6eef8;
    --glass: rgba(0,0,0,0.03);
}
html,body{
    height:100%;
    margin:0;
    font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;
    background:linear-gradient(180deg,var(--bg),#041025);
    color:var(--text);
}
.app{
    display:grid;
    grid-template-columns:260px 1fr 340px;
    gap:18px;
    height:100vh;
    padding:18px;
    box-sizing:border-box;
}
.sidebar,.main,.right{
    background:var(--panel);
    border-radius:12px;
    padding:14px;
    box-shadow:0 6px 18px rgba(2,6,23,0.6);
}

/* header */
.brand{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.logo{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden}
.logo img{width:100%;height:100%;object-fit:cover}
h1{font-size:16px;margin:0}
.sub{font-size:12px;color:var(--muted)}

/* instruments */
.search{margin:10px 0;display:flex}
.search input{flex:1;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);background:var(--glass);color:var(--text)}
.instruments{margin-top:8px;display:flex;flex-direction:column;gap:6px;max-height:calc(100vh - 240px);overflow:auto}
.instrument{display:flex;justify-content:space-between;align-items:center;padding:8px;border-radius:8px;cursor:pointer;gap:8px;}
.instrument:hover{background:var(--accent)}
.instrument-main{display:flex;flex-direction:column}
.instrument-name{font-size:13px;font-weight:600}
.instrument-symbol{font-size:11px;color:var(--muted)}
.price{font-family:monospace}

/* chart area */
.controls{display:flex;justify-content:space-between;align-items:center}
.left-controls{display:flex;gap:8px;align-items:center}
.btn{padding:6px 10px;border-radius:8px;border:0;background:transparent;color:var(--muted);cursor:pointer}
.btn.active{background:linear-gradient(90deg,rgba(255,255,255,0.03),rgba(255,255,255,0.02));color:var(--text)}
.chart{
    height:420px;
    border-radius:10px;
    background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.05));
    border:1px solid rgba(255,255,255,0.02);
    position:relative;
    overflow:hidden;
}
.chart-overlay{
    position:absolute; inset:0;
    display:flex; align-items:center; justify-content:center;
    color:var(--muted);
    pointer-events:none;
    font-size:12px;
}

/* trade row */
.trade-row{display:flex;gap:8px;align-items:center;margin-top:10px}
.qty{width:120px;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);background:var(--glass);color:var(--text)}
.buy{background:linear-gradient(90deg,var(--green),#68d391);color:#04260b;border:0;padding:8px 14px;border-radius:10px;cursor:pointer}
.sell{background:linear-gradient(90deg,var(--red),#f87171);color:#fff;border:0;padding:8px 14px;border-radius:10px;cursor:pointer}

/* ratio slider */
.ratio-wrap{display:flex;flex-direction:column;gap:8px;margin-top:12px}
.ratio-input{
    -webkit-appearance:none;
    appearance:none;
    width:100%;
    height:8px;
    border-radius:999px;
    background:linear-gradient(90deg, var(--green) 0%, var(--green) 50%, var(--red) 50%, var(--red) 100%);
    outline:none;
    cursor:pointer;
    box-shadow:0 0 0 1px rgba(15,23,42,0.7), 0 4px 10px rgba(0,0,0,0.5);
}
.ratio-input::-webkit-slider-thumb{
    -webkit-appearance:none;
    width:18px;
    height:18px;
    border-radius:50%;
    background:#fff;
    border:3px solid #0f172a;
    box-shadow:0 0 0 2px rgba(148,163,184,0.5);
    margin-top:-5px;
}
.ratio-input::-moz-range-thumb{
    width:18px;height:18px;border-radius:50%;
    background:#fff;border:3px solid #0f172a;
    box-shadow:0 0 0 2px rgba(148,163,184,0.5);
}
.ratio-input::-moz-range-track{
    height:8px;border-radius:999px;
    background:linear-gradient(90deg, var(--green) 0%, var(--green) 50%, var(--red) 50%, var(--red) 100%);
}

/* right column */
.card{background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(0,0,0,0.03));padding:10px;border-radius:8px;margin-bottom:12px}
.balance{font-size:20px;font-weight:700}
.positions{max-height:220px;overflow:auto;margin-top:8px}
.pos-item{display:flex;justify-content:space-between;padding:8px;border-radius:6px}

/* pnl colors */
.pnl{font-size:11px;margin-top:2px;}
.pnl-positive{color:var(--green);}
.pnl-negative{color:var(--red);}
.pnl-neutral{color:var(--muted);}

/* scrollbar */
.instruments,.positions{
    scrollbar-width: thin;
    scrollbar-color: rgba(148,163,184,0.7) transparent;
}
.instruments::-webkit-scrollbar,.positions::-webkit-scrollbar{width:6px;}
.instruments::-webkit-scrollbar-track,.positions::-webkit-scrollbar-track{background:transparent;}
.instruments::-webkit-scrollbar-thumb,.positions::-webkit-scrollbar-thumb{background:rgba(148,163,184,0.5);border-radius:999px;}
.instruments::-webkit-scrollbar-thumb:hover,.positions::-webkit-scrollbar-thumb:hover{background:rgba(148,163,184,0.9);}

/* top controls */
.top-right-controls{display:flex;gap:8px;align-items:center}
.toggle{padding:6px 8px;border-radius:8px;cursor:pointer;background:var(--glass);text-decoration:none;color:var(--text);display:inline-block}

@media (max-width:1100px){
    .app{grid-template-columns:1fr;grid-auto-rows:auto;height:auto;padding:12px}
    .sidebar,.right{order:2}
    .main{order:1}
}
</style>
</head>

<body data-theme="dark">

<div class="app">

    <!-- BAL OLDALI MENÜ -->
    <aside class="sidebar">
        <div class="brand">
            <div class="logo"><img src="StockMaster.png" alt="logo"></div>
            <div>
                <h1>StockMaster</h1>
                <div class="sub">Üdv, <?php echo htmlspecialchars((string)($user["Username"] ?? ""), ENT_QUOTES, 'UTF-8'); ?>!</div>
            </div>
        </div>

        <div class="search"><input id="search" placeholder="Keresés (pl. AAPL)"></div>
        <div class="instruments" id="instruments"></div>

        <div style="margin-top:12px;font-size:12px;color:var(--muted)">
            <div><strong>Záróegyenleg:</strong> <span id="balance-mini"><?php echo htmlspecialchars((string)($user["DemoBalance"] ?? "0"), ENT_QUOTES, 'UTF-8'); ?> €</span></div>
        </div>
    </aside>

    <!-- FŐ TARTALOM -->
    <main class="main">
        <div class="controls">
            <div class="left-controls">
                <div id="asset-title">—</div>
                <div id="asset-price" style="margin-left:8px;color:var(--muted)">—</div>
            </div>

            <!-- FIX: ne legyen egymásba ágyazott <a> -->
            <div class="top-right-controls">
                <a href="logout.php" class="toggle">Kijelentkezés</a>
                <a href="stats.php" class="toggle">Statisztikák</a>
                <a href="transactions.php" class="toggle">Tranzakció</a>
                <a href="settings.php" class="toggle">Beállítások</a>
            </div>
        </div>

        <div style="margin-top:10px;">
            <button class="btn active" data-tf="1m">1m</button>
            <button class="btn" data-tf="5m">5m</button>
            <button class="btn" data-tf="15m">15m</button>
            <button class="btn" data-tf="1h">1h</button>
            <button class="btn" data-tf="1d">1d</button>
        </div>

        <div class="chart" id="chart">
            <div class="chart-overlay" id="chartOverlay">Chart betöltés…</div>
        </div>

        <div class="trade-row">
            <input class="qty" id="qty" value="1">

            <button class="buy" id="buyBtn">VÉTEL</button>

            <div id="spreadBox" style="
                min-width:140px;
                padding:8px 10px;
                border-radius:12px;
                background: rgba(255,255,255,0.05);
                border: 1px solid rgba(255,255,255,0.06);
                text-align:center;
                font-size:12px;
                line-height:1.2;
            ">
                <div style="font-weight:700;">Spread: $0.05</div>
                <div style="color:var(--muted);">
                    Vétel: <span id="bidVal">—</span> | Adás: <span id="askVal">—</span>
                </div>
            </div>

            <button class="sell" id="sellBtn">ELADÁS</button>
        </div>

        <div class="ratio-wrap">
            <div id="ratioLabel">Vevők: 50% • Eladók: 50%</div>
            <input type="range" id="ratioInput" min="0" max="100" value="50" class="ratio-input">
        </div>

    </main>

    <!-- JOBB OLDALI PANEL -->
    <aside class="right">
        <div class="card">
            <div class="sub">Egyenleg</div>
            <div class="balance" id="balance"><?php echo htmlspecialchars((string)($user["DemoBalance"] ?? "0"), ENT_QUOTES, 'UTF-8'); ?> €</div>
        </div>

        <div class="card">
            <div style="font-weight:700;margin-bottom:8px">Portfólió</div>
            <div id="positions" class="positions"></div>
        </div>
    </aside>
</div>

<script>
/** -----------------------------
 *  LEGACY STATE / PRICE LOGIC
 *  ---------------------------- */
const assets = <?php echo json_encode($assets, JSON_UNESCAPED_UNICODE); ?>;
assets.forEach(a => { a.price = 0; });
const prices = {};

const SPREAD = 0.05;
const HALF_SPREAD = SPREAD / 2;

function getBidAsk(midPrice) {
  const mid = Number(midPrice || 0);
  return { bid: mid - HALF_SPREAD, ask: mid + HALF_SPREAD };
}

let selected  = assets && assets.length ? assets[0] : null;
let positions = [];
let balance   = <?php echo (float)($user["DemoBalance"] ?? 0); ?>;

// DOM
const bidVal = document.getElementById("bidVal");
const askVal = document.getElementById("askVal");
const instContainer = document.getElementById("instruments");
const positionsEl   = document.getElementById("positions");
const balanceEl     = document.getElementById("balance");
const balanceMini   = document.getElementById("balance-mini");

const qtyInput      = document.getElementById("qty");
const buyBtn        = document.getElementById("buyBtn");
const sellBtn       = document.getElementById("sellBtn");

const assetTitleEl  = document.getElementById("asset-title");
const assetPriceEl  = document.getElementById("asset-price");
const searchInput   = document.getElementById("search");

function updateSpreadUI() {
  if (!selected || !selected.price) {
    bidVal.textContent = "—";
    askVal.textContent = "—";
    return;
  }
  const { bid, ask } = getBidAsk(selected.price);
  bidVal.textContent = bid.toFixed(2) + " $";
  askVal.textContent = ask.toFixed(2) + " $";
}

function renderInstruments(filter = "") {
  instContainer.innerHTML = "";
  const term = filter.trim().toLowerCase();

  const list = assets.filter(a => {
    if (!term) return true;
    const sym  = a.symbol.toLowerCase();
    const name = (a.name || "").toLowerCase();
    return sym.includes(term) || name.includes(term);
  });

  list.forEach(a => {
    const d = document.createElement("div");
    d.className = "instrument";
    d.innerHTML = `
      <div class="instrument-main">
        <div class="instrument-name">${a.name}</div>
        <div class="instrument-symbol">${a.symbol}</div>
      </div>
      <div class="price" data-symbol="${a.symbol}">…</div>
    `;
    d.onclick = () => selectAsset(a);
    instContainer.appendChild(d);
    fetchPriceForSymbol(a.symbol);
  });
}

if (searchInput) {
  searchInput.addEventListener("input", () => renderInstruments(searchInput.value));
}

function selectAsset(a) {
  selected = a;
  assetTitleEl.textContent = `${a.symbol} — ${a.name}`;
  assetPriceEl.textContent = (a.price ? a.price.toFixed(2) : "…") + " $";

  fetchPriceForSymbol(a.symbol);
  updateSpreadUI();

  // chart reload
  loadChartForSelected(true);
}

function updateUI(){
  balanceEl.textContent   = balance.toFixed(2) + " €";
  balanceMini.textContent = balanceEl.textContent;

  positionsEl.innerHTML = "";
  if (!positions || positions.length === 0) {
    positionsEl.innerHTML = "<div class='pos-item'>Nincs nyitott pozíciód.</div>";
    return;
  }

  positions.forEach(p => {
    const qty        = parseFloat(p.Quantity ?? 0);
    const entryPrice = parseFloat(p.AvgEntryPrice ?? 0);

    const currentPrice = prices[p.Symbol] !== undefined ? parseFloat(prices[p.Symbol]) : null;

    let pnlHtml = "";
    if (currentPrice !== null && entryPrice > 0 && qty > 0) {
      const pnlValue = (currentPrice - entryPrice) * qty;
      const pnlPct   = ((currentPrice - entryPrice) / entryPrice) * 100;
      const pnlClass = pnlValue >= 0 ? "pnl-positive" : "pnl-negative";
      pnlHtml = `<div class="pnl ${pnlClass}">${pnlValue.toFixed(2)} € (${pnlPct.toFixed(2)}%)</div>`;
    } else {
      pnlHtml = `<div class="pnl pnl-neutral">PnL: …</div>`;
    }

    const item = document.createElement("div");
    item.className = "pos-item";
    item.innerHTML = `
      <div style="display:flex; justify-content:space-between; gap:10px; width:100%;">
        <div>
          <div style="font-weight:600;">${p.Symbol}</div>
          <div style="font-size:11px;color:var(--muted);">${p.Name}</div>
          <button type="button"
                  style="margin-top:6px;padding:6px 10px;border-radius:10px;border:0;cursor:pointer; position:relative; z-index:5;"
                  onclick="closeByAsset(${Number(p.AssetID)}, '${String(p.Symbol).replace(/'/g, "\\'")}')">
              Zárás
          </button>
        </div>
        <div style="text-align:right;">
          <div>${qty.toFixed(2)} db</div>
          <div style="font-size:11px;color:var(--muted);">@ ${entryPrice.toFixed(2)} €</div>
          ${pnlHtml}
        </div>
      </div>
    `;
    positionsEl.appendChild(item);
  });
}

function refreshState() {
  fetch('./get_state.php', { cache: "no-store" })
    .then(r => r.json())
    .then(data => {
      if (data.error) return console.error(data.error);
      balance   = parseFloat(data.balance ?? 0);
      positions = data.positions || [];
      positions.forEach(p => fetchPriceForSymbol(p.Symbol));
      updateUI();
    })
    .catch(console.error);
}

// --- tick ingest throttle (NE terheljük szét) ---
const _lastIngestAt = {}; // symbol -> ms timestamp

function fetchPriceForSymbol(symbol) {
  const now = Date.now();

  // ingest csak a kiválasztott instrumentre
  const shouldIngest = (selected && selected.symbol === symbol);

  // max ~1 ingest / 5 sec / symbol (ne rate limiteljük a Laravel API-t)
  const canIngest = shouldIngest && (!(_lastIngestAt[symbol]) || (now - _lastIngestAt[symbol] >= 5000));

  const ingestParam = canIngest ? "&ingest=1" : "";

  fetch('./get_price.php?symbol=' + encodeURIComponent(symbol) + ingestParam, { cache: "no-store" })
    .then(r => r.json())
    .then(data => {
      if (!data) return;
      if (data.ok === false || data.error) return;

      if (data.price === undefined || data.price === null) return;
      const price = parseFloat(data.price);

      prices[symbol] = price;

      const asset = assets.find(a => a.symbol === symbol);
      if (asset) asset.price = price;

      document.querySelectorAll(`.price[data-symbol="${symbol}"]`).forEach(el => {
        el.textContent = price.toFixed(2) + " $";
      });

      if (selected && selected.symbol === symbol) {
        selected.price = price;
        assetPriceEl.textContent = price.toFixed(2) + " $";

        // ha most ingesteltünk, jegyezzük fel
        if (canIngest) _lastIngestAt[symbol] = now;
      }

      updateUI();
      updateSpreadUI();
    })
    .catch(console.error);
}

// trade buttons
buyBtn.onclick = () => {
  const q = parseInt(qtyInput.value);
  if (isNaN(q) || q <= 0) return alert("Adj meg egy pozitív mennyiséget!");

  fetch('./open_position.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      symbol: selected.symbol,
      asset_name: selected.name,
      quantity: q,
      price: getBidAsk(selected.price).ask,
      side: 'buy'
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.error) return alert(data.error);
    refreshState();
  })
  .catch(console.error);
};

sellBtn.onclick = () => {
  const q = parseInt(qtyInput.value);
  if (isNaN(q) || q <= 0) return alert("Adj meg egy pozitív mennyiséget!");

  fetch('./open_position.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      symbol: selected.symbol,
      asset_name: selected.name,
      quantity: q,
      price: getBidAsk(selected.price).bid,
      side: 'sell'
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.error) return alert(data.error);
    refreshState();
  })
  .catch(console.error);
};

async function closeByAsset(assetId, symbol) {
  try {
    const aId = Number(assetId);
    if (!aId || aId <= 0) return alert("Hibás AssetID (nincs benne a get_state válaszban).");

    let midPrice = Number(prices[symbol] || 0);
    if (!midPrice || midPrice <= 0) {
      await fetchPriceForSymbol(symbol);
      midPrice = Number(prices[symbol] || 0);
    }
    if (!midPrice || midPrice <= 0) return alert("Nem sikerült mid árat lekérni záráshoz.");

    const res = await fetch('./close_position_by_asset.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ assetId: aId, midPrice })
    });

    const data = await res.json();
    if (!data.ok) return alert(data.error || 'Nem sikerült zárni.');
    refreshState();
  } catch (e) {
    alert(e.message || 'Hiba zárás közben.');
  }
}
window.closeByAsset = closeByAsset;


/** -----------------------------
 *  CHART (Laravel /api/candles)  ✅ FIXED: initial + realtime + backfill + stable camera
 *  ---------------------------- */
const chartEl = document.getElementById('chart');
const chartOverlay = document.getElementById('chartOverlay');

let chart, candleSeries;
let currentTf = '1m';

let chartPollTimer = null;

// Candle cache (duplikáció nélkül)
let candleMap = new Map(); // time(sec) -> candle
let earliestTime = null;
let latestTime = null;

// Flags
let isLoadingInitial = false;
let isLoadingBackfill = false;
let lastBackfillAt = 0;

// config
const INITIAL_LIMIT  = 2000;  // 1500-3000
const BACKFILL_LIMIT = 1500;
const REALTIME_LIMIT = 80;    // legutolsó N
const REALTIME_MS    = 4500;
const DEV_MIN_WICK   = false; // ha lapos gyertyák zavarnak, tedd true-ra (csak vizuális)

function tfToSeconds(tf) {
  switch (tf) {
    case '1m': return 60;
    case '5m': return 300;
    case '15m': return 900;
    case '1h': return 3600;
    case '1d': return 86400;
    default: return 60;
  }
}

function setOverlay(text) {
  if (!chartOverlay) return;
  chartOverlay.textContent = text || '';
  chartOverlay.style.display = text ? 'flex' : 'none';
}

function applyDevMinWick(c) {
  if (!DEV_MIN_WICK) return c;
  if (c.open === c.high && c.open === c.low && c.open === c.close) {
    const eps = Math.max(0.0001, c.open * 0.00002);
    return { ...c, high: c.high + eps, low: c.low - eps };
  }
  return c;
}

function normalizeCandle(raw) {
  if (!raw) return null;
  const t = Number(raw.time ?? raw.open_ts ?? raw.ts);
  const o = Number(raw.open ?? raw.o);
  const h = Number(raw.high ?? raw.h);
  const l = Number(raw.low ?? raw.l);
  const c = Number(raw.close ?? raw.c);
  if (!Number.isFinite(t) || !Number.isFinite(o) || !Number.isFinite(h) || !Number.isFinite(l) || !Number.isFinite(c)) return null;
  return applyDevMinWick({ time: Math.floor(t), open: o, high: h, low: l, close: c });
}

function mergeCandles(list) {
  // list: [{time,open,high,low,close}, ...]
  for (const x of list) {
    if (!x || x.time == null) continue;
    candleMap.set(x.time, x);
  }

  const times = Array.from(candleMap.keys()).sort((a,b)=>a-b);
  if (times.length === 0) {
    earliestTime = null;
    latestTime = null;
    return [];
  }
  earliestTime = times[0];
  latestTime = times[times.length - 1];
  return times.map(t => candleMap.get(t));
}

async function fetchCandles(symbol, tf, opts = {}) {
  const limit = opts.limit ?? 500;

  const params = new URLSearchParams({
    symbol,
    tf,
    limit: String(limit),
  });

  if (opts.from != null) params.set('from', String(opts.from));
  if (opts.to != null)   params.set('to', String(opts.to));

  const url = `/api/candles?${params.toString()}`;
  const res = await fetch(url, { cache: 'no-store' });
  if (!res.ok) throw new Error(`HTTP ${res.status} candle hiba`);
  const data = await res.json();
  if (!data || data.ok !== true) throw new Error(data?.error || 'Candle API hiba');

  const arr = Array.isArray(data.candles) ? data.candles : [];
  const out = [];
  for (const r of arr) {
    const c = normalizeCandle(r);
    if (c) out.push(c);
  }
  out.sort((a,b)=>a.time-b.time);
  return out;
}

function initChart() {
  chart = LightweightCharts.createChart(chartEl, {
    layout: {
      background: { type: 'solid', color: 'transparent' },
      textColor: getComputedStyle(document.body).getPropertyValue('--text').trim() || '#e6eef8',
    },
    rightPriceScale: { borderVisible: false },
    timeScale: {
      borderVisible: false,
      timeVisible: true,
      secondsVisible: (currentTf === '1m'),
      rightOffset: 8,
      barSpacing: 6,
    },
    grid: {
      vertLines: { visible: false },
      horzLines: { visible: false },
    },
    crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
    handleScroll: { mouseWheel: true, pressedMouseMove: true, horzTouchDrag: true, vertTouchDrag: false },
    handleScale: { axisPressedMouseMove: true, mouseWheel: true, pinch: true },
  });

  candleSeries = chart.addCandlestickSeries({
    upColor: '#16a34a',
    downColor: '#ef4444',
    wickUpColor: '#16a34a',
    wickDownColor: '#ef4444',
    borderVisible: false,
  });

  // resize
  const ro = new ResizeObserver(() => {
    try {
      chart.applyOptions({ width: chartEl.clientWidth, height: chartEl.clientHeight });
    } catch(e){}
  });
  ro.observe(chartEl);
  chart.applyOptions({ width: chartEl.clientWidth, height: chartEl.clientHeight });

  // backfill trigger
  chart.timeScale().subscribeVisibleTimeRangeChange((range) => {
    if (!range || earliestTime == null) return;
    maybeBackfill(range);
  });
}

function resetChartData() {
  candleMap.clear();
  earliestTime = null;
  latestTime = null;
  if (candleSeries) candleSeries.setData([]);
}

async function loadInitialCandles() {
  if (!selected) return;
  if (!chart) initChart();

  // stop old realtime
  if (chartPollTimer) { clearInterval(chartPollTimer); chartPollTimer = null; }

  isLoadingInitial = true;
  resetChartData();
  setOverlay('Chart betöltés…');

  try {
    const candles = await fetchCandles(selected.symbol, currentTf, { limit: INITIAL_LIMIT });
    const merged = mergeCandles(candles);
    candleSeries.setData(merged);

    if (!merged.length) {
      setOverlay('Nincs candle adat (DB).');
    } else {
      setOverlay('');
      chart.timeScale().fitContent();
    }

    startRealtime();
  } catch (e) {
    console.error(e);
    setOverlay('Chart hiba (nézd meg Console-t).');
  } finally {
    isLoadingInitial = false;
  }
}

async function maybeBackfill(range) {
  if (isLoadingInitial) return;

  const now = Date.now();
  if (isLoadingBackfill) return;
  if (now - lastBackfillAt < 650) return; // throttle

  const leftVisible = Math.floor(range.from);
  const threshold = 5 * tfToSeconds(currentTf);
  if (leftVisible > (earliestTime + threshold)) return;

  isLoadingBackfill = true;
  lastBackfillAt = now;

  // camera save
  const ts = chart.timeScale();
  const before = ts.getVisibleRange();

  try {
    setOverlay('Történet betöltése…');

    const to = earliestTime - tfToSeconds(currentTf); // earliest-1 candle
    const older = await fetchCandles(selected.symbol, currentTf, { limit: BACKFILL_LIMIT, to });

    if (!older.length) {
      setOverlay('Nincs több történet (DB-ben).');
      setTimeout(() => setOverlay(''), 1200);
      return;
    }

    const merged = mergeCandles(older);
    candleSeries.setData(merged);

    // restore camera (no jump)
    if (before) ts.setVisibleRange(before);

    setOverlay('');
  } catch (e) {
    console.warn('Backfill hiba:', e);
    setOverlay('Backfill hiba (Console).');
    setTimeout(() => setOverlay(''), 1200);
  } finally {
    isLoadingBackfill = false;
  }
}

function startRealtime() {
  if (chartPollTimer) { clearInterval(chartPollTimer); chartPollTimer = null; }

  chartPollTimer = setInterval(async () => {
    try {
      if (!selected || latestTime == null) return;

      const tfSec = tfToSeconds(currentTf);
      const from = Math.max(0, latestTime - (REALTIME_LIMIT * tfSec));

      // kamera mentés + "right edge" detekt
      const ts = chart.timeScale();
      const before = ts.getVisibleRange();
      const follow = (() => {
        if (!before || latestTime == null) return true;
        return before.to >= (latestTime - 2 * tfSec);
      })();

      const recent = await fetchCandles(selected.symbol, currentTf, { limit: REALTIME_LIMIT, from });
      if (!recent.length) return;

      const merged = mergeCandles(recent);
      candleSeries.setData(merged);

      if (follow) {
        ts.scrollToRealTime();
      } else if (before) {
        ts.setVisibleRange(before);
      }

      if (chartOverlay && chartOverlay.style.display !== 'none') setOverlay('');
    } catch (e) {
      // realtime: ne rángassuk az overlayt, csak console
      console.warn('Realtime poll hiba:', e);
    }
  }, REALTIME_MS);
}

async function loadChartForSelected(fullReload = false) {
  // kompatibilitás miatt meghagyjuk a hívást: selectAsset -> loadChartForSelected(true)
  await loadInitialCandles();
}

// timeframe buttons
document.querySelectorAll('.btn[data-tf]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.btn[data-tf]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentTf = btn.getAttribute('data-tf') || '1m';

    // secondsVisible update
    if (chart) {
      try {
        chart.applyOptions({
          timeScale: { secondsVisible: (currentTf === '1m') }
        });
      } catch(e){}
    }

    loadChartForSelected(true);
  });
});


// INIT
renderInstruments();

if (selected) {
  selectAsset(selected);
} else {
  assetTitleEl.textContent = 'Nincs tradable asset az assets táblában.';
  setOverlay('Nincs asset.');
}

refreshState();
setInterval(refreshState, 2000);

</script>

</body>
</html>