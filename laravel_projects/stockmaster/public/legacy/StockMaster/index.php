<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
requireLogin();
require_once __DIR__ . '/user_service.php';

$userId = currentUserId();
$user   = getUser($userId);
$demoBalance = (float)($user["DemoBalance"] ?? 0);
$username = (string)($user["Username"] ?? "");
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>StockMaster — Főoldal</title>

  <!-- app css -->
  <link rel="stylesheet" href="app.css?v=1">

  <!-- Lightweight Charts -->
  <script src="https://unpkg.com/lightweight-charts@4.2.0/dist/lightweight-charts.standalone.production.js"></script>

  <!-- BOOT -->
  <script>
    window.__STOCKMASTER_BOOT__ = {
      userId: <?php echo (int)$userId; ?>,
      username: <?php echo json_encode($username, JSON_UNESCAPED_UNICODE); ?>,
      demoBalance: <?php echo json_encode($demoBalance, JSON_UNESCAPED_UNICODE); ?>,
      // Laravel API base: relatív, hogy ne legyen CORS gond
      apiBase: "/api",
      legacyBase: ".", // legacy php-k ugyanebben a mappában
    };
  </script>
</head>

<body data-theme="dark">
  <div class="app">
    <!-- BAL -->
    <aside class="sidebar">
      <div class="brand">
        <div class="logo"><img src="StockMaster.png" alt="logo"></div>
        <div>
          <h1>StockMaster</h1>
          <div class="sub">Üdv, <span id="username"></span>!</div>
        </div>
      </div>

      <div class="search">
        <input id="search" placeholder="Keresés (pl. AAPL)" autocomplete="off">
      </div>

      <div class="instruments" id="instruments">
        <div class="muted small pad">Assets betöltés…</div>
      </div>

      <div class="mini-balance">
        <div><strong>Záróegyenleg:</strong> <span id="balance-mini">—</span></div>
      </div>
    </aside>

    <!-- FŐ -->
    <main class="main">
      <div class="controls">
        <div class="left-controls">
          <div id="asset-title">—</div>
          <div id="asset-price" class="muted" style="margin-left:8px;">—</div>
        </div>

        <div class="top-right-controls">
          <a href="logout.php" class="toggle">Kijelentkezés</a>
          <a href="stats.php" class="toggle">Statisztikák</a>
          <a href="transactions.php" class="toggle">Tranzakció</a>
          <a href="settings.php" class="toggle">Beállítások</a>
        </div>
      </div>

      <div class="tf-row">
        <button class="btn" data-tf="1m">1m</button>
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

        <div class="spread-box" id="spreadBox">
          <div class="spread-title">Spread: $0.05</div>
          <div class="spread-sub">
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

    <!-- JOBB -->
    <aside class="right">
      <div class="card">
        <div class="sub">Egyenleg</div>
        <div class="balance" id="balance">—</div>
      </div>

      <div class="card">
        <div style="font-weight:700;margin-bottom:8px">Portfólió</div>
        <div id="positions" class="positions"></div>
      </div>
    </aside>
  </div>

  <!-- app js -->
  <script src="app.js?v=1"></script>
</body>
</html>