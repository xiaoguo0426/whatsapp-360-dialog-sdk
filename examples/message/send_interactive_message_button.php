<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;
use Dialog360\Message\InteractiveMessage;

// 加载环境变量
EnvironmentLoader::load();

// 从环境变量获取配置
$apiKey = EnvironmentLoader::get('DIALOG360_API_KEY', 'your-api-key');
$phoneNumberId = EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID', 'your-phone-number-id');
$baseUrl = EnvironmentLoader::get('DIALOG360_BASE_URL', 'https://waba-v2.360dialog.io');
$timeout = (int)EnvironmentLoader::get('DIALOG360_TIMEOUT', 30);
$retryAttempts = (int)EnvironmentLoader::get('DIALOG360_RETRY_ATTEMPTS', 3);

$to_phone_number = EnvironmentLoader::get('TO_PHONE_NUMBER', '');

// 初始化客户端
$client = new Dialog360Client($apiKey, $phoneNumberId, $baseUrl, $timeout, $retryAttempts);

try {
    // 发送按钮消息
    $buttonMessage = InteractiveMessage::button(
        to: $to_phone_number,
        body: '请选择您喜欢的颜色:',
        buttons: [
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'red',
                    'title' => '红色'
                ]
            ],
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'blue',
                    'title' => '蓝色'
                ]
            ],
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'green',
                    'title' => '绿色'
                ]
            ]
        ],
        footer: '点击按钮进行选择'
    );

    $response = $client->sendMessage($buttonMessage);

    if ($response->isSuccess()) {
        echo "✅ 按钮消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 按钮消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
} 