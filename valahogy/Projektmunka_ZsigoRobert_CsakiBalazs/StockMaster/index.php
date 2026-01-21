<?php

    require_once "../StockMaster/auth.php";
    requireLogin();
    require "db.php"; 

    $user = getUser($_SESSION["user_id"]);


    $assets = [];
        $res = $conn->query("
                SELECT Symbol, Name 
                FROM assets 
                WHERE IsTradable = 1 
                ORDER BY Symbol 
                LIMIT 500
            ");
while ($row = $res->fetch_assoc()) {
    $assets[] = [
        "symbol" => $row["Symbol"],
        "name"   => $row["Name"],
    ];
}

?>

<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>StockMaster — Főoldal</title>

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
.instrument{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:8px;
    border-radius:8px;
    cursor:pointer;
    gap:8px;
}

.instrument-main{
    display:flex;
    flex-direction:column;
}

.instrument-name{
    font-size:13px;
    font-weight:600;
}

.instrument-symbol{
    font-size:11px;
    color:var(--muted);
}
.ratio-wrap{
    display:flex;
    flex-direction:column;
    gap:6px;
    margin-top:12px;
}

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

/* WebKit (Chrome, Edge) */
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

/* Firefox */
.ratio-input::-moz-range-thumb{
    width:18px;
    height:18px;
    border-radius:50%;
    background:#fff;
    border:3px solid #0f172a;
    box-shadow:0 0 0 2px rgba(148,163,184,0.5);
}

.ratio-input::-moz-range-track{
    height:8px;
    border-radius:999px;
    background:linear-gradient(90deg, var(--green) 0%, var(--green) 50%, var(--red) 50%, var(--red) 100%);
}

.pnl{
    font-size:11px;
    margin-top:2px;
}

.pnl-positive{
    color:var(--green);
}

.pnl-negative{
    color:var(--red);
}

.pnl-neutral{
    color:var(--muted);
}

/* --- Custom scrollbar az oldalsó listákhoz --- */

.instruments,
.positions{
    scrollbar-width: thin; /* Firefox */
    scrollbar-color: rgba(148,163,184,0.7) transparent;
}

/* WebKit (Chrome, Edge, Opera) */
.instruments::-webkit-scrollbar,
.positions::-webkit-scrollbar{
    width:6px;
}

.instruments::-webkit-scrollbar-track,
.positions::-webkit-scrollbar-track{
    background:transparent;
}

.instruments::-webkit-scrollbar-thumb,
.positions::-webkit-scrollbar-thumb{
    background:rgba(148,163,184,0.5);
    border-radius:999px;
}

.instruments::-webkit-scrollbar-thumb:hover,
.positions::-webkit-scrollbar-thumb:hover{
    background:rgba(148,163,184,0.9);
}



/* header */
.brand{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.logo{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;}
h1{font-size:16px;margin:0}
.sub{font-size:12px;color:var(--muted)}

/* instruments */
.search{margin:10px 0;display:flex}
.search input{flex:1;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);background:var(--glass);color:var(--text)}
.instruments{margin-top:8px;display:flex;flex-direction:column;gap:6px;max-height:calc(100vh - 240px);overflow:auto}
.instrument{display:flex;justify-content:space-between;align-items:center;padding:8px;border-radius:8px;cursor:pointer}
.instrument:hover{background:var(--accent)}
.sym{font-weight:700}
.price{font-family:monospace}

/* chart area */
.controls{display:flex;justify-content:space-between;align-items:center}
.left-controls{display:flex;gap:8px;align-items:center}
.btn{padding:6px 10px;border-radius:8px;border:0;background:transparent;color:var(--muted);cursor:pointer}
.btn.active{background:linear-gradient(90deg,rgba(255,255,255,0.03),rgba(255,255,255,0.02));color:var(--text)}
.chart{height:420px;border-radius:10px;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.05));display:flex;align-items:center;justify-content:center;color:var(--muted);border:1px solid rgba(255,255,255,0.02)}

/* trade row */
.trade-row{display:flex;gap:8px;align-items:center;margin-top:10px}
.qty{width:120px;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);background:var(--glass);color:var(--text)}
.buy{background:linear-gradient(90deg,var(--green),#68d391);color:#04260b;border:0;padding:8px 14px;border-radius:10px;cursor:pointer}
.sell{background:linear-gradient(90deg,var(--red),#f87171);color:#fff;border:0;padding:8px 14px;border-radius:10px;cursor:pointer}

/* ratio slider */
.ratio-wrap{display:flex;flex-direction:column;gap:8px;margin-top:12px}
.ratio-bar{position:relative;height:18px;border-radius:18px;background:linear-gradient(90deg,var(--green) 0 60%, var(--red) 60%);overflow:hidden}
.ratio-thumb{position:absolute;top:-6px;left:60%;transform:translateX(-50%);width:24px;height:24px;border-radius:50%;background:#fff;border:3px solid rgba(0,0,0,0.12)}
.ratio-input{width:100%;}

/* right column */
.card{background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(0,0,0,0.03));padding:10px;border-radius:8px;margin-bottom:12px}
.balance{font-size:20px;font-weight:700}
.positions{max-height:220px;overflow:auto;margin-top:8px}
.pos-item{display:flex;justify-content:space-between;padding:8px;border-radius:6px}

/* misc */
.top-right-controls{display:flex;gap:8px;align-items:center}
.toggle{padding:6px 8px;border-radius:8px;cursor:pointer;background:var(--glass)}

@media (max-width:1100px){
    .app{grid-template-columns:1fr;grid-auto-rows:auto;height:auto;padding:12px}
    .sidebar,.right{order:2}
    .main{order:1}
}
</style>
</head>

<body data-theme="dark">

<div class="app" >

    <!-- BAL OLDALI MENÜ -->
    <aside class="sidebar">
        <div class="brand">
            <div class="logo"> <img src="StockMaster.png" alt="logo"></div>
            <div>
                <h1>StockMaster</h1>
                <div class="sub">Üdv, <?php echo htmlspecialchars($user["Username"]); ?>!</div>
            </div>
        </div>

        <div class="search"><input id="search" placeholder="Keresés (pl. AAPL)"></div>

        <div class="instruments" id="instruments"></div>

        <div style="margin-top:12px;font-size:12px;color:var(--muted)">
            <div><strong>Záróegyenleg:</strong> <span id="balance-mini"><?php echo $user["DemoBalance"]; ?> €</span></div>
        </div>
    </aside>

    <!-- FŐ TARTALOM -->
    <main class="main">
        
        <div class="controls">
            <div class="left-controls">
                <div id="asset-title">AAPL — Apple Inc.</div>
                <div id="asset-price" style="margin-left:8px;color:var(--muted)">170.12 €</div>
            </div>

            <div class="top-right-controls">
                <div class="toggle" id="themeToggle">Sötét/Világos</div>
                <a href="logout.php" class="toggle" style="text-decoration:none;color:var(--text)">Kijelentkezés</a>
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
            <!-- ide jöhet majd a canvas / grafikon -->
        </div>

        <div class="trade-row">
            <input class="qty" id="qty" value="1">
            <button class="buy" id="buyBtn">VÉTEL</button>
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
            <div class="balance" id="balance"><?php echo $user["DemoBalance"]; ?> €</div>
        </div>

        <div class="card">
            <div style="font-weight:700;margin-bottom:8px">Portfólió</div>
            <div id="positions" class="positions"></div>
        </div>
    </aside>
</div>

<script>


const assets = <?php echo json_encode($assets, JSON_UNESCAPED_UNICODE); ?>;
assets.forEach(a => { a.price = 0; });
const prices = {}; 


let selected  = assets[0];
let positions = [];
let balance   = <?php echo (float)$user["DemoBalance"]; ?>;

// --- DOM ELEMEK ---

const instContainer = document.getElementById("instruments");
const positionsEl   = document.getElementById("positions");
const balanceEl     = document.getElementById("balance");
const balanceMini   = document.getElementById("balance-mini");

const themeToggle   = document.getElementById("themeToggle");
const qtyInput      = document.getElementById("qty");
const buyBtn        = document.getElementById("buyBtn");
const sellBtn       = document.getElementById("sellBtn");

const assetTitleEl  = document.getElementById("asset-title");
const assetPriceEl  = document.getElementById("asset-price");

// --- INSTRUMENT LISTA KIRAKÁSA ---

const searchInput = document.getElementById("search");

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

// kereső esemény
if (searchInput) {
    searchInput.addEventListener("input", () => {
        renderInstruments(searchInput.value);
    });
}


function selectAsset(a) {
    selected = a;
    assetTitleEl.textContent = `${a.symbol} — ${a.name}`;
    assetPriceEl.textContent = (a.price ? a.price.toFixed(2) : "…") + " $";

    fetchPriceForSymbol(a.symbol);
}


function updateUI(){
    // Egyenleg
    balanceEl.textContent   = balance.toFixed(2) + " €";
    balanceMini.textContent = balanceEl.textContent;

    // Portfólió
    positionsEl.innerHTML = "";

    if (!positions || positions.length === 0) {
        positionsEl.innerHTML = "<div class='pos-item'>Nincs nyitott pozíciód.</div>";
        return;
    }

    positions.forEach(p => {
        const qty        = parseFloat(p.Quantity ?? 0);
        const entryPrice = parseFloat(p.AvgEntryPrice ?? 0);

        // aktuális ár, ha már megjött az API-tól
        const currentPrice = prices[p.Symbol] !== undefined
            ? parseFloat(prices[p.Symbol])
            : null;

        let pnlHtml = "";
        if (currentPrice !== null && entryPrice > 0 && qty > 0) {
            const pnlValue = (currentPrice - entryPrice) * qty;
            const pnlPct   = ((currentPrice - entryPrice) / entryPrice) * 100;

            const pnlClass = pnlValue >= 0 ? "pnl-positive" : "pnl-negative";

            pnlHtml = `
                <div class="pnl ${pnlClass}">
                    ${pnlValue.toFixed(2)} € (${pnlPct.toFixed(2)}%)
                </div>
            `;
        } else {
            pnlHtml = `<div class="pnl pnl-neutral">PnL: …</div>`;
        }

        const item = document.createElement("div");
        item.className = "pos-item";
        item.innerHTML = `
            <div>
                <div style="font-weight:600;">${p.Symbol}</div>
                <div style="font-size:11px;color:var(--muted);">${p.Name}</div>
            </div>
            <div style="text-align:right;">
                <div>${qty.toFixed(2)} db</div>
                <div style="font-size:11px;color:var(--muted);">@ ${entryPrice.toFixed(2)} €</div>
                ${pnlHtml}
            </div>
        `;
        positionsEl.appendChild(item);
    });
}




function refreshState() {
    fetch('get_state.php')
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            balance   = parseFloat(data.balance ?? 0);
            positions = data.positions || [];

            positions.forEach(p => {
                fetchPriceForSymbol(p.Symbol);
            });

            updateUI();
        })
        .catch(err => console.error(err));
}


buyBtn.onclick = () => {
    const q = parseInt(qtyInput.value);
    if (isNaN(q) || q <= 0) {
        alert("Adj meg egy pozitív mennyiséget!");
        return;
    }

    fetch('open_position.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            symbol:     selected.symbol,
            asset_name: selected.name,   // <<< EZ ÚJ
            quantity:   q,
            price:      selected.price,
            side:       'buy'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        refreshState();
    })
    .catch(console.error);
};

sellBtn.onclick = () => {
    const q = parseInt(qtyInput.value);
    if (isNaN(q) || q <= 0) {
        alert("Adj meg egy pozitív mennyiséget!");
        return;
    }

    fetch('open_position.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            symbol:     selected.symbol,
            asset_name: selected.name,   // <<< EZ ÚJ
            quantity:   q,
            price:      selected.price,
            side:       'sell'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        refreshState();
    })
    .catch(console.error);
};

function fetchPriceForSymbol(symbol) {
    fetch('get_price.php?symbol=' + encodeURIComponent(symbol))
        .then(r => r.json())
        .then(data => {
            if (!data || !data.price) return;

            const price = parseFloat(data.price);

            // mentjük az aktuális árat a mapbe
            prices[symbol] = price;

            // ha van assets tömbünk, oda is betoljuk
            const asset = assets.find(a => a.symbol === symbol);
            if (asset) {
                asset.price = price;
            }

            // bal oldali lista ára
            const els = document.querySelectorAll(`.price[data-symbol="${symbol}"]`);
            els.forEach(el => {
                el.textContent = price.toFixed(2) + " $";
            });

            // ha ez a kiválasztott eszköz, a fő árnál is frissítsünk
            if (selected && selected.symbol === symbol) {
                selected.price = price;
                assetPriceEl.textContent = price.toFixed(2) + " $";
            }

            // PnL-ek frissítése
            updateUI();
        })
        .catch(err => console.error(err));
}


// --- KEZDŐ FUTTATÁS ---

renderInstruments();
selectAsset(assets[0]);

// első betöltés
refreshState();

// pár másodpercenként frissítse automatikusan:
setInterval(refreshState, 2000);


// Improved theme toggle: use data-theme attribute and persist to localStorage
(function(){
    const btn = document.getElementById('themeToggle');

    function setTheme(theme) {
        document.body.setAttribute('data-theme', theme);
        try { localStorage.setItem('theme', theme); } catch (e) {}

        if (!btn) return;
        // update button label/icon
        if (theme === 'light') {
            btn.textContent = 'Világos';
            btn.setAttribute('aria-pressed', 'true');
        } else {
            btn.textContent = 'Sötét';
            btn.setAttribute('aria-pressed', 'false');
        }
    }

    // initialize from localStorage or existing data-theme attribute
    try {
        const stored = localStorage.getItem('theme');
        if (stored === 'light' || stored === 'dark') {
            setTheme(stored);
        } else {
            const initial = document.body.getAttribute('data-theme') || 'dark';
            setTheme(initial);
        }
    } catch (e) {
        const initial = document.body.getAttribute('data-theme') || 'dark';
        setTheme(initial);
    }

    if (btn) {
        btn.addEventListener('click', () => {
            const current = document.body.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            setTheme(current === 'light' ? 'dark' : 'light');
        });
    }
})();
</script>


</body>
</html>
