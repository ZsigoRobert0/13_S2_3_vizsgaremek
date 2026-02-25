(() => {
  const boot = window.__STOCKMASTER_BOOT__ || {};
  const API_BASE = boot.apiBase || "/api";

  // ====== CONFIG ======
  const SPREAD = 0.05;
  const HALF_SPREAD = SPREAD / 2;

  const STATE_POLL_MS = 2000;

  // ár frissítés: csak látható + selected + positions
  // FONTOS: Finnhub limit miatt ritkítunk + kevesebb symbolt kérünk egyszerre
  const VISIBLE_PRICE_POLL_MS = 4000;
  const SELECTED_INGEST_MS = 8000;
  const POSITIONS_INGEST_MS = 15000;

  // chart
  const REALTIME_MS = 4500;
  const REALTIME_LIMIT = 80;

  // assets cache
  const ASSETS_CACHE_KEY = "sm_assets_cache_v1";
  const ASSETS_CACHE_TTL_MS = 5 * 60 * 1000;

  // price flood védelem
  const PRICE_FETCH_TIMEOUT_MS = 1500;
  const PRICE_CONCURRENCY = 6;

  // ====== DOM ======
  const elUsername = document.getElementById("username");
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

  const chartEl = document.getElementById("chart");
  const chartOverlay = document.getElementById("chartOverlay");

  if (elUsername) elUsername.textContent = boot.username || "";

  // ====== STATE ======
  let assets = [];      // {symbol,name,price}
  const prices = {};    // symbol -> mid
  let selected = null;

  let positions = [];
  let balance = Number(boot.demoBalance || 0);

  // click védelem
  let tradeBusy = false;
  const closeBusy = new Set();

  // extra védelem: per-symbol action lock (dupla katt + dupla request ellen)
  const actionLock = new Map(); // symbol -> timestamp(until)
  function lockSymbol(symbol, ms = 900) {
    const s = String(symbol || "").toUpperCase();
    const now = Date.now();
    const until = actionLock.get(s) || 0;
    if (until > now) return false;
    actionLock.set(s, now + ms);
    return true;
  }

  // settings
  const SETTINGS = {
    loaded: false,
    userId: Number(boot.userId || 1),
    chart_interval: "1m",
    chart_limit_initial: 1500,
    chart_backfill_chunk: 1500,
    chart_theme: "dark",
  };

  // ====== HELPERS ======
  const sleep = (ms) => new Promise(r => setTimeout(r, ms));

  function getBidAsk(midPrice) {
    const mid = Number(midPrice || 0);
    return { bid: mid - HALF_SPREAD, ask: mid + HALF_SPREAD };
  }

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

  function setOverlay(text) {
    if (!chartOverlay) return;
    chartOverlay.textContent = text || "";
    chartOverlay.style.display = text ? "flex" : "none";
  }

  function setActiveTfButton(tf) {
    document.querySelectorAll(".btn[data-tf]").forEach(b => b.classList.toggle("active", b.dataset.tf === tf));
  }

  function applyThemeFromSettings() {
    const theme = String(SETTINGS.chart_theme || "dark").toLowerCase();
    document.body.setAttribute("data-theme", theme === "light" ? "light" : "dark");
  }

  function escapeHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
  function escapeAttr(s) { return escapeHtml(s); }

  function cssEscape(str) {
    // elég a symbolokra (AAPL, MSFT...), de hagyjuk meg
    return String(str).replaceAll('"', '\\"');
  }

  function setTradeButtonsDisabled(disabled) {
    if (buyBtn) buyBtn.disabled = !!disabled;
    if (sellBtn) sellBtn.disabled = !!disabled;
  }

  // ====== NEW HELPERS: smart close/open ======
  function getNetQtyAndAssetIdForSymbol(symbol) {
    const sym = String(symbol || "").toUpperCase();
    let net = 0;
    let assetId = null;

    for (const p of (positions || [])) {
      const ps = String(p.Symbol ?? "").toUpperCase();
      if (!ps || ps !== sym) continue;

      const q = parseFloat(p.Quantity ?? 0);
      if (Number.isFinite(q)) net += q;

      const aId = Number(p.AssetID);
      if (!assetId && Number.isFinite(aId) && aId > 0) assetId = aId;
    }

    return { netQty: net, assetId };
  }

  function getSelectedMid() {
    return Number(selected?.price || prices[selected?.symbol] || 0);
  }

  // ====== API: settings ======
  async function apiGetSettings(userId) {
    const res = await fetch(`${API_BASE}/settings?user_id=${encodeURIComponent(userId)}`, {
      headers: { "Accept": "application/json" },
      cache: "no-store",
    });
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json?.error || "settings_get_failed");
    return json.data;
  }

  async function apiUpdateSettings(payload) {
    const res = await fetch(`${API_BASE}/settings`, {
      method: "POST",
      headers: { "Accept": "application/json", "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json?.error || "settings_update_failed");
    return json.data;
  }

  async function loadSettingsOrFallback() {
    try {
      const s = await apiGetSettings(SETTINGS.userId);
      Object.assign(SETTINGS, s, { loaded: true });
    } catch (e) {
      console.warn("Settings load failed, using defaults:", e);
      SETTINGS.loaded = false;
    }
    applyThemeFromSettings();
  }

  // ====== API: assets (cache) ======
  function readAssetsCache() {
    try {
      const raw = localStorage.getItem(ASSETS_CACHE_KEY);
      if (!raw) return null;
      const parsed = JSON.parse(raw);
      if (!parsed?.ts || !Array.isArray(parsed?.data)) return null;
      if ((Date.now() - parsed.ts) > ASSETS_CACHE_TTL_MS) return null;
      return parsed.data;
    } catch {
      return null;
    }
  }

  function writeAssetsCache(list) {
    try {
      localStorage.setItem(ASSETS_CACHE_KEY, JSON.stringify({ ts: Date.now(), data: list }));
    } catch {}
  }

  function setAssets(list) {
    assets = (list || []).map(x => ({
      symbol: x.symbol,
      name: x.name,
      price: prices[x.symbol] ?? 0,
    }));
  }

  async function apiGetAssets(search = "", limit = 500, signal) {
    const params = new URLSearchParams();
    if (search && search.trim()) params.set("search", search.trim());
    params.set("limit", String(limit));
    params.set("tradable", "1");

    const res = await fetch(`${API_BASE}/assets?${params.toString()}`, {
      headers: { "Accept": "application/json" },
      cache: "no-store",
      signal,
    });
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json?.error || "assets_get_failed");
    return Array.isArray(json.data) ? json.data : [];
  }

  // ====== UI: instruments ======
  function renderInstruments(filterTerm = "") {
    const term = (filterTerm || "").trim().toLowerCase();
    instContainer.innerHTML = "";

    const list = assets.filter(a => {
      if (!term) return true;
      return a.symbol.toLowerCase().includes(term) || (a.name || "").toLowerCase().includes(term);
    });

    if (!list.length) {
      instContainer.innerHTML = `<div class="muted small pad">Nincs találat.</div>`;
      return;
    }

    for (const a of list) {
      const d = document.createElement("div");
      d.className = "instrument";
      d.innerHTML = `
        <div class="instrument-main">
          <div class="instrument-name">${escapeHtml(a.name)}</div>
          <div class="instrument-symbol">${escapeHtml(a.symbol)}</div>
        </div>
        <div class="price" data-symbol="${escapeAttr(a.symbol)}">${a.price ? a.price.toFixed(2) + " $" : "…"}</div>
      `;
      d.onclick = () => selectAsset(a.symbol);
      instContainer.appendChild(d);
    }
  }

  // ====== fetch helpers ======
  async function fetchJson(url, opts = {}) {
    const res = await fetch(url, opts);
    const json = await res.json().catch(() => ({}));
    return { res, json };
  }

  // =====================================================================
  // ====== PRICE + INGEST (Laravel /api/prices) — batch + flood védelem ===
  // A régi fetchPriceForSymbol API megmarad, csak a hívás get_price.php helyett:
  //   GET /api/prices?symbols=AAPL,MSFT&ingest=1
  //
  // FONTOS: a /api/prices a backendben SYMBOLONKÉNT hív Finnhubot, nem valódi Finnhub batch.
  // Ezért: kevesebb symbol / ritkább polling, különben 429 és csak 1-2 ár jön meg.
  // =====================================================================

  const _lastPriceAt = {};
  const _lastIngestAt = {};
  const _inFlight = new Set(); // p:SYM / i:SYM

  let _priceInFlight = 0;
  const _priceQueue = [];
  async function withPriceSlot(fn) {
    if (_priceInFlight >= PRICE_CONCURRENCY) {
      await new Promise(r => _priceQueue.push(r));
    }
    _priceInFlight++;
    try { return await fn(); }
    finally {
      _priceInFlight--;
      const r = _priceQueue.shift();
      if (r) r();
    }
  }

  const _pending = {
    p: new Map(), // symbol -> [{resolve,reject}]
    i: new Map(),
  };
  let _flushTimer = null;

  function _queue(map, symbol) {
    const sym = String(symbol || "").toUpperCase();
    let arr = map.get(sym);
    if (!arr) { arr = []; map.set(sym, arr); }
    return new Promise((resolve, reject) => arr.push({ resolve, reject }));
  }

  function _scheduleFlush() {
    if (_flushTimer) return;
    _flushTimer = setTimeout(_flushNow, 80);
  }

  async function _fetchPricesBatch(symbols, ingest) {
    const uniq = Array.from(new Set(symbols.map(s => String(s || "").toUpperCase()).filter(Boolean)));
    if (!uniq.length) return { data: {}, errors: {} };

    const params = new URLSearchParams();
    params.set("symbols", uniq.join(","));
    if (ingest) params.set("ingest", "1");

    // FIX: abszolút útvonal legacy alatt is
    const url = `/api/prices?${params.toString()}`;

    const ac = new AbortController();
    const t = setTimeout(() => ac.abort(), PRICE_FETCH_TIMEOUT_MS);

    try {
      const { res, json } = await fetchJson(url, {
        headers: { "Accept": "application/json" },
        cache: "no-store",
        signal: ac.signal,
      });

      if (!res.ok || !json || json.ok === false) {
        return { data: {}, errors: { _http: `HTTP ${res.status}` } };
      }

      const errs = (json.errors && typeof json.errors === "object") ? json.errors : {};
      return { data: json.data || {}, errors: errs };
    } catch (e) {
      return { data: {}, errors: { _net: e?.message || "network_error" } };
    } finally {
      clearTimeout(t);
    }
  }

  function _applyPrice(symbol, mid) {
    const sym = String(symbol || "").toUpperCase();
    const v = Number.parseFloat(mid);
    if (!Number.isFinite(v)) return;

    prices[sym] = v;

    const a = assets.find(x => x.symbol === sym);
    if (a) a.price = v;

    document.querySelectorAll(`.price[data-symbol="${cssEscape(sym)}"]`).forEach(el => {
      el.textContent = v.toFixed(2) + " $";
    });

    if (selected && selected.symbol === sym) {
      selected.price = v;
      assetPriceEl.textContent = v.toFixed(2) + " $";
      updateSpreadUI();
    }
  }

  async function _flushMap(map, ingest) {
    const symbols = Array.from(map.keys());
    if (!symbols.length) return;

    // chunk limit: 10 (Finnhub limit miatt!)
    const chunk = symbols.slice(0, 10);

    await withPriceSlot(async () => {
      const { data, errors } = await _fetchPricesBatch(chunk, ingest);

      for (const sym of chunk) {
        const payload = data[sym];
        if (payload && payload.price != null) {
          _applyPrice(sym, payload.price);
        }
      }

      for (const sym of chunk) {
        const waiters = map.get(sym) || [];
        map.delete(sym);

        _inFlight.delete((ingest ? "i:" : "p:") + sym);

        if (errors && errors[sym]) {
          waiters.forEach(w => w.reject(new Error(errors[sym])));
        } else {
          waiters.forEach(w => w.resolve(true));
        }
      }
    });
  }

  async function _flushNow() {
    _flushTimer = null;

    await _flushMap(_pending.i, true);
    await _flushMap(_pending.p, false);

    if (_pending.i.size || _pending.p.size) _scheduleFlush();
  }

  async function fetchPriceForSymbol(symbol, { ingest = false, priceEveryMs = 10000, ingestEveryMs = 12000 } = {}) {
    const sym = String(symbol || "").toUpperCase();
    if (!sym) return;

    const now = Date.now();

    if (!ingest) {
      if (_lastPriceAt[sym] && (now - _lastPriceAt[sym] < priceEveryMs)) return;
      _lastPriceAt[sym] = now;
    } else {
      if (_lastIngestAt[sym] && (now - _lastIngestAt[sym] < ingestEveryMs)) return;
      _lastIngestAt[sym] = now;
    }

    const key = (ingest ? "i:" : "p:") + sym;
    if (_inFlight.has(key)) return;
    _inFlight.add(key);

    const p = ingest ? _queue(_pending.i, sym) : _queue(_pending.p, sym);
    _scheduleFlush();
    return p;
  }

  // ====== STATE (positions / balance) ======
  function updateUI() {
    balanceEl.textContent = balance.toFixed(2) + " €";
    balanceMini.textContent = balanceEl.textContent;

    positionsEl.innerHTML = "";
    if (!positions || positions.length === 0) {
      positionsEl.innerHTML = "<div class='pos-item'>Nincs nyitott pozíciód.</div>";
      return;
    }

    positions.forEach(p => {
      const qtySigned = parseFloat(p.Quantity ?? 0);
      const qtyAbs = Math.abs(qtySigned);
      const isShort = qtySigned < 0;

      const entry = parseFloat(p.AvgEntryPrice ?? 0);
      const sym = String(p.Symbol ?? "");
      const name = String(p.Name ?? "");
      const current = (prices[sym] !== undefined) ? parseFloat(prices[sym]) : null;

      let pnlHtml = `<div class="pnl pnl-neutral">PnL: …</div>`;
      if (current !== null && entry > 0 && qtyAbs > 0) {
        const pnlVal = isShort ? (entry - current) * qtyAbs : (current - entry) * qtyAbs;
        const pnlPct = isShort ? ((entry - current) / entry) * 100 : ((current - entry) / entry) * 100;
        pnlHtml = `<div class="pnl ${pnlVal >= 0 ? "pnl-positive" : "pnl-negative"}">${pnlVal.toFixed(2)} € (${pnlPct.toFixed(2)}%)</div>`;
      }

      const item = document.createElement("div");
      item.className = "pos-item";
      item.innerHTML = `
        <div style="display:flex; justify-content:space-between; gap:10px; width:100%;">
          <div>
            <div style="font-weight:600;">${escapeHtml(sym)} ${isShort ? "<span style='color:var(--muted);font-size:11px'>(SHORT)</span>" : ""}</div>
            <div style="font-size:11px;color:var(--muted);">${escapeHtml(name)}</div>
            <button type="button"
              style="margin-top:6px;padding:6px 10px;border-radius:10px;border:0;cursor:pointer; position:relative; z-index:5;"
              data-assetid="${Number(p.AssetID)}"
              data-symbol="${escapeAttr(sym)}"
            >Zárás</button>
          </div>
          <div style="text-align:right;">
            <div>${qtyAbs.toFixed(2)} db</div>
            <div style="font-size:11px;color:var(--muted);">@ ${entry.toFixed(2)} €</div>
            ${pnlHtml}
          </div>
        </div>
      `;

      const btn = item.querySelector("button");
      btn?.addEventListener("click", () => closeByAsset(Number(p.AssetID), sym, btn));

      positionsEl.appendChild(item);
    });
  }

  async function refreshState() {
    try {
      const userId = Number(SETTINGS.userId || boot.userId || 0);
      const { res, json } = await fetchJson(`/api/state?user_id=${userId}`, { cache: "no-store" });
      if (!res.ok || !json?.ok) return;

      balance = parseFloat(json.balance ?? balance);
      positions = json.positions || [];

      for (const p of positions) {
        const sym = p.Symbol;
        if (sym) fetchPriceForSymbol(sym, { ingest: false, priceEveryMs: 10000 });
      }

      updateUI();
    } catch (e) {
      console.warn("State fetch failed:", e);
    }
  }

  // ====== TRADE (API) ======
  async function apiOpenPosition({ side, quantity, price }) {
    const userId = Number(SETTINGS.userId || boot.userId || 0);
    if (!userId) throw new Error("Hiányzó userId.");
    if (!selected?.symbol) throw new Error("Nincs kiválasztott instrument!");

    const { res, json } = await fetchJson(`${API_BASE}/positions/open`, {
      method: "POST",
      headers: { "Accept": "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({
        user_id: userId,
        symbol: selected.symbol,
        asset_name: selected.name,
        quantity: Number(quantity),
        price: Number(price),
        side: String(side),
      }),
    });

    if (!res.ok || !json?.ok) throw new Error(json?.error || "Nem sikerült pozíciót nyitni.");
    return json;
  }

  async function apiCloseByAsset({ assetId, midPrice }) {
    const userId = Number(SETTINGS.userId || boot.userId || 0);
    if (!userId) throw new Error("Hiányzó userId.");

    const { res, json } = await fetchJson(`${API_BASE}/positions/close-by-asset`, {
      method: "POST",
      headers: { "Accept": "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({
        user_id: userId,
        assetId: Number(assetId),
        midPrice: Number(midPrice),
      }),
    });

    if (!res.ok || !json?.ok) throw new Error(json?.error || "Nem sikerült zárni.");
    return json;
  }

  // ====== BUY/SELL buttons (SMART + LOCK) ======
  buyBtn?.addEventListener("click", async () => {
    if (tradeBusy) return;
    tradeBusy = true;
    setTradeButtonsDisabled(true);

    try {
      if (!lockSymbol(selected?.symbol, 900)) return;

      const q = parseInt(qtyInput.value, 10);
      if (!selected) return alert("Nincs kiválasztott instrument!");
      if (!Number.isFinite(q) || q <= 0) return alert("Adj meg pozitív mennyiséget!");

      const { netQty, assetId } = getNetQtyAndAssetIdForSymbol(selected.symbol);

      if (netQty < 0) {
        let mid = getSelectedMid();
        if (!mid || mid <= 0) {
          await fetchPriceForSymbol(selected.symbol, { ingest: false, priceEveryMs: 0 });
          mid = getSelectedMid();
        }
        if (!mid || mid <= 0) return alert("Nincs ár adat (mid) záráshoz.");
        if (!assetId) return alert("Nem találom az AssetID-t a nyitott pozícióból (state).");

        await apiCloseByAsset({ assetId: assetId, midPrice: mid });
        await refreshState();
        return;
      }

      const mid = getSelectedMid();
      const { ask } = getBidAsk(mid);
      if (!ask || ask <= 0) return alert("Nincs ár adat (ask).");

      await apiOpenPosition({ side: "buy", quantity: q, price: ask });
      await refreshState();
    } catch (e) {
      alert(e?.message || "Hiba vétel közben.");
    } finally {
      tradeBusy = false;
      setTradeButtonsDisabled(false);
    }
  });

  sellBtn?.addEventListener("click", async () => {
    if (tradeBusy) return;
    tradeBusy = true;
    setTradeButtonsDisabled(true);

    try {
      if (!lockSymbol(selected?.symbol, 900)) return;

      const q = parseInt(qtyInput.value, 10);
      if (!selected) return alert("Nincs kiválasztott instrument!");
      if (!Number.isFinite(q) || q <= 0) return alert("Adj meg pozitív mennyiséget!");

      const { netQty, assetId } = getNetQtyAndAssetIdForSymbol(selected.symbol);

      if (netQty > 0) {
        let mid = getSelectedMid();
        if (!mid || mid <= 0) {
          await fetchPriceForSymbol(selected.symbol, { ingest: false, priceEveryMs: 0 });
          mid = getSelectedMid();
        }
        if (!mid || mid <= 0) return alert("Nincs ár adat (mid) záráshoz.");
        if (!assetId) return alert("Nem találom az AssetID-t a nyitott pozícióból (state).");

        await apiCloseByAsset({ assetId: assetId, midPrice: mid });
        await refreshState();
        return;
      }

      const mid = getSelectedMid();
      const { bid } = getBidAsk(mid);
      if (!bid || bid <= 0) return alert("Nincs ár adat (bid).");

      await apiOpenPosition({ side: "sell", quantity: q, price: bid });
      await refreshState();
    } catch (e) {
      alert(e?.message || "Hiba eladás közben.");
    } finally {
      tradeBusy = false;
      setTradeButtonsDisabled(false);
    }
  });

  async function closeByAsset(assetId, symbol, btnEl) {
    const aId = Number(assetId);
    if (!aId || aId <= 0) return alert("Hibás AssetID.");
    if (closeBusy.has(aId)) return;

    // lock ugyanarra a symbolra is (dupla katt ellen)
    if (!lockSymbol(symbol, 900)) return;

    closeBusy.add(aId);
    if (btnEl) btnEl.disabled = true;

    try {
      let mid = Number(prices[symbol] || 0);
      if (!mid || mid <= 0) {
        await fetchPriceForSymbol(symbol, { ingest: false, priceEveryMs: 0 });
        mid = Number(prices[symbol] || 0);
      }
      if (!mid || mid <= 0) return alert("Nem sikerült mid árat lekérni záráshoz.");

      await apiCloseByAsset({ assetId: aId, midPrice: mid });
      await refreshState();
    } catch (e) {
      alert(e?.message || "Hiba zárás közben.");
    } finally {
      closeBusy.delete(aId);
      if (btnEl) btnEl.disabled = false;
    }
  }

  // ====== ASSET SELECT ======
  function selectAsset(symbol) {
    const a = assets.find(x => x.symbol === symbol);
    if (!a) return;

    selected = a;

    assetTitleEl.textContent = `${a.symbol} — ${a.name}`;
    assetPriceEl.textContent = (a.price ? a.price.toFixed(2) : "…") + " $";
    updateSpreadUI();

    // ár azonnal
    fetchPriceForSymbol(a.symbol, { ingest: false, priceEveryMs: 0 });

    // ingest: ne spam-eljük (Finnhub limit)
    fetchPriceForSymbol(a.symbol, { ingest: true, ingestEveryMs: 0 });
    setTimeout(() => fetchPriceForSymbol(a.symbol, { ingest: true, ingestEveryMs: 8000 }), 800);
    setTimeout(() => fetchPriceForSymbol(a.symbol, { ingest: true, ingestEveryMs: 8000 }), 1600);

    setTimeout(() => chartLoadInitial(true), 900);
  }

  // ====== SEARCH (debounce + abort) ======
  let searchTimer = null;
  let searchAbort = null;

  searchInput?.addEventListener("input", () => {
    const term = searchInput.value || "";
    clearTimeout(searchTimer);

    searchTimer = setTimeout(async () => {
      try {
        if (searchAbort) searchAbort.abort();
        searchAbort = new AbortController();

        const list = await apiGetAssets(term, 200, searchAbort.signal);
        setAssets(list);
        renderInstruments(term);

        if (selected && !assets.some(x => x.symbol === selected.symbol) && assets.length) {
          selectAsset(assets[0].symbol);
        }
      } catch (e) {
        if (String(e?.name) === "AbortError") return;
        console.warn("Asset search failed:", e);
      }
    }, 250);
  });

  // ====== PRICE LOOP: visible only ======
  function getVisibleSymbolsInSidebar(max = 8) {
    const els = Array.from(document.querySelectorAll('.price[data-symbol]'));
    const out = [];
    for (const el of els) {
      const sym = el.getAttribute("data-symbol");
      if (!sym) continue;
      out.push(sym);
      if (out.length >= max) break;
    }
    return out;
  }

  function startLoops() {
    refreshState();
    setInterval(refreshState, STATE_POLL_MS);

    setInterval(() => {
      const visible = getVisibleSymbolsInSidebar(8);
      for (const sym of visible) {
        fetchPriceForSymbol(sym, { ingest: false, priceEveryMs: 12000 });
      }
      if (selected?.symbol) fetchPriceForSymbol(selected.symbol, { ingest: false, priceEveryMs: 6000 });
    }, VISIBLE_PRICE_POLL_MS);

    setInterval(() => {
      if (selected?.symbol) fetchPriceForSymbol(selected.symbol, { ingest: true, ingestEveryMs: SELECTED_INGEST_MS });
    }, 2500);

    setInterval(() => {
      for (const p of positions || []) {
        const sym = p.Symbol;
        if (sym && sym !== selected?.symbol) {
          fetchPriceForSymbol(sym, { ingest: true, ingestEveryMs: POSITIONS_INGEST_MS });
        }
      }
    }, 4000);
  }

  // ====== CHART (a te stabil logikáddal) ======
  let chart = null;
  let candleSeries = null;
  let currentTf = "1m";

  let candleMap = new Map();
  let earliestTime = null;
  let latestTime = null;

  let isLoadingInitial = false;
  let isLoadingBackfill = false;
  let lastBackfillAt = 0;

  let chartPollTimer = null;

  function tfToSeconds(tf) {
    switch (tf) {
      case "1m": return 60;
      case "5m": return 300;
      case "15m": return 900;
      case "1h": return 3600;
      case "1d": return 86400;
      default: return 60;
    }
  }

  function normalizeCandle(raw) {
    if (!raw) return null;
    const t = Number(raw.time ?? raw.open_ts ?? raw.ts);
    const o = Number(raw.open ?? raw.o);
    const h = Number(raw.high ?? raw.h);
    const l = Number(raw.low ?? raw.l);
    const c = Number(raw.close ?? raw.c);
    if (![t, o, h, l, c].every(Number.isFinite)) return null;
    return { time: Math.floor(t), open: o, high: h, low: l, close: c };
  }

  function mergeCandles(list) {
    for (const x of list) {
      if (!x || x.time == null) continue;
      candleMap.set(x.time, x);
    }
    const times = Array.from(candleMap.keys()).sort((a, b) => a - b);
    if (!times.length) { earliestTime = null; latestTime = null; return []; }
    earliestTime = times[0];
    latestTime = times[times.length - 1];
    return times.map(t => candleMap.get(t));
  }

  async function fetchCandles(symbol, tf, opts = {}) {
    const limit = opts.limit ?? 500;
    const params = new URLSearchParams({ symbol, tf, limit: String(limit) });
    if (opts.from != null) params.set("from", String(opts.from));
    if (opts.to != null) params.set("to", String(opts.to));

    const res = await fetch(`${API_BASE}/candles?${params.toString()}`, { cache: "no-store" });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (!data || data.ok !== true) throw new Error(data?.error || "candle_api_error");

    const arr = Array.isArray(data.candles) ? data.candles : [];
    const out = [];
    for (const r of arr) {
      const c = normalizeCandle(r);
      if (c) out.push(c);
    }
    out.sort((a, b) => a.time - b.time);
    return out;
  }

  function initChart() {
    chart = LightweightCharts.createChart(chartEl, {
      layout: {
        background: { type: "solid", color: "transparent" },
        textColor: getComputedStyle(document.body).getPropertyValue("--text").trim() || "#e6eef8",
      },
      rightPriceScale: { borderVisible: false },
      timeScale: {
        borderVisible: false,
        timeVisible: true,
        secondsVisible: (currentTf === "1m"),
        rightOffset: 8,
        barSpacing: 6,
      },
      grid: { vertLines: { visible: false }, horzLines: { visible: false } },
      crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
      handleScroll: { mouseWheel: true, pressedMouseMove: true, horzTouchDrag: true, vertTouchDrag: false },
      handleScale: { axisPressedMouseMove: true, mouseWheel: true, pinch: true },
    });

    candleSeries = chart.addCandlestickSeries({
      upColor: "#16a34a",
      downColor: "#ef4444",
      wickUpColor: "#16a34a",
      wickDownColor: "#ef4444",
      borderVisible: false,
    });

    const ro = new ResizeObserver(() => {
      try { chart.applyOptions({ width: chartEl.clientWidth, height: chartEl.clientHeight }); } catch {}
    });
    ro.observe(chartEl);
    chart.applyOptions({ width: chartEl.clientWidth, height: chartEl.clientHeight });

    chart.timeScale().subscribeVisibleTimeRangeChange((range) => {
      if (!range || earliestTime == null) return;
      maybeBackfill(range);
    });
  }

  function resetChartData() {
    candleMap.clear();
    earliestTime = null;
    latestTime = null;
    candleSeries?.setData([]);
  }

  async function chartLoadInitial() {
    if (!selected) return;
    if (!chart) initChart();

    if (chartPollTimer) { clearInterval(chartPollTimer); chartPollTimer = null; }

    isLoadingInitial = true;
    resetChartData();
    setOverlay("Chart betöltés…");

    try {
      const initialLimit = Number(SETTINGS.chart_limit_initial || 1500);
      const candles = await fetchCandles(selected.symbol, currentTf, { limit: initialLimit });
      const merged = mergeCandles(candles);
      candleSeries.setData(merged);

      if (!merged.length) {
        setOverlay("Nincs candle adat (DB).");
      } else {
        setOverlay("");
        chart.timeScale().fitContent();
      }

      startRealtime();
    } catch (e) {
      console.warn(e);
      setOverlay("Chart hiba (Console).");
    } finally {
      isLoadingInitial = false;
    }
  }

  async function maybeBackfill(range) {
    if (isLoadingInitial) return;

    const now = Date.now();
    if (isLoadingBackfill) return;
    if (now - lastBackfillAt < 650) return;

    const leftVisible = Math.floor(range.from);
    const threshold = 5 * tfToSeconds(currentTf);
    if (leftVisible > (earliestTime + threshold)) return;

    isLoadingBackfill = true;
    lastBackfillAt = now;

    const ts = chart.timeScale();
    const before = ts.getVisibleRange();

    try {
      setOverlay("Történet betöltése…");

      const to = earliestTime - tfToSeconds(currentTf);
      const backfillLimit = Number(SETTINGS.chart_backfill_chunk || 1500);
      const older = await fetchCandles(selected.symbol, currentTf, { limit: backfillLimit, to });

      if (!older.length) {
        setOverlay("Nincs több történet.");
        setTimeout(() => setOverlay(""), 900);
        return;
      }

      const merged = mergeCandles(older);
      candleSeries.setData(merged);

      if (before) ts.setVisibleRange(before);
      setOverlay("");
    } catch (e) {
      console.warn("Backfill error:", e);
      setOverlay("Backfill hiba.");
      setTimeout(() => setOverlay(""), 900);
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

        const ts = chart.timeScale();
        const before = ts.getVisibleRange();
        const follow = (!before || before.to >= (latestTime - 2 * tfSec));

        const recent = await fetchCandles(selected.symbol, currentTf, { limit: REALTIME_LIMIT, from });
        if (!recent.length) return;

        const merged = mergeCandles(recent);
        candleSeries.setData(merged);

        if (follow) ts.scrollToRealTime();
        else if (before) ts.setVisibleRange(before);

        if (chartOverlay && chartOverlay.style.display !== "none") setOverlay("");
      } catch {
        // realtime: ne villogtassunk
      }
    }, REALTIME_MS);
  }

  async function onTfSelected(newTf) {
    currentTf = newTf;
    setActiveTfButton(currentTf);

    try {
      await apiUpdateSettings({ user_id: SETTINGS.userId, chart_interval: newTf });
      SETTINGS.chart_interval = newTf;
    } catch {}

    if (chart) {
      try { chart.applyOptions({ timeScale: { secondsVisible: (currentTf === "1m") } }); } catch {}
    }

    await chartLoadInitial();
  }

  document.querySelectorAll(".btn[data-tf]").forEach(btn => {
    btn.addEventListener("click", () => onTfSelected(btn.dataset.tf || "1m"));
  });

  // ====== BOOT FLOW ======
  (async () => {
    await loadSettingsOrFallback();

    currentTf = SETTINGS.chart_interval || "1m";
    setActiveTfButton(currentTf);

    const cached = readAssetsCache();
    if (cached && cached.length) {
      setAssets(cached);
      renderInstruments(searchInput?.value || "");
      selected = assets[0] || null;
      if (selected) selectAsset(selected.symbol);
    } else {
      instContainer.innerHTML = `<div class="muted small pad">Assets betöltés…</div>`;
    }

    try {
      const list = await apiGetAssets("", 500);
      writeAssetsCache(list);
      setAssets(list);
      renderInstruments(searchInput?.value || "");

      if (!selected && assets.length) selectAsset(assets[0].symbol);
      if (selected && !assets.some(a => a.symbol === selected.symbol) && assets.length) {
        selectAsset(assets[0].symbol);
      }
    } catch (e) {
      console.warn("Assets API failed:", e);
      if (!assets.length) instContainer.innerHTML = `<div class="muted small pad">Assets API hiba.</div>`;
    }

    updateUI();
    startLoops();

    if (selected) {
      await sleep(600);
      chartLoadInitial();
    } else {
      setOverlay("Nincs asset.");
    }
  })();
})();