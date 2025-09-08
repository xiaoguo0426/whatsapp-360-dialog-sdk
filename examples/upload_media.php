<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;
use Dialog360\Message\MediaMessage;
use Dialog360\Exception\Dialog360Exception;

// 加载环境变量
EnvironmentLoader::load();

// 从环境变量获取配置
$apiKey = EnvironmentLoader::get('DIALOG360_API_KEY', 'your-api-key');
$phoneNumberId = EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID', 'your-phone-number-id');
$baseUrl = EnvironmentLoader::get('DIALOG360_BASE_URL', 'https://waba-v2.360dialog.io');
$timeout = (int)EnvironmentLoader::get('DIALOG360_TIMEOUT', 30);
$retryAttempts = (int)EnvironmentLoader::get('DIALOG360_RETRY_ATTEMPTS', 3);

// 初始化客户端
$client = new Dialog360Client($apiKey, $phoneNumberId, $baseUrl, $timeout, $retryAttempts);

try {
//    echo "=== 媒体上传和发送示例 ===\n\n";
//
//    // 示例1: 上传图片文件
//    echo "1. 上传图片文件\n";
//    $imagePath = __DIR__ . '/files/file_example.aac'; // 请确保此文件存在
//
//    if (!file_exists($imagePath)) {
//        echo "请先准备一个测试图片文件: {$imagePath}\n";
//    } else {
//        try {
//            $mediaId = $client->uploadMedia($imagePath, 'audio/aac');
//            echo "图片上传成功，媒体ID: {$mediaId}\n";
//
//            // 使用媒体ID发送图片消息
////            $mediaId = '3717826858526032';
//            $message = MediaMessage::audioById('85268064134', $mediaId);
//            $response = $client->sendMessage($message);
//            echo "图片消息发送成功，消息ID: {$response->getMessageId()}\n\n";
//        } catch (Dialog360Exception $e) {
//            echo "上传失败: " . $e->getMessage() . "\n\n";
//        }
//    }

    // 示例2: 上传音频文件
//    echo "2. 上传音频文件\n";
//    $audioPath = __DIR__ . '/test_audio.mp3'; // 请确保此文件存在
//
//    if (!file_exists($audioPath)) {
//        echo "请先准备一个测试音频文件: {$audioPath}\n";
//        echo "支持的格式: AAC, AMR, MP3, M4A, OGG\n";
//        echo "最大大小: 16MB\n\n";
//    } else {
//        try {
//            $mediaId = $client->uploadMedia($audioPath, 'audio/mpeg');
//            echo "音频上传成功，媒体ID: {$mediaId}\n";
//
//            // 使用媒体ID发送音频消息
//            $message = MediaMessage::audioById('1234567890', $mediaId);
//            $response = $client->sendMessage($message);
//            echo "音频消息发送成功，消息ID: {$response->getMessageId()}\n\n";
//        } catch (Dialog360Exception $e) {
//            echo "上传失败: " . $e->getMessage() . "\n\n";
//        }
//    }

    // 示例3: 上传视频文件
    echo "3. 上传视频文件\n";
    $videoPath = __DIR__ . '/files/localsend.mp4'; // 请确保此文件存在

    if (!file_exists($videoPath)) {
        echo "请先准备一个测试视频文件: {$videoPath}\n";
        echo "支持的格式: MP4, 3GP\n";
        echo "最大大小: 16MB\n\n";
    } else {
        try {
            $mediaId = $client->uploadMedia($videoPath, 'video/mp4');
            echo "视频上传成功，媒体ID: {$mediaId}\n";

            // 使用媒体ID发送视频消息
            $message = MediaMessage::videoById('85268064134', $mediaId, '这是一个通过媒体ID发送的视频');
            $response = $client->sendMessage($message);
            echo "视频消息发送成功，消息ID: {$response->getMessageId()}\n\n";
        } catch (Dialog360Exception $e) {
            echo "上传失败: " . $e->getMessage() . "\n\n";
        }
    }

    // 示例4: 上传文档文件
//    echo "4. 上传文档文件\n";
//    $docPath = __DIR__ . '/test_document.pdf'; // 请确保此文件存在
//
//    if (!file_exists($docPath)) {
//        echo "请先准备一个测试文档文件: {$docPath}\n";
//        echo "支持的格式: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT\n";
//        echo "最大大小: 100MB\n\n";
//    } else {
//        try {
//            $mediaId = $client->uploadMedia($docPath, 'application/pdf');
//            echo "文档上传成功，媒体ID: {$mediaId}\n";
//
//            // 使用媒体ID发送文档消息
//            $message = MediaMessage::documentById('1234567890', $mediaId, '这是一个通过媒体ID发送的文档', 'test_document.pdf');
//            $response = $client->sendMessage($message);
//            echo "文档消息发送成功，消息ID: {$response->getMessageId()}\n\n";
//        } catch (Dialog360Exception $e) {
//            echo "上传失败: " . $e->getMessage() . "\n\n";
//        }
//    }

    // 示例5: 获取媒体信息
//    echo "5. 获取媒体信息\n";
//    if (isset($mediaId)) {
//        try {
//            $mediaInfo = $client->getMediaInfo($mediaId);
//            echo "媒体信息:\n";
//            echo "- 媒体ID: {$mediaInfo->getMediaId()}\n";
//            echo "- MIME类型: {$mediaInfo->getMimeType()}\n";
//            echo "- 文件大小: {$mediaInfo->getFileSize()} 字节\n";
//            echo "- SHA256: {$mediaInfo->getSha256()}\n";
//            echo "- 文件扩展名: {$mediaInfo->getFileExtension()}\n";
//            echo "- 是否为图片: " . ($mediaInfo->isImage() ? '是' : '否') . "\n";
//            echo "- 是否为音频: " . ($mediaInfo->isAudio() ? '是' : '否') . "\n";
//            echo "- 是否为视频: " . ($mediaInfo->isVideo() ? '是' : '否') . "\n";
//            echo "- 是否为文档: " . ($mediaInfo->isDocument() ? '是' : '否') . "\n\n";
//        } catch (Dialog360Exception $e) {
//            echo "获取媒体信息失败: " . $e->getMessage() . "\n\n";
//        }
//    }
//
//    // 示例6: 下载媒体文件
//    echo "6. 下载媒体文件\n";
//    if (isset($mediaId)) {
//        try {
//            $downloadPath = __DIR__ . '/downloaded_media';
//            $content = $client->downloadMedia($mediaId, $downloadPath);
//            echo "媒体文件下载成功，保存到: {$downloadPath}\n";
//            echo "文件大小: " . strlen($content) . " 字节\n\n";
//        } catch (Dialog360Exception $e) {
//            echo "下载媒体文件失败: " . $e->getMessage() . "\n\n";
//        }
//    }
//
//    // 示例7: 删除媒体文件
//    echo "7. 删除媒体文件\n";
//    if (isset($mediaId)) {
//        try {
//            $success = $client->deleteMedia($mediaId);
//            if ($success) {
//                echo "媒体文件删除成功\n";
//            } else {
//                echo "媒体文件删除失败\n";
//            }
//        } catch (Dialog360Exception $e) {
//            echo "删除媒体文件失败: " . $e->getMessage() . "\n";
//        }
//    }

} catch (Dialog360Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "未知错误: " . $e->getMessage() . "\n";
}