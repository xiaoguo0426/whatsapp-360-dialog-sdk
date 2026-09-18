<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;
use Dialog360\Message\MediaMessage;

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
    // 发送图片消息
    $imageMessage = MediaMessage::image(
        to: $to_phone_number,
        url: 'https://example.com/image.jpg',
        caption: 'Beautiful sunset!'
    );

    $response = $client->sendMessage($imageMessage);

    if ($response->isSuccess()) {
        echo "✅ 图片消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 图片消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }

    // 发送音频消息
    $audioMessage = MediaMessage::audio(
        to: $to_phone_number,
        url: 'https://example.com/audio.mp3'
    );

    $response = $client->sendMessage($audioMessage);

    if ($response->isSuccess()) {
        echo "✅ 音频消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 音频消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }

    // 发送视频消息
    $videoMessage = MediaMessage::video(
        to: $to_phone_number,
        url: 'https://example.com/video.mp4',
        caption: 'Check out this amazing video!'
    );

    $response = $client->sendMessage($videoMessage);

    if ($response->isSuccess()) {
        echo "✅ 视频消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 视频消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }

    // 发送文档消息
    $documentMessage = MediaMessage::document(
        to: $to_phone_number,
        url: 'https://example.com/document.pdf',
        caption: 'Important document for you',
        filename: 'document.pdf'
    );

    $response = $client->sendMessage($documentMessage);

    if ($response->isSuccess()) {
        echo "✅ 文档消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 文档消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }

} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
} 