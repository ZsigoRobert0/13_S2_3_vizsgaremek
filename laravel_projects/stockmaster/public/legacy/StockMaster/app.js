(() => {
  const boot = window.__STOCKMASTER_BOOT__ || { assets: [], demoBalance: 0 };

  // ====== CONFIG ======
  const API_BASE = 'http://127.0.0.1:8000/api';   // Laravel API
  const SPREAD = 0.05;
  const HALF_SPREAD = SPREAD / 2;

  const PRICE_POLL_MS = 2000;   // legacy state refresh
  const CHART_POLL_MS = 4000;   // candle refresh

  // History/backfill beállítások
  const INITIAL_CANDLE_LIMIT = 1500; // több adat -> kevesebb "üres mező"
  const HISTORY_PAGE_LIMIT = 1500;   // ennyit töltünk, amikor balra húzol
  const HISTORY_THRESHOLD_BARS = 60; // ha ennyire közel érsz a bal széléhez -> loadMore

  const TF_SEC = { '1m': 60, '5m': 300, '15m': 900, '1h': 3600, '1d': 86400 };

  // ====== STATE ======
  const assets = (boot.assets || []).map(a => ({ ...a, price: 0 }));
  const prices = {};     // symbol -> mid
  let selected = assets[0] || null;
  let positions = [];
  let balance = Number(boot.demoBalance || 0);
  let tf = '1m';

  // Chart cache (history + realtime merge)
  let candleCache = [];
  let earliestTime = null;
  let latestTime = null;
  let isLoadingHistory = false;

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

  function normalizeCandles(arr) {
    return (arr || []).map(c => ({
      time: Number(c.time),
      open: Number(c.open),
      high: Number(c.high),
      low: Number(c.low),
      close: Number(c.close),
    })).filter(c =>
      Number.isFinite(c.time) &&
      Number.isFinite(c.open) &&
      Number.isFinite(c.high) &&
      Number.isFinite(c.low) &&
      Number.isFinite(c.close)
    ).sort((a, b) => a.time - b.time);
  }

  function mergeUnique(oldArr, newArr) {
    const m = new Map();
    for (const c of oldArr) m.set(c.time, c);
    for (const c of newArr) m.set(c.time, c);
    return Array.from(m.values()).sort((a, b) => a.time - b.time);
  }

  // ====== CHART (Lightweight Charts) ======
  const proChartEl = document.getElementById('proChart');
  const chart = LightweightCharts.createChart(proChartEl, {
    layout: { background: { type: 'solid', color: 'transparent' }, textColor: '#cbd5e1' },
    grid: { vertLines: { color: 'rgba(148,163,184,0.08)' }, horzLines: { color: 'rgba(148,163,184,0.08)' } },
    timeScale: {
      timeVisible: true,
      secondsVisible: false,
      rightOffset: 6,     // “lélegzet” jobb oldalt
      barSpacing: 10,     // ne legyen üresnek érzés
    },
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

  // 🔥 Backfill trigger: ha balra húzol és közel vagy a legrégebbi candle-höz -> töltsünk még
  chart.timeScale().subscribeVisibleTimeRangeChange(async (range) => {
    if (!range || !selected || !earliestTime || isLoadingHistory) return;

    const threshold = (TF_SEC[tf] || 60) * HISTORY_THRESHOLD_BARS;
    if (range.from <= earliestTime + threshold) {
      await loadMoreHistory().catch(() => {});
    }
  });

  async function fetchCandles({ symbol, tf, limit, from = null, to = null }) {
    const qs = new URLSearchParams();
    qs.set('symbol', symbol);
    qs.set('tf', tf);
    qs.set('limit', String(limit));
    if (from !== null) qs.set('from', String(from));
    if (to !== null) qs.set('to', String(to));

    const url = `${API_BASE}/candles?${qs.toString()}`;

    const res = await fetch(url, { cache: 'no-store', mode: 'cors' });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Candles API error');
    return normalizeCandles(json.candles || []);
  }

  async function fetchCandlesFull() {
    if (!selected) return;

    try {
      const data = await fetchCandles({
        symbol: selected.symbol,
        tf,
        limit: INITIAL_CANDLE_LIMIT,
      });

      candleCache = data;
      earliestTime = data.length ? data[0].time : null;
      latestTime = data.length ? data[data.length - 1].time : null;

      series.setData(candleCache);
      chart.timeScale().fitContent();

      if (chartHint) chartHint.style.display = data.length ? 'none' : 'flex';
      if (chartHint && !data.length) chartHint.textContent = 'Nincs adat ehhez a symbol-hoz (ingest kell).';
    } catch (e) {
      console.warn('Candles fetch error:', e);
      if (chartHint) {
        chartHint.style.display = 'flex';
        chartHint.textContent = 'Chart hiba / API nem elérhető';
      }
    }
  }

  async function loadMoreHistory() {
    if (!selected || !earliestTime) return;
    if (isLoadingHistory) return;

    isLoadingHistory = true;
    try {
      // megőrizzük a jelenlegi nézetet, hogy ne “ugorjon” a chart
      const prevRange = chart.timeScale().getVisibleRange();

      const older = await fetchCandles({
        symbol: selected.symbol,
        tf,
        limit: HISTORY_PAGE_LIMIT,
        to: earliestTime - 1,
      });

      if (!older.length) return;

      candleCache = mergeUnique(candleCache, older);
      earliestTime = candleCache[0].time;
      latestTime = candleCache[candleCache.length - 1].time;

      series.setData(candleCache);

      // visszaállítjuk a nézetet (ne rántsa el a kamera a frissen betöltött adatok miatt)
      if (prevRange) chart.timeScale().setVisibleRange(prevRange);

      if (chartHint) chartHint.style.display = 'none';
    } catch (e) {
      console.warn('History load error:', e);
    } finally {
      isLoadingHistory = false;
    }
  }

  async function fetchCandlesLast() {
    if (!selected) return;
    try {
      // last pár bar: merge + setData (stabilabb, mint csak update)
      const last = await fetchCandles({
        symbol: selected.symbol,
        tf,
        limit: 15,
      });
      if (!last.length) return;

      candleCache = mergeUnique(candleCache, last);
      earliestTime = candleCache[0].time;
      latestTime = candleCache[candleCache.length - 1].time;

      series.setData(candleCache);
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

    // reset chart cache instrument váltáskor
    candleCache = [];
    earliestTime = null;
    latestTime = null;

    assetTitleEl.textContent = `${a.symbol} — ${a.name}`;
    assetPriceEl.textContent = (a.price ? a.price.toFixed(2) : "…") + " $";

    updateSpreadUI();
    fetchCandlesFull().then(() => startChartPolling());
  }

  // ====== PRICE (legacy get_price.php) ======
  async function fetchPriceForSymbol(symbol) {
    try {
      const res = await fetch(`get_price.php?symbol=${encodeURIComponent(symbol)}`, { cache: 'no-store' });
      const json = await res.json();
      if (!json || typeof json.price === 'undefined') return;

      const mid = Number(json.price);
      prices[symbol] = mid;

      const a = assets.find(x => x.symbol === symbol);
      if (a) a.price = mid;

      const priceEl = instContainer.querySelector(`.price[data-symbol="${symbol}"]`);
      if (priceEl) priceEl.textContent = mid.toFixed(2) + " $";

      if (selected && selected.symbol === symbol) {
        assetPriceEl.textContent = mid.toFixed(2) + " $";
        updateSpreadUI();
      }
    } catch {
      // ha Finnhub nincs, maradhat "—"
    }
  }

  // ====== STATE (positions / balance) ======
  async function refreshState() {
    try {
      const res = await fetch('get_state.php', { cache: 'no-store' });
      const json = await res.json();

      if (json && json.ok) {
        positions = json.positions || [];
        balance = Number(json.balance ?? balance);
      }

      updateUI();
    } catch (e) {
      console.warn('State fetch error:', e);
    }
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
            <div style="font-weight:600;">${qty.toFixed(2)} db</div>
            <div style="font-size:11px;color:var(--muted);">@ ${entryPrice.toFixed(2)} €</div>
            ${pnlHtml}
          </div>
        </div>
      `;
      positionsEl.appendChild(item);
    });
  }

  async function openPosition(side) {
    try {
      if (!selected) return;

      const qty = parseFloat(qtyInput.value || "0");
      if (!qty || qty <= 0) { alert("Adj meg mennyiséget."); return; }

      // bid/ask
      const mid = Number(prices[selected.symbol] || selected.price || 0);
      if (!mid || mid <= 0) { alert("Nincs ár adat (mid)."); return; }

      const { bid, ask } = getBidAsk(mid);
      const execPrice = (side === 'BUY') ? ask : bid;

      const res = await fetch('open_position.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          symbol: selected.symbol,
          quantity: qty,
          side,
          price: execPrice
        })
      });

      const data = await res.json();
      if (!data.ok) { alert(data.error || 'Nem sikerült nyitni.'); return; }

      refreshState();
    } catch (e) {
      alert(e.message || 'Hiba nyitás közben.');
    }
  }

  async function closeByAsset(assetId, symbol) {
    try {
      const aId = Number(assetId);
      if (!aId || aId <= 0) { alert("Hibás AssetID."); return; }

      let midPrice = Number(prices[symbol] || 0);
      if (!midPrice || midPrice <= 0) {
        await fetchPriceForSymbol(symbol);
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

      // TF váltás: reset cache + új full load
      candleCache = [];
      earliestTime = null;
      latestTime = null;

      fetchCandlesFull().then(() => startChartPolling());
    });
  });

  if (buyBtn) buyBtn.addEventListener('click', () => openPosition('BUY'));
  if (sellBtn) sellBtn.addEventListener('click', () => openPosition('SELL'));

  // ====== BOOT ======
  renderInstruments();
  if (assets.length) selectAsset(assets[0]);

  refreshState();
  setInterval(refreshState, PRICE_POLL_MS);
})();