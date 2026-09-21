<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\Message\LocationMessage;
use Dialog360\EnvironmentLoader;

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
    // 创建位置消息（完整字段：经纬度 + 名称 + 地址）
    $message = LocationMessage::named(
        to: $to_phone_number, // 替换为实际的电话号码
        longitude: 114.1694,
        latitude: 22.2933,
        name: 'Victoria Harbour',
        address: 'Tsim Sha Tsui, Hong Kong'
    );

    // 也可以仅发送经纬度（最简形式）
//     $message = LocationMessage::coordinates(
//         to: $to_phone_number,
//         longitude: 114.1694,
//         latitude: 22.2933
//     );

    // 发送消息
    $response = $client->sendMessage($message);

    var_dump($response);

    // 检查响应
    if ($response->isSuccess()) {
        echo "✅ 位置消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 发送失败！\n";
        echo "错误代码: " . $response->getErrorCode() . "\n";
        echo "错误消息: " . $response->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
}
