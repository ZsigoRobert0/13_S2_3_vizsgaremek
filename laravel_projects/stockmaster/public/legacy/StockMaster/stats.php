<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

requireLogin();

$userId = (int)($_SESSION["user_id"] ?? 0);

// 1) Összegző statok (csak zárt pozik)
$sqlSummary = "
  SELECT
    COUNT(*) AS closedTrades,
    COALESCE(SUM(ProfitLoss), 0) AS totalPnl,
    COALESCE(AVG(ProfitLoss), 0) AS avgPnl,
    COALESCE(SUM(CASE WHEN ProfitLoss > 0 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0), 0) AS winRate,
    COALESCE(AVG(TIMESTAMPDIFF(SECOND, OpenTime, CloseTime)), 0) AS avgHoldSec,
    COALESCE(MAX(ProfitLoss), 0) AS bestTrade,
    COALESCE(MIN(ProfitLoss), 0) AS worstTrade
  FROM positions
  WHERE UserID = ? AND IsOpen = 0
";
$stmt = $conn->prepare($sqlSummary);
$stmt->bind_param("i", $userId);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2) Top eszközök PnL alapján
$sqlTopAssets = "
  SELECT
    a.Symbol,
    a.Name,
    COUNT(*) AS trades,
    COALESCE(SUM(p.ProfitLoss), 0) AS totalPnl,
    COALESCE(AVG(p.ProfitLoss), 0) AS avgPnl,
    COALESCE(AVG(TIMESTAMPDIFF(SECOND, p.OpenTime, p.CloseTime)), 0) AS avgHoldSec
  FROM positions p
  JOIN assets a ON a.ID = p.AssetID
  WHERE p.UserID = ? AND p.IsOpen = 0
  GROUP BY a.ID, a.Symbol, a.Name
  ORDER BY totalPnl DESC
  LIMIT 10
";
$stmt = $conn->prepare($sqlTopAssets);
$stmt->bind_param("i", $userId);
$stmt->execute();
$topAssets = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $topAssets[] = $r;
$stmt->close();

// 3) Utolsó 50 zárt ügylet
$sqlLast = "
  SELECT
    p.ID,
    a.Symbol,
    a.Name,
    p.PositionType,
    p.Quantity,
    p.EntryPrice,
    p.ExitPrice,
    p.ProfitLoss,
    p.OpenTime,
    p.CloseTime,
    TIMESTAMPDIFF(SECOND, p.OpenTime, p.CloseTime) AS holdSec
  FROM positions p
  JOIN assets a ON a.ID = p.AssetID
  WHERE p.UserID = ? AND p.IsOpen = 0
  ORDER BY p.CloseTime DESC
  LIMIT 50
";
$stmt = $conn->prepare($sqlLast);
$stmt->bind_param("i", $userId);
$stmt->execute();
$lastTrades = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $lastTrades[] = $r;
$stmt->close();

function fmtTime($sec) {
  $sec = (int)$sec;
  if ($sec <= 0) return "—";
  $h = intdiv($sec, 3600);
  $m = intdiv($sec % 3600, 60);
  if ($h > 0) return $h . "h " . $m . "m";
  return $m . "m";
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>StockMaster — Statisztikák</title>

<link rel="stylesheet" href="style.css">

<style>
:root{
  --bg:#0f1724;
  --panel:#0b1220;
  --text:#e6eef8;
  --muted:#98a2b3;
  --green:#16a34a;
  --red:#ef4444;
  --glass: rgba(255,255,255,0.03);
  --accent:#334155;
}
html,body{height:100%;margin:0;font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;background:linear-gradient(180deg,var(--bg),#041025);color:var(--text);}
.wrap{max-width:1200px;margin:0 auto;padding:18px;box-sizing:border-box;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
.back{padding:8px 10px;border-radius:10px;background:var(--glass);text-decoration:none;color:var(--text);}
.grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;}
.card{background:var(--panel);border-radius:12px;padding:12px;box-shadow:0 6px 18px rgba(2,6,23,0.6);}
.kpi-title{font-size:12px;color:var(--muted);}
.kpi-val{font-size:20px;font-weight:800;margin-top:6px;}
.kpi-sub{font-size:12px;color:var(--muted);margin-top:6px;}
.good{color:var(--green);}
.bad{color:var(--red);}

.section{margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.table{width:100%;border-collapse:collapse;font-size:13px;}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:left;}
.table th{color:var(--muted);font-weight:700;font-size:12px;}
.tag{display:inline-block;padding:3px 8px;border-radius:999px;background:rgba(255,255,255,0.05);font-size:11px;color:var(--muted);}
@media (max-width:1100px){.grid{grid-template-columns:repeat(2,1fr);} .section{grid-template-columns:1fr;}}

*{
  scrollbar-width: thin; /* Firefox */
  scrollbar-color: rgba(148,163,184,0.6) transparent;
}

/* WebKit (Chrome, Edge) */
*::-webkit-scrollbar{
  width:8px;
  height:8px;
}
*::-webkit-scrollbar-track{
  background: transparent;
}
*::-webkit-scrollbar-thumb{
  background: rgba(148,163,184,0.35);
  border-radius: 999px;
}
*::-webkit-scrollbar-thumb:hover{
  background: rgba(148,163,184,0.75);
}

.smNotifWrap{ position:relative; display:inline-flex; }

.smNotifBtn{
  position:relative;
  height:44px;
  padding:10px 14px;
  border-radius:14px;
  background: rgba(255,255,255,0.06);
  border:1px solid rgba(255,255,255,0.08);
  color:#fff;
  cursor:pointer;
  font-weight:800;
  display:inline-flex;
  align-items:center;
  gap:10px;
}
.smNotifBtn:hover{
  background: rgba(255,255,255,0.10);
  border-color: rgba(255,255,255,0.14);
}

.smNotifBadge{
  min-width:18px;
  height:18px;
  padding:0 6px;
  border-radius:999px;
  background:#ff4d4d;
  color:#fff;
  font-size:12px;
  font-weight:900;
  line-height:18px;
  text-align:center;
}

.smNotifDropdown{
  position:absolute;
  right:0;
  top:52px;
  width:380px;
  max-height:420px;
  overflow:hidden;
  border-radius:18px;
  background: rgba(10,18,30,0.92);
  border:1px solid rgba(255,255,255,0.10);
  backdrop-filter: blur(16px);
  box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  z-index:9999;
}

.smNotifHeader{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:12px;
  border-bottom:1px solid rgba(255,255,255,0.08);
}
.smNotifTitle{ font-weight:900; }
.smNotifLink{
  background:transparent;
  border:0;
  color:rgba(255,255,255,0.75);
  cursor:pointer;
  font-weight:800;
  font-size: 12px;
  opacity: 0.7;
}
.smNotifLink:hover{ 
  color:#fff;
  opacity: 1;
}

.smNotifList{
  max-height:340px;
  overflow:auto;
  padding:6px;
}
.smNotifItem{
  padding:10px 10px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,0.06);
  background: rgba(255,255,255,0.04);
  margin:6px 0;
  cursor:pointer;
}
.smNotifItem.unread{
  border-color: rgba(45,211,111,0.22);
  background: rgba(45,211,111,0.06);
}
.smNotifItemTitle{ font-weight:900; font-size:14px; margin-bottom:4px; }
.smNotifItemMsg{ opacity:0.85; font-size:13px; }
.smNotifItemTime{ opacity:0.6; font-size:12px; margin-top:6px; }

.smNotifEmpty{ opacity:0.7; padding:14px; }


</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div>
      <div style="font-size:20px;font-weight:900;">Statisztikák</div>
      <div style="color:var(--muted);font-size:12px;">Zárt ügyletek alapján</div>
    </div>
      <div class="smNotifWrap">
        <button class="smNotifBtn" id="smNotifBtn" type="button">
          Értesítések
          <span class="smNotifBadge" id="smNotifBadge" style="display:none;">0</span>
        </button>

        <div class="smNotifDropdown" id="smNotifDropdown" style="display:none;">
          <div class="smNotifHeader">
            <div class="smNotifTitle">Értesítések</div>
            <button class="smNotifLink" id="smNotifMarkAll" type="button">Összes olvasott</button>
          </div>

          <div class="smNotifList" id="smNotifList">
            <div class="smNotifEmpty">Betöltés…</div>
          </div>
        </div>
      </div>

    <a class="back" href="index.php">← Vissza a főoldalra</a>
  </div>

  <!-- KPI GRID -->
  <div class="grid">
    <div class="card">
      <div class="kpi-title">Zárt ügyletek</div>
      <div class="kpi-val"><?= (int)$summary["closedTrades"] ?></div>
      <div class="kpi-sub">lezárt ügyletek száma</div>
    </div>

    <div class="card">
      <div class="kpi-title">Össz PnL</div>
      <div class="kpi-val <?= ((float)$summary["totalPnl"] >= 0 ? "good" : "bad") ?>">
        <?= number_format((float)$summary["totalPnl"], 2) ?> €
      </div>
      <div class="kpi-sub">ProfitLoss összeg</div>
    </div>

    <div class="card">
      <div class="kpi-title">Átlag PnL / trade</div>
      <div class="kpi-val"><?= number_format((float)$summary["avgPnl"], 2) ?> €</div>
      <div class="kpi-sub">ProfitLoss átlag</div>
    </div>

    <div class="card">
      <div class="kpi-title">Win rate</div>
      <div class="kpi-val"><?= number_format(((float)$summary["winRate"]) * 100, 2) ?>%</div>
      <div class="kpi-sub">nyerő / összes</div>
    </div>

    <div class="card">
      <div class="kpi-title">Átlag tartás</div>
      <div class="kpi-val"><?= htmlspecialchars(fmtTime((float)$summary["avgHoldSec"])) ?></div>
      <div class="kpi-sub">Open → Close</div>
    </div>

    <div class="card">
      <div class="kpi-title">Best / Worst</div>
      <div class="kpi-val">
        <span class="good"><?= number_format((float)$summary["bestTrade"], 2) ?>€</span>
        <span style="color:var(--muted);"> / </span>
        <span class="bad"><?= number_format((float)$summary["worstTrade"], 2) ?>€</span>
      </div>
      <div class="kpi-sub">legjobb/legrosszabb trade</div>
    </div>
  </div>

  <div class="section">
    <!-- Top assets -->
    <div class="card">
      <div style="font-weight:900;margin-bottom:8px;">Top részvények (PnL alapján)</div>
      <table class="table">
        <thead>
          <tr>
            <th>Symbol</th>
            <th>Trades</th>
            <th>Össz PnL</th>
            <th>Átlag PnL</th>
            <th>Átlag tartás</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($topAssets) === 0): ?>
            <tr><td colspan="5" style="color:var(--muted);">Még nincs zárt ügylet.</td></tr>
          <?php else: foreach ($topAssets as $r): ?>
            <tr>
              <td>
                <div style="font-weight:800;"><?= htmlspecialchars($r["Symbol"]) ?></div>
                <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($r["Name"]) ?></div>
              </td>
              <td><?= (int)$r["trades"] ?></td>
              <td class="<?= ((float)$r["totalPnl"] >= 0 ? "good" : "bad") ?>">
                <?= number_format((float)$r["totalPnl"], 2) ?> €
              </td>
              <td><?= number_format((float)$r["avgPnl"], 2) ?> €</td>
              <td><?= htmlspecialchars(fmtTime((float)$r["avgHoldSec"])) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Last trades -->
    <div class="card">
      <div style="font-weight:900;margin-bottom:8px;">Utolsó zárt ügyletek</div>
      <div style="color:var(--muted);font-size:12px;margin-bottom:8px;">Legutóbbi 50</div>
      <div style="max-height:520px;overflow:auto;">
        <table class="table">
          <thead>
            <tr>
              <th>Symbol</th>
              <th>Side</th>
              <th>Qty</th>
              <th>Entry</th>
              <th>Exit</th>
              <th>PnL</th>
              <th>Hold</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($lastTrades) === 0): ?>
              <tr><td colspan="7" style="color:var(--muted);">Még nincs zárt ügylet.</td></tr>
            <?php else: foreach ($lastTrades as $t): ?>
              <tr>
                <td>
                  <div style="font-weight:800;"><?= htmlspecialchars($t["Symbol"]) ?></div>
                  <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($t["Name"]) ?></div>
                </td>
                <td><span class="tag"><?= htmlspecialchars($t["PositionType"]) ?></span></td>
                <td><?= number_format((float)$t["Quantity"], 2) ?></td>
                <td><?= number_format((float)$t["EntryPrice"], 2) ?></td>
                <td><?= number_format((float)$t["ExitPrice"], 2) ?></td>
                <td class="<?= ((float)$t["ProfitLoss"] >= 0 ? "good" : "bad") ?>">
                  <?= number_format((float)$t["ProfitLoss"], 2) ?> €
                </td>
                <td><?= htmlspecialchars(fmtTime((float)$t["holdSec"])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
<script>
(() => {
  const btn = document.getElementById("smNotifBtn");
  const dd  = document.getElementById("smNotifDropdown");
  const badge = document.getElementById("smNotifBadge");
  const list = document.getElementById("smNotifList");
  const markAll = document.getElementById("smNotifMarkAll");

  if (!btn || !dd || !badge || !list || !markAll) return;

  let open = false;

  const esc = (s) => String(s||"")
    .replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;")
    .replaceAll('"',"&quot;").replaceAll("'","&#039;");

  const setBadge = (n) => {
    if (n > 0){ badge.style.display="inline-block"; badge.textContent=String(n); }
    else { badge.style.display="none"; }
  };

  const render = (data) => {
    if (!data || !data.ok){
      list.innerHTML = `<div class="smNotifEmpty">Hiba a betöltésnél.</div>`;
      setBadge(0);
      return;
    }
    setBadge(data.unread || 0);

    const items = data.items || [];
    if (items.length === 0){
      list.innerHTML = `<div class="smNotifEmpty">
          Jelenleg nincs új értesítés.<br>
          <span style="opacity:.6;font-size:12px;">(nagy ármozgás, figyelmeztetés)</span>
        </div>
        `;
      return;
    }

    list.innerHTML = items.map(n => `
      <div class="smNotifItem ${n.isRead === 0 ? "unread" : ""}" data-id="${n.id}">
        <div class="smNotifItemTitle">${esc(n.title)}</div>
        <div class="smNotifItemMsg">${esc(n.message)}</div>
        <div class="smNotifItemTime">${esc(n.createdAt || "")}</div>
      </div>
    `).join("");

    list.querySelectorAll(".smNotifItem").forEach(el => {
      el.addEventListener("click", () => markRead(el.dataset.id));
    });
  };

  const load = () => {
    fetch("get_notifications.php?limit=20", { cache:"no-store" })
      .then(r => r.json())
      .then(render)
      .catch(() => list.innerHTML = `<div class="smNotifEmpty">Hálózati hiba.</div>`);
  };

  const markRead = (id) => {
    const fd = new FormData();
    fd.append("id", id);
    fetch("mark_notification_read.php", { method:"POST", body:fd })
      .then(() => load());
  };

  markAll.addEventListener("click", (e) => {
    e.stopPropagation();
    fetch("mark_all_notifications_read.php", { method:"POST" })
      .then(() => load());
  });

  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    open = !open;
    dd.style.display = open ? "block" : "none";
    if (open) load();
  });

  document.addEventListener("click", () => {
    open = false;
    dd.style.display = "none";
  });

  // induláskor csak badge frissítés
  load();
})();
</script>


</body>
</html>
