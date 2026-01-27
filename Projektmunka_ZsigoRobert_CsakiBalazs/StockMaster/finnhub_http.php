<?php

function finnhub_get_json(string $url): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,

    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,

    CURLOPT_HTTPHEADER => ["Accept: application/json"],
  ]);

  $resp = curl_exec($ch);
  $errNo = curl_errno($ch);
  $err   = curl_error($ch);
  $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($resp === false || $errNo !== 0) {
    return [
      "_ok" => false,
      "_http" => $code,
      "_errNo" => $errNo,
      "_err" => $err
    ];
  }

  $data = json_decode($resp, true);
  if (!is_array($data)) {
    return [
      "_ok" => false,
      "_http" => $code,
      "_errNo" => -1,
      "_err" => "JSON decode error",
      "_raw" => mb_substr($resp, 0, 300)
    ];
  }

  $data["_ok"] = true;
  $data["_http"] = $code;
  return $data;
}
