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
?>

<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title>StockMaster — Transaction log</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <style>
    body{ background:#07111f; color:#fff; font-family:system-ui; margin:0; }
    .wrap{ max-width:1100px; margin:30px auto; padding:0 14px; }
    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
    }

    .card{ margin-top:18px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:16px; backdrop-filter: blur(14px); }
    .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
    .row{ display:flex; gap:10px; align-items:end; flex-wrap:wrap; }
    .field{ display:flex; flex-direction:column; gap:6px; position:relative; }

    .btn{
      background:rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.08);
      color:#fff;
      border-radius:12px;
      padding:10px 14px;
      cursor:pointer;
      font-weight:600;
    }
    .btn:hover{
      background:rgba(255,255,255,0.10);
      border-color:rgba(255,255,255,0.14);
    }

    input, select{
      -webkit-appearance:none; -moz-appearance:none; appearance:none;
      background: rgba(0,0,0,0.28);
      border: 1px solid rgba(255,255,255,0.10);
      color: #fff;
      padding: 10px 42px 10px 12px;
      border-radius: 12px;
      outline: none;
      min-width: 140px;
    }
    input:hover, select:hover{ border-color: rgba(255,255,255,0.16); }
    input:focus, select:focus{
      border-color: rgba(255,255,255,0.22);
      box-shadow: 0 0 0 3px rgba(255,255,255,0.06);
    }

    .selectField::after{
      content:"▾";
      position:absolute;
      right:14px;
      top:38px;
      color: rgba(255,255,255,0.65);
      pointer-events:none;
      font-size:14px;
    }

    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button{
      -webkit-appearance:none; margin:0;
    }
    input[type="number"]{ -moz-appearance:textfield; }

    select option{ background:#0b1422; color:#fff; }

    .ok{ color:#2dd36f; }
    .bad{ color:#ff4d4d; }
    .muted{ opacity:0.7; }

    table{ width:100%; border-collapse:collapse; }
    th, td{ padding:12px 10px; border-bottom:1px solid rgba(255,255,255,0.06); text-align:left; }
    th{ opacity:0.75; font-weight:700; }

    .tag{ display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; border:1px solid rgba(255,255,255,0.10); }
    .tag.deposit{ background:rgba(45,211,111,0.12); }
    .tag.withdrawal{ background:rgba(255,77,77,0.12); }
    .tag.trade{ background:rgba(64,155,255,0.12); }

    .backBtn{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 14px;
      border-radius:14px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.08);
      color:#fff;
      text-decoration:none;
      font-weight:600;
      transition:.15s ease;
      white-space:nowrap;
    }
    .backBtn:hover{
      background: rgba(255,255,255,0.10);
      border-color: rgba(255,255,255,0.14);
    }
    .backBtn::before{ content:"←"; opacity:.75; }

    @media (max-width: 850px){
      .grid{ grid-template-columns:1fr; }
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="topbar">
      <h2 style="margin:0;">Tranzakció</h2>
      <a href="index.php" class="backBtn">Vissza a főoldalra</a>
    </div>


    <div class="card">
      <div class="grid">
        <div>
          <h3 style="margin-top:0;">Befizetés / Kiutalás</h3>

          <div class="row">
            <div class="field selectField" style="flex:0.8;">
              <label>Típus</label>
              <select id="tType">
                <option value="deposit">Befizetés</option>
                <option value="withdrawal">Kiutalás</option>
              </select>
            </div>

            <div class="field" style="flex:1;">
              <label>Összeg (€)</label>
              <input id="tAmount" type="number" step="0.01" min="0" placeholder="pl. 1000">
            </div>

            <div class="field" style="flex:1.6;">
              <label>Leírás (opcionális)</label>
              <input id="tDesc" type="text" placeholder="pl. Demo feltöltés">
            </div>

            <button class="btn" id="tSave" type="button">Mentés</button>
          </div>

          <div id="tMsg" class="muted" style="margin-top:10px;"></div>
        </div>

        <div>
          <h3 style="margin-top:0;">Szűrés</h3>
          <div class="row">
            <div class="field selectField" style="flex:1;">
              <label>Limit</label>
              <select id="tLimit">
                <option value="50">50</option>
                <option value="100" selected>100</option>
                <option value="200">200</option>
                <option value="500">500</option>
              </select>
            </div>

            <div class="field" style="flex:1;">
              <label>&nbsp;</label>
              <button class="btn" id="tReload" type="button">Frissítés</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <h3 style="margin-top:0;">Tranzakciók</h3>
      <div style="overflow:auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Típus</th>
              <th>Összeg</th>
              <th>Idő</th>
              <th>Leírás</th>
            </tr>
          </thead>
          <tbody id="tBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    const tBody = document.getElementById("tBody");
    const tMsg  = document.getElementById("tMsg");

    document.getElementById("tReload").addEventListener("click", loadTx);
    document.getElementById("tSave").addEventListener("click", saveTx);

    loadTx();

    function loadTx(){
      const limit = document.getElementById("tLimit").value;

      fetch("get_transactionslog.php?limit=" + encodeURIComponent(limit), { cache: "no-store" })
        .then(r => r.json())
        .then(rows => {
          tBody.innerHTML = "";

          if (!Array.isArray(rows) || rows.length === 0){
            tBody.innerHTML = `<tr><td colspan="5" class="muted">Nincs tranzakció.</td></tr>`;
            return;
          }

          rows.forEach(r => {
            const tag = `<span class="tag ${r.type}">${labelType(r.type)}</span>`;
            const amt = formatAmt(r.amount, r.type);

            tBody.innerHTML += `
              <tr>
                <td class="muted">${r.id}</td>
                <td>${tag}</td>
                <td>${amt}</td>
                <td class="muted">${escapeHtml(r.time || "")}</td>
                <td>${escapeHtml(r.description || "")}</td>
              </tr>
            `;
          });
        })
        .catch(() => {
          tBody.innerHTML = `<tr><td colspan="5" class="muted">Hiba a betöltésnél.</td></tr>`;
        });
    }

    function saveTx(){
      const type   = document.getElementById("tType").value;
      const amount = document.getElementById("tAmount").value;
      const desc   = document.getElementById("tDesc").value;

      if (!amount || parseFloat(amount) <= 0){
        tMsg.className = "bad";
        tMsg.textContent = "Adj meg pozitív összeget.";
        return;
      }

      const data = new FormData();
      data.append("type", type);
      data.append("amount", amount);
      data.append("description", desc);

      fetch("create_transaction.php", { method:"POST", body:data })
        .then(r => r.json())
        .then(res => {
          if(res.ok){
            tMsg.className = "ok";
            tMsg.textContent = "Mentve. Új demo egyenleg: " + (res.newBalance ?? "?") + " €";
            document.getElementById("tAmount").value = "";
            document.getElementById("tDesc").value = "";
            loadTx();
          } else {
            tMsg.className = "bad";
            tMsg.textContent = "Hiba: " + (res.error || "ismeretlen");
          }
        })
        .catch(() => {
          tMsg.className = "bad";
          tMsg.textContent = "Szerver / hálózati hiba.";
        });
    }

    function labelType(t){
      if (t === "deposit") return "Befizetés";
      if (t === "withdrawal") return "Kiutalás";
      if (t === "trade") return "Trade";
      return t;
    }

    function formatAmt(a, t){
      const val = Number(a || 0);
      if (t === "withdrawal") return "-" + val.toFixed(2) + " €";
      if (t === "deposit") return "+" + val.toFixed(2) + " €";
      return (val >= 0 ? "+" : "") + val.toFixed(2) + " €";
    }

    function escapeHtml(str){
      return String(str)
        .replaceAll("&","&amp;")
        .replaceAll("<","&lt;")
        .replaceAll(">","&gt;")
        .replaceAll('"',"&quot;")
        .replaceAll("'","&#039;");
    }
  </script>
</body>
</html>
