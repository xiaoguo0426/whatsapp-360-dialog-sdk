<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\Message\ReactionMessage;
use Dialog360\Message\TextMessage;
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
    // ===== 第一步：发送一条文本消息，获取需要回应的消息 ID =====
    // 实际使用中，message_id 可以是任何已发送或收到的 WhatsApp 消息 ID（wamid.xxx 格式）
    $textMessage = new TextMessage(
        to: $to_phone_number, // 替换为实际的电话号码
        text: 'React to this message with an emoji!'
    );

    $textResponse = $client->sendMessage($textMessage);

    if (!$textResponse->isSuccess()) {
        throw new Exception('文本消息发送失败: ' . $textResponse->getErrorMessage());
    }

    $messageId = $textResponse->getMessageId();
    echo "📨 原始消息发送成功，消息ID: {$messageId}\n";

    // ===== 第二步：对该消息添加 emoji 回应 =====
    $message = ReactionMessage::react(
        to: $to_phone_number, // 替换为实际的电话号码
        messageId: $messageId,
        emoji: '👍'
    );

    // 也可以直接对已有的消息 ID 回应（无需先发送消息）
    // $message = ReactionMessage::react(
    //     to: '85268064134',
    //     messageId: 'wamid.HBgLMTY0NjcwNDM1OTUVAgARGBI1RjQyNUE3NEYxMzAzMzQ5MkEA',
    //     emoji: '❤️'
    // );

    // 移除已有回应（官方规定 emoji 传空字符串）
    // $message = ReactionMessage::remove(
    //     to: '85268064134',
    //     messageId: $messageId
    // );

    // 发送回应
    $response = $client->sendMessage($message);

    var_dump($response);

    // 检查响应
    if ($response->isSuccess()) {
        echo "✅ 回应消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 发送失败！\n";
        echo "错误代码: " . $response->getErrorCode() . "\n";
        echo "错误消息: " . $response->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
}
