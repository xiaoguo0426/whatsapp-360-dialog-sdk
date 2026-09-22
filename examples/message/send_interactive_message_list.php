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
    // 发送列表消息
    $listMessage = InteractiveMessage::list(
        to: $to_phone_number,
        body: '请选择您感兴趣的产品类别:',
        action: [
            'button' => '查看产品',
            'sections' => [
                [
                    'title' => '电子产品',
                    'rows' => [
                        [
                            'id' => 'smartphone',
                            'title' => '智能手机',
                            'description' => '最新款智能手机'
                        ],
                        [
                            'id' => 'laptop',
                            'title' => '笔记本电脑',
                            'description' => '高性能笔记本电脑'
                        ]
                    ]
                ],
                [
                    'title' => '服装',
                    'rows' => [
                        [
                            'id' => 'shirt',
                            'title' => 'T恤',
                            'description' => '舒适透气的T恤'
                        ],
                        [
                            'id' => 'pants',
                            'title' => '裤子',
                            'description' => '时尚休闲裤'
                        ]
                    ]
                ]
            ]
        ],
        footer: '选择您感兴趣的产品'
    );

    $response = $client->sendMessage($listMessage);

    if ($response->isSuccess()) {
        echo "✅ 列表消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 列表消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
} 