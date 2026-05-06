<?php
// --- AYARLAR ---
$secretToken = "benim_cok_gizli_anahtarim_123"; // Bu anahtarı kimseyle paylaşma!
$postMaxSizeStr = ini_get('post_max_size'); // php.ini'den gelen değer (örn: 50M)

// --- FONKSİYONLAR ---
function returnBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// --- 1. GÜVENLİK KATI: BOYUT KONTROLÜ ---
$contentLength = (int)$_SERVER['CONTENT_LENGTH'];
$postMaxSize = returnBytes($postMaxSizeStr);

if ($contentLength > $postMaxSize) {
    http_response_code(413);
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Payload too large! Veri limiti aşıldı.",
        "limit" => $postMaxSizeStr,
        "received" => round($contentLength / 1024 / 1024, 2) . "MB"
    ]);
    exit;
}

// --- 2. GÜVENLİK KATI: HMAC İMZA DOĞRULAMASI ---
$receivedSignature = $_SERVER['HTTP_X_SIGNATURE'] ?? ''; 
$rawData = file_get_contents('php://input');
$expectedSignature = hash_hmac('sha256', $rawData, $secretToken);

if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "İmza hatalı! Yetkisiz veya değiştirilmiş veri."
    ]);
    exit;
}

// --- TÜM KONTROLLER GEÇTİ ---
header('Content-Type: application/json');
echo json_encode([
    "status" => "success",
    "message" => "Veri hem boyut hem de kimlik açısından doğrulandı. İşlenmeye hazır."
]);
?>
