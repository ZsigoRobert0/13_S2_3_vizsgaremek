<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/*
| Finnhub HTTP Client
| Használat:
| $data = finnhub_get_json("https://finnhub.io/api/v1/quote?symbol=AAPL");
*/

function finnhub_get_json(string $url): array
{
    // API key hozzáfűzése, ha nincs benne
    if (defined('FINNHUB_API_KEY') && FINNHUB_API_KEY !== '') {
        $sep = (str_contains($url, '?')) ? '&' : '?';
        if (!str_contains($url, 'token=')) {
            $url .= $sep . 'token=' . urlencode(FINNHUB_API_KEY);
        }
    }

    $ch = curl_init($url);

    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "User-Agent: StockMaster/1.0"
        ],
    ]);

    // SSL kezelés (localhost vs production)
    if ($isLocal) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }

    $resp = curl_exec($ch);
    $errNo = curl_errno($ch);
    $err   = curl_error($ch);
    $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    // Curl error
    if ($resp === false || $errNo !== 0) {
        return [
            "ok" => false,
            "http" => $code,
            "error" => "Curl error: " . $err,
            "errno" => $errNo
        ];
    }

    // HTTP error
    if ($code >= 400) {
        return [
            "ok" => false,
            "http" => $code,
            "error" => "HTTP error",
            "raw" => mb_substr((string)$resp, 0, 300)
        ];
    }

    $data = json_decode((string)$resp, true);

    if (!is_array($data)) {
        return [
            "ok" => false,
            "http" => $code,
            "error" => "JSON decode error",
            "raw" => mb_substr((string)$resp, 0, 300)
        ];
    }

    return [
        "ok" => true,
        "http" => $code,
        "data" => $data
    ];
}
