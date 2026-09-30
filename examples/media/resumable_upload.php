<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;
use Dialog360\Api\MediaApi;

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

$filePath = __DIR__ . '/../message/files/localsend.mp4'; // 请确保此文件存在
$mimeType = 'video/mp4';

if (!file_exists($filePath)) {
    echo "请先准备一个测试文件: {$filePath}\n";
    exit;
}

// ===== 方式一（推荐）：一步完成分块续传，返回文件句柄 handle =====
// handle 目前主要用于更新头像等资料（普通消息仍建议用 media()->upload() 拿 media_id）
$handle = $client->media()->uploadResumable($filePath, $mimeType, chunkSize: MediaApi::DEFAULT_CHUNK_SIZE);
echo "上传成功，handle: {$handle->getHandle()}\n";

// ===== 方式二：手动控制会话（断点续传场景，按需启用） =====
// $fileSize = (int) filesize($filePath);
//
// // 1. 创建上传会话（POST /uploads）
// $session = $client->media()->createUploadSession(basename($filePath), $fileSize, $mimeType);
// $sessionId = $session->getSessionId();
// echo "会话已创建: {$sessionId}，服务端偏移: {$session->getFileOffset()}\n";
//
// // 2. 逐块上传（POST /upload:{session-id}，file_offset 头指明起点）
// $stream = fopen($filePath, 'rb');
// $offset = $session->getFileOffset();
// fseek($stream, $offset);
//
// while (!feof($stream)) {
//     $chunk = fread($stream, 4 * 1024 * 1024); // 4MB，须为 8KB 的整数倍
//     if ($chunk === false || $chunk === '') {
//         break;
//     }
//
//     $response = $client->media()->uploadChunk($sessionId, $chunk, $offset);
//     $offset += strlen($chunk);
//     echo "已上传 {$offset}/{$fileSize} 字节\n";
// }
// fclose($stream);
//
// // 3. 最后一个分块的响应即包含 handle
// echo "handle: {$response->getHandle()}\n";
//
// // 4. 中断后可查询会话状态，从返回的偏移继续（GET /upload:{session-id}）
// $status = $client->media()->getUploadSessionStatus($sessionId);
// echo "服务端当前偏移: {$status->getFileOffset()}\n";

// 也可以把已有会话ID直接交给 uploadResumable，自动从服务端偏移续传：
// $handle = $client->media()->uploadResumable($filePath, $mimeType, sessionId: $sessionId);
