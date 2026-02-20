(() => {
  const boot = window.__STOCKMASTER_BOOT__ || { assets: [], demoBalance: 0 };

  // ====== CONFIG ======
  const API_BASE = 'http://127.0.0.1:8000/api';   // Laravel API
  const SPREAD = 0.05;
  const HALF_SPREAD = SPREAD / 2;
  const PRICE_POLL_MS = 2000;   // legacy state refresh
  const CHART_POLL_MS = 4000;   // candle refresh

  // ====== STATE ======
  const assets = (boot.assets || []).map(a => ({ ...a, price: 0 }));
  const prices = {};     // symbol -> mid
  let selected = assets[0] || null;
  let positions = [];
  let balance = Number(boot.demoBalance || 0);
  let tf = '1m';

  // ====== DOM ======
  const bidVal = document.getElementById("bidVal");
  const askVal = document.getElementById("askVal");
  const instContainer = document.getElementById("instruments");
  const positionsEl = document.getElementById("positions");
  const balanceEl = document.getElementById("balance");
  const balanceMini = document.getElementById("balance-mini");
  const qtyInput = document.getElementById("qty");
  const buyBtn = document.getElementById("buyBtn");
  const sellBtn = document.getElementById("sellBtn");
  const assetTitleEl = document.getElementById("asset-title");
  const assetPriceEl = document.getElementById("asset-price");
  const searchInput = document.getElementById("search");
  const chartHint = document.getElementById("chartHint");

  // ====== HELPERS ======
  function getBidAsk(midPrice) {
    const mid = Number(midPrice || 0);
    return { bid: mid - HALF_SPREAD, ask: mid + HALF_SPREAD };
  }

  function updateSpreadUI() {
    if (!selected || !selected.price) { bidVal.textContent = "—"; askVal.textContent = "—"; return; }
    const { bid, ask } = getBidAsk(selected.price);
    bidVal.textContent = bid.toFixed(2) + " $";
    askVal.textContent = ask.toFixed(2) + " $";
  }

  function setActiveTfButton(newTf) {
    document.querySelectorAll('.tf-row .btn').forEach(b => {
      b.classList.toggle('active', b.dataset.tf === newTf);
    });
  }

  // ====== CHART (Lightweight Charts) ======
  const proChartEl = document.getElementById('proChart');
  const chart = LightweightCharts.createChart(proChartEl, {
    layout: { background: { type: 'solid', color: 'transparent' }, textColor: '#cbd5e1' },
    grid: { vertLines: { color: 'rgba(148,163,184,0.08)' }, horzLines: { color: 'rgba(148,163,184,0.08)' } },
    timeScale: { timeVisible: true, secondsVisible: false },
    rightPriceScale: { borderColor: 'rgba(148,163,184,0.15)' },
    crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
  });

  const series = chart.addCandlestickSeries({
    upColor: '#22c55e',
    downColor: '#ef4444',
    borderUpColor: '#22c55e',
    borderDownColor: '#ef4444',
    wickUpColor: '#22c55e',
    wickDownColor: '#ef4444',
  });

  function resizeChart() {
    if (!proChartEl) return;
    chart.applyOptions({ width: proChartEl.clientWidth, height: proChartEl.clientHeight });
  }
  window.addEventListener('resize', resizeChart);
  resizeChart();

  async function fetchCandlesFull() {
    if (!selected) return;
    const url = `${API_BASE}/candles?symbol=${encodeURIComponent(selected.symbol)}&tf=${encodeURIComponent(tf)}&limit=500`;

    try {
      const res = await fetch(url, { cache: 'no-store', mode: 'cors' });
      const json = await res.json();
      if (!json.ok) throw new Error(json.error || 'Candles API error');

      const data = (json.candles || []).map(c => ({
        time: Number(c.time),
        open: Number(c.open),
        high: Number(c.high),
        low: Number(c.low),
        close: Number(c.close),
      }));

      series.setData(data);
      chart.timeScale().fitContent();
      if (chartHint) chartHint.style.display = data.length ? 'none' : 'flex';
    } catch (e) {
      console.warn('Candles fetch error:', e);
      if (chartHint) {
        chartHint.style.display = 'flex';
        chartHint.textContent = 'Chart hiba / API nem elérhető';
      }
    }
  }

  async function fetchCandlesLast() {
    if (!selected) return;
    const url = `${API_BASE}/candles?symbol=${encodeURIComponent(selected.symbol)}&tf=${encodeURIComponent(tf)}&limit=5`;

    try {
      const res = await fetch(url, { cache: 'no-store', mode: 'cors' });
      const json = await res.json();
      if (!json.ok) return;

      const arr = json.candles || [];
      if (!arr.length) return;

      const last = arr[arr.length - 1];
      series.update({
        time: Number(last.time),
        open: Number(last.open),
        high: Number(last.high),
        low: Number(last.low),
        close: Number(last.close),
      });

      if (chartHint) chartHint.style.display = 'none';
    } catch {
      // csendben
    }
  }

  let chartTimer = null;
  function startChartPolling() {
    if (chartTimer) clearInterval(chartTimer);
    chartTimer = setInterval(fetchCandlesLast, CHART_POLL_MS);
  }

  // ====== INSTRUMENT LIST ======
  function renderInstruments(filter = "") {
    instContainer.innerHTML = "";
    const term = filter.trim().toLowerCase();

    const list = assets.filter(a => {
      if (!term) return true;
      const sym = a.symbol.toLowerCase();
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

  // ====== SELECT ASSET ======
  function selectAsset(a) {
    selected = a;
    assetTitleEl.textContent = `${a.symbol} — ${a.name}`;
    assetPriceEl.textContent = (a.price ? a.price.toFixed(2) : "…") + " $";

    updateSpreadUI();
    fetchCandlesFull();       // ✅ chart reload symbol váltáskor
  }

  // ====== UI ======
  function updateUI() {
    balanceEl.textContent = balance.toFixed(2) + " €";
    balanceMini.textContent = balanceEl.textContent;

    positionsEl.innerHTML = "";
    if (!positions || positions.length === 0) {
      positionsEl.innerHTML = "<div class='pos-item'>Nincs nyitott pozíciód.</div>";
      return;
    }

    positions.forEach(p => {
      const qty = parseFloat(p.Quantity ?? 0);
      const entryPrice = parseFloat(p.AvgEntryPrice ?? 0);

      const currentPrice = prices[p.Symbol] !== undefined ? parseFloat(prices[p.Symbol]) : null;

      let pnlHtml = "";
      if (currentPrice !== null && entryPrice > 0 && qty > 0) {
        const pnlValue = (currentPrice - entryPrice) * qty;
        const pnlPct = ((currentPrice - entryPrice) / entryPrice) * 100;
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

  // ====== LEGACY STATE ======
  function refreshState() {
    fetch('get_state.php', { cache: "no-store" })
      .then(r => r.json())
      .then(data => {
        if (data.error) { console.error(data.error); return; }
        balance = parseFloat(data.balance ?? 0);
        positions = data.positions || [];

        positions.forEach(p => fetchPriceForSymbol(p.Symbol));
        updateUI();
      })
      .catch(console.error);
  }

  // ====== PRICE FETCH ======
  async function postTickToLaravel(symbol, price) {
    // ✅ ezzel építjük a DB-s candle-öket realtime
    const ts = Math.floor(Date.now() / 1000);
    try {
      await fetch(`${API_BASE}/tick/ingest`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify({ symbol, price, ts, source: 'legacy' }),
        mode: 'cors',
      });
    } catch {
      // ha laravel API épp nem elérhető, nem dőlünk el
    }
  }

  function fetchPriceForSymbol(symbol) {
    fetch('get_price.php?symbol=' + encodeURIComponent(symbol), { cache: "no-store" })
      .then(r => r.json())
      .then(async data => {
        if (!data) return;
        if (data.ok === false || data.error) return;
        if (data.price === undefined || data.price === null) return;

        const price = parseFloat(data.price);
        prices[symbol] = price;

        const asset = assets.find(a => a.symbol === symbol);
        if (asset) asset.price = price;

        // bal oldali lista ára
        document.querySelectorAll(`.price[data-symbol="${symbol}"]`).forEach(el => {
          el.textContent = price.toFixed(2) + " $";
        });

        // kiválasztott instrument ár frissítése
        if (selected && selected.symbol === symbol) {
          selected.price = price;
          assetPriceEl.textContent = price.toFixed(2) + " $";
          updateSpreadUI();

          // ✅ csak a selected szimbólumot toljuk tick-re (nem spammeljük az összeset)
          await postTickToLaravel(symbol, price);
        }

        updateUI();
      })
      .catch(console.error);
  }

  // ====== TRADING ======
  buyBtn.onclick = () => {
    const q = parseInt(qtyInput.value);
    if (isNaN(q) || q <= 0) { alert("Adj meg egy pozitív mennyiséget!"); return; }

    fetch('open_position.php', {
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
      if (data.error) { alert(data.error); return; }
      refreshState();
    })
    .catch(console.error);
  };

  sellBtn.onclick = () => {
    const q = parseInt(qtyInput.value);
    if (isNaN(q) || q <= 0) { alert("Adj meg egy pozitív mennyiséget!"); return; }

    fetch('open_position.php', {
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
      if (data.error) { alert(data.error); return; }
      refreshState();
    })
    .catch(console.error);
  };

  async function closeByAsset(assetId, symbol) {
    try {
      const aId = Number(assetId);
      if (!aId || aId <= 0) { alert("Hibás AssetID."); return; }

      let midPrice = Number(prices[symbol] || 0);
      if (!midPrice || midPrice <= 0) {
        fetchPriceForSymbol(symbol);
        midPrice = Number(prices[symbol] || 0);
      }
      if (!midPrice || midPrice <= 0) { alert("Nem sikerült mid árat lekérni záráshoz."); return; }

      const res = await fetch('close_position_by_asset.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ assetId: aId, midPrice })
      });

      const data = await res.json();
      if (!data.ok) { alert(data.error || 'Nem sikerült zárni.'); return; }

      refreshState();
    } catch (e) {
      alert(e.message || 'Hiba zárás közben.');
    }
  }
  window.closeByAsset = closeByAsset;

  // ====== EVENTS ======
  if (searchInput) searchInput.addEventListener("input", () => renderInstruments(searchInput.value));

  document.querySelectorAll('.tf-row .btn').forEach(btn => {
    btn.addEventListener('click', () => {
      tf = btn.dataset.tf;
      setActiveTfButton(tf);
      fetchCandlesFull();
    });
  });

  // ====== BOOT ======
  renderInstruments();
  if (assets.length) selectAsset(assets[0]);

  refreshState();
  setInterval(refreshState, PRICE_POLL_MS);

  fetchCandlesFull().then(() => startChartPolling());
})();