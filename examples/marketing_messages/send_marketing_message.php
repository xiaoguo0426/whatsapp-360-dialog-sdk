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

// 发送营销消息（Cloud API: POST /marketing_messages）
// language 传字符串会自动包装为 ['code' => ...]；发送前请确认模板已获批准
$response = $client->marketing()->send(
    '+1234567890',
    [
        'name' => 'promo_template',
        'language' => 'en',
        'components' => [
            ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'Alice']]],
        ],
    ],
    1.0   // bid_spec.per_message_bid_multiplier，默认 1
);

if ($response->isSuccess()) {
    echo "消息 ID: {$response->getMessageId()}，状态: {$response->getMessageStatus()}\n";
} else {
    // 4xx（如模板未批准）不抛异常，通过 getErrorMessage() 获取原因
    echo "发送失败: {$response->getErrorMessage()}\n";
}

// 接入 BSUID 的商家可用 recipient 代替手机号：
// $client->marketing()->send('', ['name' => 'promo_template', 'language' => 'en'], null, ['recipient' => 'US.abc123']);
