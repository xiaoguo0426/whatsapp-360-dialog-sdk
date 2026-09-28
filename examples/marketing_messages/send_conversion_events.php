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

// 上报 Conversions API 事件（Cloud API: POST /marketing/{dataset_id}/events）
// dataset_id 来自 $client->marketing()->getDataset()/createDataset()；单次最多 1000 条
$response = $client->marketing()->sendEvents('your-dataset-id', [
    [
        'event_name' => 'Purchase',
        'event_time' => time(),
        'user' => ['phone_number' => '+1234567890'],
        'custom_data' => ['order_id' => 'order-123'],
    ],
]);

echo "已接收事件: {$response->getEventsReceived()}\n";
echo "fbtrace_id: {$response->getFbtraceId()}\n";
