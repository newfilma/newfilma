<?php
// payments/paypal_create_order.php

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

// Merrim të dhënat nga subscribe.php (plan, amount, description)
$input = json_decode(file_get_contents('php://input'), true);

$amount      = $input['amount']      ?? 0;
$description = $input['description'] ?? 'Abonim NewFilma';

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Shuma e pavlefshme.']);
    exit;
}

// 1) Marrim access token
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

// 2) Krijojmë order
$orderBody = [
    'intent' => 'CAPTURE',
    'purchase_units' => [
        [
            'amount' => [
                'currency_code' => 'EUR',
                'value' => number_format($amount, 2, '.', ''),
            ],
            'description' => $description,
        ],
    ],
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiBase . '/v2/checkout/orders');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken,
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderBody));

$orderResult = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

echo $orderResult;
