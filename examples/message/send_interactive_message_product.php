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
    // 发送产品消息
    $productMessage = InteractiveMessage::product(
        to: $to_phone_number,
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