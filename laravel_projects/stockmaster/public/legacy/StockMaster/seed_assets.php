<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

//csak bejelentkezve fusson
if (!isLoggedIn()) {
     exit('Unauthorized.');
}

set_time_limit(60);

$conn = legacy_db();

$assets = [
    // --- USA tech + mega caps ---
    ['AAPL',  'Apple Inc.'],
    ['MSFT',  'Microsoft Corporation'],
    ['GOOGL', 'Alphabet Inc. Class A'],
    ['GOOG',  'Alphabet Inc. Class C'],
    ['AMZN',  'Amazon.com Inc.'],
    ['META',  'Meta Platforms Inc.'],
    ['TSLA',  'Tesla Inc.'],
    ['NVDA',  'NVIDIA'],
    ['JPM',   'JPMorgan Chase & Co.'],
    ['JNJ',   'Johnson & Johnson'],
    ['V',     'Visa Inc.'],
    ['MA',    'Mastercard Incorporated'],
    ['HD',    'Home Depot Inc.'],
    ['DIS',   'Walt Disney Company'],
    ['NFLX',  'Netflix Inc.'],
    ['ADBE',  'Adobe Inc.'],
    ['CSCO',  'Cisco Systems Inc.'],
    ['INTC',  'Intel'],
    ['ORCL',  'Oracle'],
    ['PEP',   'PepsiCo Inc.'],
    ['KO',    'Coca-Cola Company'],
    ['PFE',   'Pfizer Inc.'],
    ['MRK',   'Merck & Co. Inc.'],
    ['BAC',   'Bank of America Corporation'],
    ['C',     'Citigroup Inc.'],
    ['XOM',   'Exxon Mobil Corporation'],
    ['CVX',   'Chevron Corporation'],
    ['WMT',   'Walmart Inc.'],
    ['NKE',   'Nike Inc.'],
    ['MCD',   'McDonald’s Corporation'],
    ['T',     'AT&T Inc.'],
    ['CRM',   'Salesforce Inc.'],
    ['AVGO',  'Broadcom Inc.'],
    ['TXN',   'Texas Instruments Incorporated'],
    ['AMD',   'Advanced Micro Devices Inc.'],
    ['QCOM',  'Qualcomm Incorporated'],
    ['PYPL',  'PayPal Holdings Inc.'],
    ['SHOP',  'Shopify Inc.'],
    ['UBER',  'Uber Technologies Inc.'],

    // --- USA további nagy nevek ---
    ['GS',    'Goldman Sachs Group Inc.'],
    ['MS',    'Morgan Stanley'],
    ['BLK',   'BlackRock Inc.'],
    ['UNH',   'UnitedHealth Group Incorporated'],
    ['UPS',   'United Parcel Service Inc.'],
    ['FDX',   'FedEx Corporation'],
    ['BA',    'Boeing Company'],
    ['CAT',   'Caterpillar Inc.'],
    ['GE',    'General Electric Company'],
    ['GM',    'General Motors Company'],
    ['F',     'Ford Motor Company'],
    ['WFC',   'Wells Fargo & Company'],
    ['COST',  'Costco Wholesale Corporation'],
    ['TMO',   'Thermo Fisher Scientific Inc.'],
    ['HON',   'Honeywell International Inc.'],
    ['IBM',   'International Business Machines Corporation'],
    ['SPY',   'SPDR S&P 500 ETF Trust'],
];

$stmt = $conn->prepare("
    INSERT INTO assets (Symbol, Name, IsTradable)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE
        Name = VALUES(Name)
");

if (!$stmt) {
    exit('Prepare hiba: ' . $conn->error);
}

$inserted = 0;

foreach ($assets as $a) {
    [$sym, $name] = $a;

    $sym  = strtoupper(trim($sym));
    $name = trim($name);

    if ($sym === '' || $name === '') {
        continue;
    }

    $stmt->bind_param('ss', $sym, $name);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $inserted++;
    }
}

$stmt->close();

echo "Sikeresen beszúrva / frissítve: {$inserted} részvény az assets táblába.";
