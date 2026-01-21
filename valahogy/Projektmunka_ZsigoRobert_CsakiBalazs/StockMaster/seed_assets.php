<?php
// seed_assets.php
require "db.php";

// Növeljük egy kicsit a limitet, ha sokat szúrunk
set_time_limit(60);

$assets = [
    // --- USA tech + mega caps ---
    ['AAPL',  'Apple Inc.'],
    ['MSFT',  'Microsoft Corporation'],
    ['GOOGL', 'Alphabet Inc. Class A'],
    ['GOOG',  'Alphabet Inc. Class C'],
    ['AMZN',  'Amazon.com Inc.'],
    ['META',  'Meta Platforms Inc.'],
    ['TSLA',  'Tesla Inc.'],
    ['NVDA',  'NVIDIA Corporation'],
    ['BRK.B', 'Berkshire Hathaway Inc. Class B'],
    ['BRK.A', 'Berkshire Hathaway Inc. Class A'],
    ['JPM',   'JPMorgan Chase & Co.'],
    ['JNJ',   'Johnson & Johnson'],
    ['V',     'Visa Inc.'],
    ['MA',    'Mastercard Incorporated'],
    ['PG',    'Procter & Gamble Company'],
    ['HD',    'Home Depot Inc.'],
    ['DIS',   'Walt Disney Company'],
    ['NFLX',  'Netflix Inc.'],
    ['ADBE',  'Adobe Inc.'],
    ['CSCO',  'Cisco Systems Inc.'],
    ['INTC',  'Intel Corporation'],
    ['ORCL',  'Oracle Corporation'],
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
    ['VZ',    'Verizon Communications Inc.'],
    ['ABBV',  'AbbVie Inc.'],
    ['CRM',   'Salesforce Inc.'],
    ['AVGO',  'Broadcom Inc.'],
    ['TXN',   'Texas Instruments Incorporated'],
    ['AMD',   'Advanced Micro Devices Inc.'],
    ['QCOM',  'Qualcomm Incorporated'],
    ['PYPL',  'PayPal Holdings Inc.'],
    ['SHOP',  'Shopify Inc.'],
    ['UBER',  'Uber Technologies Inc.'],
    ['LYFT',  'Lyft Inc.'],

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
    ['SPY',   'SPDR S&P 500 ETF Trust'],  // ETF, de tanuláshoz jó

    // --- Európa / UK blue chip ---
    ['NESN.SW', 'Nestlé S.A.'],
    ['ROG.SW',  'Roche Holding AG'],
    ['NOVN.SW', 'Novartis AG'],
    ['SAP.DE',  'SAP SE'],
    ['SIE.DE',  'Siemens AG'],
    ['ALV.DE',  'Allianz SE'],
    ['BMW.DE',  'Bayerische Motoren Werke AG'],
    ['MBG.DE',  'Mercedes-Benz Group AG'],
    ['VOW3.DE','Volkswagen AG'],
    ['BAS.DE',  'BASF SE'],
    ['DTE.DE',  'Deutsche Telekom AG'],
    ['DBK.DE',  'Deutsche Bank AG'],
    ['AIR.PA',  'Airbus SE'],
    ['OR.PA',   'L’Oréal S.A.'],
    ['MC.PA',   'LVMH Moët Hennessy Louis Vuitton SE'],
    ['SAN.PA',  'Sanofi S.A.'],
    ['BN.PA',   'Danone S.A.'],
    ['RDSA.L',  'Shell plc'],
    ['BP.L',    'BP plc'],
    ['HSBA.L',  'HSBC Holdings plc'],
    ['ULVR.L',  'Unilever plc'],
    ['RIO.L',   'Rio Tinto plc'],
    ['AZN.L',   'AstraZeneca plc'],
    ['BATS.L',  'British American Tobacco p.l.c.'],
    ['GSK.L',   'GSK plc'],

    // --- Magyar blue chip (BÉT) ---
    ['OTP',     'OTP Bank Nyrt.'],
    ['MOL',     'MOL Magyar Olaj- és Gázipari Nyrt.'],
    ['RICHTER', 'Richter Gedeon Nyrt.'],
];

$stmt = $conn->prepare("INSERT IGNORE INTO assets (Symbol, Name, IsTradable) VALUES (?, ?, 1)");
if (!$stmt) {
    die("Prepare error: " . $conn->error);
}

$inserted = 0;
foreach ($assets as $a) {
    [$sym, $name] = $a;
    $stmt->bind_param("ss", $sym, $name);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $inserted++;
    }
}

echo "Sikeresen beszúrva: {$inserted} részvény az assets táblába.";
