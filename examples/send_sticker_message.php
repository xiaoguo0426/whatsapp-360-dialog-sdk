<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\Message\StickerMessage;
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
    // ===== 方式一（官方推荐）：先上传贴图获取 media_id，再发送消息 =====

    // 本地贴图路径（仅支持 WebP 格式：动画贴图最大 500KB，静态贴图最大 100KB）
    $filePath = __DIR__ . '/cfc73d54db553000e80e1902fee857ea.jpeg'; // 替换为实际的贴图路径
    $mimeType = 'image/jpeg';

    // 上传媒体，获取 media_id
    $mediaId = $client->uploadMedia($filePath, $mimeType);
    echo "🎨 贴图上传成功，media_id: {$mediaId}\n";

    // 创建贴图消息
    $message = StickerMessage::fromMediaId(
        to: $to_phone_number, // 替换为实际的电话号码
        mediaId: $mediaId
    );

    // ===== 方式二：直接使用托管在公开服务器上的贴图 URL =====
    // $message = StickerMessage::fromUrl(
    //     to: '85268064134',
    //     link: 'https://www.example.com/assets/animated-smiling-plant.webp'
    // );

    // 发送消息
    $response = $client->sendMessage($message);

    var_dump($response);

    // 检查响应
    if ($response->isSuccess()) {
        echo "✅ 贴图消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 发送失败！\n";
        echo "错误代码: " . $response->getErrorCode() . "\n";
        echo "错误消息: " . $response->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
}
