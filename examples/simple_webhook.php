<?php
/**
 * 360 Dialog 简单 Webhook 接收器
 * 
 * 这是一个简化版本的 webhook 接收器，用于快速测试和开发
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 加载 Composer 自动加载器
require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\Message\TextMessage;
use Dialog360\EnvironmentLoader;

// 加载环境变量
EnvironmentLoader::load(__DIR__.'/../.env');

// 配置
$config = [
    'verify_token' => EnvironmentLoader::get('DIALOG360_VERIFY_TOKEN', 'your-verify-token'),
    'api_key' => EnvironmentLoader::get('DIALOG360_API_KEY'),
    'phone_number_id' => EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID'),
    'base_url' => EnvironmentLoader::get('DIALOG360_BASE_URL', 'https://waba-v2.360dialog.io'),
];

// 日志函数
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$type}: {$message}" . PHP_EOL;
    file_put_contents(__DIR__ . '/webhook.log', $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage; // 同时输出到控制台
}

// 处理 GET 请求（webhook 验证）
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    if ($mode === 'subscribe' && $token === $config['verify_token']) {
        echo $challenge;
        logMessage('Webhook 验证成功');
    } else {
        http_response_code(403);
        echo 'Verification failed';
        logMessage('Webhook 验证失败', 'ERROR');
    }
    exit;
}

// 处理 POST 请求（接收消息）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 获取原始 POST 数据
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('无法解析 webhook 数据');
        }
        
        logMessage('收到 webhook 数据: ' . $input);
        
        // 处理 webhook 事件
        if (isset($data['entry'][0]['changes'][0]['value'])) {
            $value = $data['entry'][0]['changes'][0]['value'];
            
            // 处理消息
            if (isset($value['messages'])) {
                foreach ($value['messages'] as $message) {
                    processMessage($message, $config);
                }
            }
            
            // 处理状态更新
            if (isset($value['statuses'])) {
                foreach ($value['statuses'] as $status) {
                    processStatus($status);
                }
            }
        }
        
        // 返回成功响应
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        
    } catch (Exception $e) {
        logMessage('处理 webhook 时出错: ' . $e->getMessage(), 'ERROR');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * 处理接收到的消息
 */
function processMessage($message, $config) {
    $messageType = $message['type'] ?? 'unknown';
    $from = $message['from'] ?? '';
    $timestamp = $message['timestamp'] ?? '';
    
    logMessage("收到来自 {$from} 的 {$messageType} 消息");
    
    switch ($messageType) {
        case 'text':
            handleTextMessage($message, $config);
            break;
        case 'image':
            handleImageMessage($message);
            break;
        case 'audio':
            handleAudioMessage($message);
            break;
        case 'video':
            handleVideoMessage($message);
            break;
        case 'document':
            handleDocumentMessage($message);
            break;
        case 'location':
            handleLocationMessage($message);
            break;
        case 'contacts':
            handleContactsMessage($message);
            break;
        case 'interactive':
            handleInteractiveMessage($message, $config);
            break;
        default:
            logMessage("未处理的消息类型: {$messageType}");
            break;
    }
}

/**
 * 处理文本消息
 */
function handleTextMessage($message, $config) {
    $text = $message['text']['body'] ?? '';
    $from = $message['from'] ?? '';
    
    logMessage("文本消息内容: {$text}");
    
    // 简单的自动回复逻辑
    if (str_contains(strtolower($text), 'hello') || str_contains(strtolower($text), 'hi')) {
        sendAutoReply($from, 'Hello! 感谢您的消息，我们会尽快回复您。', $config);
    } elseif (str_contains(strtolower($text), 'help')) {
        sendAutoReply($from, '您需要帮助吗？请描述您的问题，我们会为您提供支持。', $config);
    } elseif (str_contains(strtolower($text), 'price')) {
        sendAutoReply($from, '关于价格信息，请访问我们的网站或联系客服。', $config);
    }
}

/**
 * 处理图片消息
 */
function handleImageMessage($message) {
    $image = $message['image'] ?? [];
    $mediaId = $image['id'] ?? '';
    $mimeType = $image['mime_type'] ?? '';
    
    logMessage("图片消息: ID={$mediaId}, 类型={$mimeType}");
    
    // 这里可以添加图片处理逻辑
    // 例如：下载、存储、分析等
}

/**
 * 处理音频消息
 */
function handleAudioMessage($message) {
    $audio = $message['audio'] ?? [];
    $mediaId = $audio['id'] ?? '';
    
    logMessage("音频消息: ID={$mediaId}");
}

/**
 * 处理视频消息
 */
function handleVideoMessage($message) {
    $video = $message['video'] ?? [];
    $mediaId = $video['id'] ?? '';
    
    logMessage("视频消息: ID={$mediaId}");
}

/**
 * 处理文档消息
 */
function handleDocumentMessage($message) {
    $document = $message['document'] ?? [];
    $mediaId = $document['id'] ?? '';
    $filename = $document['filename'] ?? '';
    
    logMessage("文档消息: {$filename} (ID: {$mediaId})");
}

/**
 * 处理位置消息
 */
function handleLocationMessage($message) {
    $location = $message['location'] ?? [];
    $latitude = $location['latitude'] ?? '';
    $longitude = $location['longitude'] ?? '';
    
    logMessage("位置消息: {$latitude}, {$longitude}");
}

/**
 * 处理联系人消息
 */
function handleContactsMessage($message) {
    $contacts = $message['contacts'] ?? [];
    
    foreach ($contacts as $contact) {
        $name = $contact['name'] ?? [];
        $formattedName = $name['formatted_name'] ?? 'Unknown';
        logMessage("联系人消息: {$formattedName}");
    }
}

/**
 * 处理交互式消息
 */
function handleInteractiveMessage($message, $config) {
    $interactive = $message['interactive'] ?? [];
    $type = $interactive['type'] ?? '';
    $from = $message['from'] ?? '';
    
    logMessage("交互式消息类型: {$type}");
    
    if ($type === 'button_reply') {
        $buttonReply = $interactive['button_reply'] ?? [];
        $buttonId = $buttonReply['id'] ?? '';
        $buttonTitle = $buttonReply['title'] ?? '';
        
        logMessage("按钮回复: {$buttonTitle} ({$buttonId})");
        
        // 根据按钮ID发送不同的回复
        switch ($buttonId) {
            case 'btn_help':
                sendAutoReply($from, '您选择了帮助选项。我们的客服团队将尽快联系您。', $config);
                break;
            case 'btn_info':
                sendAutoReply($from, '您选择了信息选项。这里是我们的产品信息...', $config);
                break;
            case 'btn_contact':
                sendAutoReply($from, '您选择了联系我们。请留下您的联系方式，我们会尽快回复。', $config);
                break;
            default:
                sendAutoReply($from, "您选择了: {$buttonTitle}", $config);
                break;
        }
        
    } elseif ($type === 'list_reply') {
        $listReply = $interactive['list_reply'] ?? [];
        $listId = $listReply['id'] ?? '';
        $listTitle = $listReply['title'] ?? '';
        
        logMessage("列表回复: {$listTitle} ({$listId})");
        sendAutoReply($from, "您选择了: {$listTitle}", $config);
    }
}

/**
 * 处理状态更新
 */
function processStatus($status) {
    $messageId = $status['id'] ?? '';
    $statusType = $status['status'] ?? '';
    $timestamp = $status['timestamp'] ?? '';
    
    logMessage("消息状态更新: {$messageId} -> {$statusType}");
    
    switch ($statusType) {
        case 'sent':
            logMessage("消息 {$messageId} 发送成功");
            break;
        case 'delivered':
            logMessage("消息 {$messageId} 已送达");
            break;
        case 'read':
            logMessage("消息 {$messageId} 已被阅读");
            break;
        case 'failed':
            $error = $status['errors'] ?? [];
            logMessage("消息 {$messageId} 发送失败: " . json_encode($error), 'ERROR');
            break;
    }
}

/**
 * 发送自动回复
 */
function sendAutoReply($to, $text, $config) {
    try {
        if (!$config['api_key'] || !$config['phone_number_id']) {
            logMessage('缺少 API 配置，无法发送自动回复', 'ERROR');
            return;
        }
        
        $client = new Dialog360Client(
            $config['api_key'],
            $config['phone_number_id'],
            $config['base_url']
        );
        
        $message = new TextMessage($to, $text);
        $response = $client->sendMessage($message);
        
        if ($response->isSuccess()) {
            logMessage("自动回复发送成功: {$to} -> {$text}");
        } else {
            logMessage("自动回复发送失败: " . $response->getErrorMessage(), 'ERROR');
        }
        
    } catch (Exception $e) {
        logMessage("发送自动回复时出错: " . $e->getMessage(), 'ERROR');
    }
}

// 如果不是 GET 或 POST 请求
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
} 