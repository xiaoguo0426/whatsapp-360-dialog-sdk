<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;

// 加载环境变量
EnvironmentLoader::load();

// 从环境变量获取配置
$apiKey = EnvironmentLoader::get('DIALOG360_API_KEY', 'your-api-key');
$phoneNumberId = EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID', 'your-phone-number-id');
$baseUrl = EnvironmentLoader::get('DIALOG360_BASE_URL', 'https://waba-v2.360dialog.io');
$timeout = (int)EnvironmentLoader::get('DIALOG360_TIMEOUT', 30);
$retryAttempts = (int)EnvironmentLoader::get('DIALOG360_RETRY_ATTEMPTS', 3);

// 初始化客户端
$client = new Dialog360Client($apiKey, $phoneNumberId, $baseUrl, $timeout, $retryAttempts);

// 拉黑用户（Cloud API: POST /block_users）
$response = $client->blockUsers()->block(['+1234567890', '+9876543210']);

if (!$response->isSuccess()) {
    // 400"混合成功/失败或错误请求"：部分结果与 error 对象都在响应对象里
    var_dump($response->getError());
}

// 成功处理的用户：[['input' => ..., 'wa_id' => ...], ...]
var_dump($response->getAddedUsers());

// 处理失败的用户（含逐用户的 errors 详情）
var_dump($response->getFailedUsers());
