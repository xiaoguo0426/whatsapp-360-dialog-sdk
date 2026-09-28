<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dialog360\Api\ConversationalComponentsApi;
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

// 配置会话自动化（Conversational Components: POST /conversational_automation）
$response = $client->conversationalComponents()->configure(
    [
        ConversationalComponentsApi::command('support', '联系人工客服'),
        ConversationalComponentsApi::command('pricing', '查询套餐价格'),
    ],
    false,                                // 启用欢迎消息
    [
        'hi'
//        '提示语1',
//        '提示语2',
//        '提示语3',
    ]           // 提示语列表
);

var_dump($response->toArray());
