<?php
// payments/paypal_capture_order.php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/functions.php';

header('Content-Type: application/json');

$config = require __DIR__ . '/paypal_config.php';

$clientId = $config['client_id'];
$secret   = $config['secret'];
$sandbox  = $config['sandbox'];

$apiBase  = $sandbox
    ? 'https://api.sandbox.paypal.com'
    : 'https://api.paypal.com';

// lexojmë orderId dhe plan nga frontend
$input = json_decode(file_get_contents('php://input'), true);

$orderId = $input['orderId'] ?? null;
$plan    = $input['plan']    ?? 'plan_unknown';
$amount  = $input['amount']  ?? 0;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Mungon orderId.']);
    exit;
}

// 1) Access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiBase . '/v1/oauth2/token');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Accept-Language: en_US',
]);
curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$tokenResult = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

$tokenData = json_decode($tokenResult, true);
$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Nuk mora access token nga PayPal.']);
    exit;
}

// 2) CAPTURE
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiBase . '/v2/checkout/orders/' . urlencode($orderId) . '/capture');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken,
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$captureResult = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

$captureData = json_decode($captureResult, true);

// nëse CAPTURE OK → ruajmë abonim në subscriptions.json
$status = $captureData['status'] ?? '';
if ($status === 'COMPLETED') {
    $user = current_user();

    $subsPath = DATA_PATH . '/subscriptions.json';
    $subs = load_json($subsPath);

    // id i ri
    $nextId = 1;
    foreach ($subs as $s) {
        if (($s['id'] ?? 0) >= $nextId) {
            $nextId = $s['id'] + 1;
        }
    }

    $now = time();

    // shembull: plani 2 euro → 30 ditë
    $days = 30;
    if ($plan === '5_euro') {
        $days = 90;
    } elseif ($plan === '10_euro') {
        $days = 180;
    }

    $newSub = [
        'id'              => $nextId,
        'user_id'         => $user['id'] ?? null,
        'email'           => $user['email'] ?? '',
        'plan'            => $plan,
        'amount'          => $amount,
        'status'          => 'active',
        'created_at'      => date('Y-m-d H:i:s', $now),
        'expires_at'      => date('Y-m-d H:i:s', strtotime("+{$days} days", $now)),
        'paypal_order_id' => $orderId,
    ];

    $subs[] = $newSub;
    save_json($subsPath, $subs);
}

echo json_encode($captureData);
