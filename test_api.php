<?php
// 测试剩余 API（跳过 Apikey 接口的 UTF-8 问题）
$baseUrl = 'http://localhost:8888/index.php/api/client/v1';

function apiRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

echo "=== Login ===\n";
$resp = apiRequest("$baseUrl/auth/login", 'POST', ['mobile' => '13800138000', 'password' => '123456']);
$loginData = json_decode($resp, true);
$token = $loginData['data']['token'] ?? '';
echo "token: $token\n";

echo "\n=== Quota ===\n";
echo apiRequest("$baseUrl/quota", 'GET', null, $token) . "\n";

echo "\n=== Report Token ===\n";
echo apiRequest("$baseUrl/quota/report", 'POST', [
    'model' => 'deepseek-chat',
    'input_tokens' => 1500,
    'output_tokens' => 500,
    'request_id' => 'test-report-001'
], $token) . "\n";

echo "\n=== Quota After Report ===\n";
echo apiRequest("$baseUrl/quota", 'GET', null, $token) . "\n";

echo "\n=== Idempotent Test (same request_id) ===\n";
echo apiRequest("$baseUrl/quota/report", 'POST', [
    'model' => 'deepseek-chat',
    'input_tokens' => 1500,
    'output_tokens' => 500,
    'request_id' => 'test-report-001'
], $token) . "\n";

echo "\n=== Update Check ===\n";
echo apiRequest("$baseUrl/update/check?version=1.0.0") . "\n";
