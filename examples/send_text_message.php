<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\Message\TextMessage;
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
    // 创建文本消息
    $message = new TextMessage(
        to: '1234567890', // 替换为实际的电话号码
        text: 'Hello from 360 Dialog PHP SDK!',
        previewUrl: false
    );

    // 发送消息
    $response = $client->sendMessage($message);

    // 检查响应
    if ($response->isSuccess()) {
        echo "✅ 消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
        
        // 获取消息状态
        $status = $client->getMessageStatus($response->getMessageId());
        echo "消息状态: " . $status->getStatus() . "\n";
    } else {
        echo "❌ 发送失败！\n";
        echo "错误代码: " . $response->getErrorCode() . "\n";
        echo "错误消息: " . $response->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
} 