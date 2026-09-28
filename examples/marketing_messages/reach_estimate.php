<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dialog360\Api\MarketingApi;
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

// 触达估算（Cloud API: GET /marketing/reachestimate）
// date_interval 合法取值见 MarketingApi::DATE_INTERVALS（L1D/L7D/L14D/L28D）
$estimate = $client->marketing()->getReachEstimate(
    ['geo_locations' => ['countries' => ['BR']]],
    'L7D'
);

echo "货币: {$estimate->getCurrency()}\n";
var_dump($estimate->getEstimates());
