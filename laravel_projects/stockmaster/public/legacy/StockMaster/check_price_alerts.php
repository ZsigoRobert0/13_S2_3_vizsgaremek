<?php

require "db.php";

$q = $conn->query("
    SELECT p.UserID, p.Symbol, p.EntryPrice, p.Quantity, a.LastPrice
    FROM positions p
    JOIN assets a ON a.Symbol = p.Symbol
    WHERE p.IsOpen = 1
");

while ($row = $q->fetch_assoc()) {
    $change = ($row["LastPrice"] - $row["EntryPrice"]) / $row["EntryPrice"] * 100;

    if (abs($change) >= 5) {
        $title = "⚠ Nagy ármozgás: {$row['Symbol']}";
        $msg = "Az ár {$change}%-ot mozdult az entry óta.";

        $stmt = $conn->prepare("
            INSERT INTO notifications (UserID, Title, Message, CreatedAt, IsRead)
            VALUES (?, ?, ?, NOW(), 0)
        ");
        $stmt->bind_param("iss", $row["UserID"], $title, $msg);
        $stmt->execute();
    }
}
