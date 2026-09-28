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

// 更新主页资料（Cloud API: POST /whatsapp_business_profile）
// 可用字段见 ProfileApi::UPDATABLE_FIELDS，vertical 合法取值见 ProfileApi::VERTICALS
$response = $client->profile()->update([
    'about' => 'Hi, we are here to help',
    'description' => 'An e-commerce business',
    'email' => 'support@example.com',
    'address' => 'Room 1201, Tower A, Hong Kong',
    'vertical' => 'RETAIL',
    'websites' => ['https://example.com'],
]);

// 更新头像：先通过 Resumable Upload API 上传取得 handle，再提交 profile_picture_handle
// $client->profile()->update(['profile_picture_handle' => '<resumable-upload-handle>']);

var_dump($response->toArray());
