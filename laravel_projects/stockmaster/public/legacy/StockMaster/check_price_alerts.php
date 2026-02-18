<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

legacy_require_login_api();

$conn = legacy_db();

// Lekérjük az összes nyitott pozíciót
$sql = "
    SELECT p.UserID, p.Symbol, p.EntryPrice, p.Quantity, a.LastPrice
    FROM positions p
    JOIN assets a ON a.Symbol = p.Symbol
    WHERE p.IsOpen = 1
";

$q = $conn->query($sql);

if (!$q) {
    legacy_json([
        'ok' => false,
        'error' => 'Query failed: ' . $conn->error
    ], 500);
}

$created = 0;

while ($row = $q->fetch_assoc()) {

    if ((float)$row["EntryPrice"] <= 0) {
        continue;
    }

    $change = (($row["LastPrice"] - $row["EntryPrice"]) / $row["EntryPrice"]) * 100;

    if (abs($change) >= 5) {

        $title = "⚠ Nagy ármozgás: {$row['Symbol']}";
        $msg   = "Az ár " . round($change, 2) . "%-ot mozdult az entry óta.";

        $stmt = $conn->prepare("
            INSERT INTO notifications (UserID, Title, Message, CreatedAt, IsRead)
            VALUES (?, ?, ?, NOW(), 0)
        ");

        if ($stmt) {
            $stmt->bind_param("iss", $row["UserID"], $title, $msg);
            $stmt->execute();
            $created++;
        }
    }
}

// Visszajelzés JSON-ben
legacy_json([
    'ok' => true,
    'notifications_created' => $created
]);
