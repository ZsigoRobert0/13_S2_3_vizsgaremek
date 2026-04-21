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
    ['AAPL',  'Apple Inc.'],
    ['MSFT',  'Microsoft Corporation'],
    ['NVDA',  'NVIDIA Corporation'],
    ['AMZN',  'Amazon.com, Inc.'],
    ['GOOGL',  'Alphabet Inc. Class A'],
    ['GOOG',  'Alphabet Inc. Class C'],
    ['META',  'Meta Platforms, Inc.'],
    ['TSLA',  'Tesla, Inc.'],
    ['BRK.B',  'Berkshire Hathaway Inc. Class B'],
    ['JPM',  'JPMorgan Chase & Co.'],
    ['V',  'Visa Inc.'],
    ['MA',  'Mastercard Incorporated'],
    ['UNH',  'UnitedHealth Group Incorporated'],
    ['XOM',  'Exxon Mobil Corporation'],
    ['LLY',  'Eli Lilly and Company'],
    ['WMT',  'Walmart Inc.'],
    ['JNJ',  'Johnson & Johnson'],
    ['PG',  'Procter & Gamble Company'],
    ['COST',  'Costco Wholesale Corporation'],
    ['AVGO',  'Broadcom Inc.'],
    ['HD',  'The Home Depot, Inc.'],
    ['ORCL',  'Oracle Corporation'],
    ['BAC',  'Bank of America Corporation'],
    ['ABBV',  'AbbVie Inc.'],
    ['KO',  'The Coca-Cola Company'],
    ['CRM',  'Salesforce, Inc.'],
    ['NFLX',  'Netflix, Inc.'],
    ['AMD',  'Advanced Micro Devices, Inc.'],
    ['ADBE',  'Adobe Inc.'],
    ['PEP',  'PepsiCo, Inc.'],
    ['TMO',  'Thermo Fisher Scientific Inc.'],
    ['MCD',  'McDonald\'s Corporation'],
    ['CSCO',  'Cisco Systems, Inc.'],
    ['ACN',  'Accenture plc'],
    ['ABT',  'Abbott Laboratories'],
    ['DHR',  'Danaher Corporation'],
    ['LIN',  'Linde plc'],
    ['WFC',  'Wells Fargo & Company'],
    ['MRK',  'Merck & Co., Inc.'],
    ['DIS',  'The Walt Disney Company'],
    ['INTU',  'Intuit Inc.'],
    ['QCOM',  'QUALCOMM Incorporated'],
    ['TXN',  'Texas Instruments Incorporated'],
    ['INTC',  'Intel Corporation'],
    ['IBM',  'International Business Machines Corporation'],
    ['AMGN',  'Amgen Inc.'],
    ['GE',  'GE Aerospace'],
    ['CAT',  'Caterpillar Inc.'],
    ['NOW',  'ServiceNow, Inc.'],
    ['BKNG',  'Booking Holdings Inc.'],
    ['GS',  'The Goldman Sachs Group, Inc.'],
    ['MS',  'Morgan Stanley'],
    ['RTX',  'RTX Corporation'],
    ['BLK',  'BlackRock, Inc.'],
    ['SPGI',  'S&P Global Inc.'],
    ['PLD',  'Prologis, Inc.'],
    ['MDT',  'Medtronic plc'],
    ['ISRG',  'Intuitive Surgical, Inc.'],
    ['CMCSA',  'Comcast Corporation'],
    ['UBER',  'Uber Technologies, Inc.'],
    ['SHOP',  'Shopify Inc.'],
    ['PYPL',  'PayPal Holdings, Inc.'],
    ['NKE',  'NIKE, Inc.'],
    ['HON',  'Honeywell International Inc.'],
    ['LOW',  'Lowe\'s Companies, Inc.'],
    ['UNP',  'Union Pacific Corporation'],
    ['DE',  'Deere & Company'],
    ['BA',  'The Boeing Company'],
    ['AMAT',  'Applied Materials, Inc.'],
    ['MU',  'Micron Technology, Inc.'],
    ['ADI',  'Analog Devices, Inc.'],
    ['LRCX',  'Lam Research Corporation'],
    ['PANW',  'Palo Alto Networks, Inc.'],
    ['CRWD',  'CrowdStrike Holdings, Inc.'],
    ['SNOW',  'Snowflake Inc.'],
    ['KLAC',  'KLA Corporation'],
    ['PFE',  'Pfizer Inc.'],
    ['GILD',  'Gilead Sciences, Inc.'],
    ['COP',  'ConocoPhillips'],
    ['CVX',  'Chevron Corporation'],
    ['SLB',  'Schlumberger Limited'],
    ['BX',  'Blackstone Inc.'],
    ['SCHW',  'The Charles Schwab Corporation'],
    ['C',  'Citigroup Inc.'],
    ['USB',  'U.S. Bancorp'],
    ['AXP',  'American Express Company'],
    ['T',  'AT&T Inc.'],
    ['VZ',  'Verizon Communications Inc.'],
    ['TMUS',  'T-Mobile US, Inc.'],
    ['F',  'Ford Motor Company'],
    ['GM',  'General Motors Company'],
    ['RIVN',  'Rivian Automotive, Inc.'],
    ['SPY',  'SPDR S&P 500 ETF Trust'],
    ['QQQ',  'Invesco QQQ Trust'],
    ['IWM',  'iShares Russell 2000 ETF'],
    ['DIA',  'SPDR Dow Jones Industrial Average ETF Trust'],
    ['XLF',  'Financial Select Sector SPDR Fund'],
    ['XLK',  'Technology Select Sector SPDR Fund'],
    ['XLE',  'Energy Select Sector SPDR Fund'],
    ['SMH',  'VanEck Semiconductor ETF'],
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
