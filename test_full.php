<?php
// 完整计费流程测试
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

echo "=== Step 1: Login ===\n";
$resp = apiRequest("$baseUrl/auth/login", 'POST', ['mobile' => '13800138000', 'password' => '123456']);
$loginData = json_decode($resp, true);
$token = $loginData['data']['token'] ?? '';
echo "Response: $resp\n";
echo "Token: $token\n";

echo "\n=== Step 2: Check Quota (Before) ===\n";
$resp = apiRequest("$baseUrl/quota", 'GET', null, $token);
echo "Response: $resp\n";
$q = json_decode($resp, true);
$before = $q['data']['score'] ?? 0;
echo "Score Before: $before\n";

echo "\n=== Step 3: Report Token Consumption (new request_id) ===\n";
$reqId = 'final-test-' . time();
$resp = apiRequest("$baseUrl/quota/report", 'POST', [
    'model' => 'deepseek-chat',
    'input_tokens' => 3500,
    'output_tokens' => 500,
    'request_id' => $reqId
], $token);
echo "request_id: $reqId\n";
echo "input+output: 3500+500=4000 tokens\n";
echo "Response: $resp\n";

echo "\n=== Step 4: Check Quota (After) ===\n";
$resp = apiRequest("$baseUrl/quota", 'GET', null, $token);
echo "Response: $resp\n";
$q = json_decode($resp, true);
$after = $q['data']['score'] ?? 0;
echo "Score After: $after\n";
echo "Consumed: " . ($before - $after) . " points\n";
echo "Formula: ceil(4000 / 10000) = " . ceil(4000/10000) . " point\n";

echo "\n=== Step 5: Idempotent Test (duplicate request_id) ===\n";
$resp = apiRequest("$baseUrl/quota/report", 'POST', [
    'model' => 'deepseek-chat',
    'input_tokens' => 3500,
    'output_tokens' => 500,
    'request_id' => $reqId
], $token);
echo "Response: $resp\n";

echo "\n=== Step 6: Quota After Duplicate ===\n";
$resp = apiRequest("$baseUrl/quota", 'GET', null, $token);
echo "Response: $resp\n";

echo "\n=== Step 7: Update Check ===\n";
$resp = apiRequest("$baseUrl/update/check?version=1.0.0");
echo "Response: $resp\n";

echo "\n=== Step 8: Update Check (already latest) ===\n";
$resp = apiRequest("$baseUrl/update/check?version=1.1.0");
echo "Response: $resp\n";

echo "\n=== Step 9: API Key Info ===\n";
$resp = apiRequest("$baseUrl/apikey", 'GET', null, $token);
echo "Response: $resp\n";

echo "\n=== Step 10: Register (new user) ===\n";
$mobile = '139' . rand(10000000, 99999999);
$resp = apiRequest("$baseUrl/auth/register", 'POST', [
    'mobile' => $mobile,
    'password' => 'test123456'
]);
echo "mobile: $mobile\n";
echo "Response: $resp\n";

echo "\n=== Step 11: Login Error (wrong password) ===\n";
$resp = apiRequest("$baseUrl/auth/login", 'POST', ['mobile' => '13800138000', 'password' => 'wrongpassword']);
echo "Response: $resp\n";

echo "\n=== Step 12: Quota Without Token ===\n";
$resp = apiRequest("$baseUrl/quota");
echo "Response: $resp\n";
