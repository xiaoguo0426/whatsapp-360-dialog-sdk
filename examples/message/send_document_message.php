<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;
use Dialog360\Message\DocumentMessage;

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
    // ===== 方式一（官方推荐）：先上传文档获取 media_id，再发送消息 =====

    // 本地文档路径（支持: txt, pdf, doc, docx, xls, xlsx, ppt, pptx，最大 100MB）
    $filePath = __DIR__ . '/sample.pdf'; // 替换为实际的文档路径
    $mimeType = 'application/pdf';

    // 上传媒体，获取 media_id
    $mediaId = $client->uploadMedia($filePath, $mimeType);
    echo "📄 文档上传成功，media_id: {$mediaId}\n";

    // 创建文档消息
    $message = DocumentMessage::fromMediaId(
        to: $to_phone_number, // 替换为实际的电话号码
        mediaId: $mediaId,
        caption: 'Your order confirmation (PDF)', // 可选，最多 1024 字符
        filename: 'order_abc123.pdf' // 可选，客户端根据扩展名显示文件类型图标
    );

    // ===== 方式二：直接使用托管在公开服务器上的文档 URL =====
    // $message = DocumentMessage::fromUrl(
    //     to: '85268064134',
    //     link: 'https://www.example.com/invoices/lucky-shrub-invoice.pdf',
    //     caption: 'Lucky Shrub Invoice',
    //     filename: 'lucky-shrub-invoice.pdf'
    // );

    // 发送消息
    $response = $client->sendMessage($message);

    var_dump($response);

    // 检查响应
    if ($response->isSuccess()) {
        echo "✅ 文档消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 发送失败！\n";
        echo "错误代码: " . $response->getErrorCode() . "\n";
        echo "错误消息: " . $response->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
}
