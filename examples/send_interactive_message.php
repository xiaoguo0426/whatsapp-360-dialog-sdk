<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\Message\InteractiveMessage;
use Dialog360\EnvironmentLoader;

// 加载环境变量
EnvironmentLoader::load();

// 从环境变量获取配置
$apiKey = EnvironmentLoader::get('DIALOG360_API_KEY', 'your-api-key');
$phoneNumberId = EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID', 'your-phone-number-id');
$baseUrl = EnvironmentLoader::get('DIALOG360_BASE_URL', 'https://waba-api.360dialog.io');
$timeout = (int)EnvironmentLoader::get('DIALOG360_TIMEOUT', 30);
$retryAttempts = (int)EnvironmentLoader::get('DIALOG360_RETRY_ATTEMPTS', 3);

// 初始化客户端
$client = new Dialog360Client($apiKey, $phoneNumberId, $baseUrl, $timeout, $retryAttempts);

try {
    // 发送按钮消息
    $buttonMessage = InteractiveMessage::button(
        to: '1234567890',
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

    // 发送列表消息
    $listMessage = InteractiveMessage::list(
        to: '1234567890',
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

    // 发送产品消息
    $productMessage = InteractiveMessage::product(
        to: '1234567890',
        body: '查看我们的特色产品:',
        action: [
            'catalog_id' => 'catalog-id',
            'product_retailer_id' => 'product-id'
        ],
        footer: '点击查看产品详情'
    );

    $response = $client->sendMessage($productMessage);

    if ($response->isSuccess()) {
        echo "✅ 产品消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 产品消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }

} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
} 