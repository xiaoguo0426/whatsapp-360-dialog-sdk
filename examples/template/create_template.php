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

// 创建模板（Cloud API: POST /message_templates），需审批通过后才能用于发送
$response = $client->templates()->create(
    'promo',
    'en_US',
    'MARKETING',
    [
        ['type' => 'BODY', 'text' => 'Hello {{1}}, enjoy 20% off with code {{2}}!'],
    ],
    ['allow_category_change' => true] // 其余可选字段见 TemplateApi::CREATE_OPTIONS
);

echo "模板 ID: {$response->getTemplateId()}，状态: {$response->getStatus()}\n";
