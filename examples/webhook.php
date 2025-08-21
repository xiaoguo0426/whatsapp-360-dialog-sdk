<?php
/**
 * 360 Dialog Webhook 接收器
 *
 * 这个文件用于接收和处理来自 360 Dialog 的 webhook 消息
 * 包括消息接收、状态更新、媒体下载等功能
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置日志文件
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/webhook_errors.log');

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 加载 Composer 自动加载器
require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;
use Dialog360\Message\TextMessage;

// 加载环境变量
EnvironmentLoader::load();

class Dialog360WebhookReceiver
{
    private Dialog360Client $client;
    private string $verifyToken;
    private array $webhookEvents = [];

    public function __construct()
    {
        // 从环境变量获取配置
        $apiKey = EnvironmentLoader::get('DIALOG360_API_KEY');
        $phoneNumberId = EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID');
        $baseUrl = EnvironmentLoader::get('DIALOG360_BASE_URL', 'https://waba-v2.360dialog.io');

        if (!$apiKey || !$phoneNumberId) {
            throw new Exception('缺少必要的环境变量: DIALOG360_API_KEY 或 DIALOG360_PHONE_NUMBER_ID');
        }

        $this->client = new Dialog360Client($apiKey, $phoneNumberId, $baseUrl);
        $this->verifyToken = EnvironmentLoader::get('DIALOG360_VERIFY_TOKEN', 'your-verify-token');
    }

    /**
     * 处理 webhook 请求
     */
    public function handleWebhook(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        try {
            switch ($method) {
                case 'GET':
                    $this->handleVerification();
                    break;
                case 'POST':
                    $this->handleIncomingMessage();
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    break;
            }
        } catch (Exception $e) {
            $this->logError('Webhook 处理错误: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * 处理 webhook 验证请求
     */
    private function handleVerification(): void
    {
        $mode = $_GET['hub_mode'] ?? '';
        $token = $_GET['hub_verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? '';

        if ($mode === 'subscribe' && $token === $this->verifyToken) {
            echo $challenge;
            $this->logInfo('Webhook 验证成功');
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Verification failed']);
            $this->logError('Webhook 验证失败');
        }
    }

    /**
     * 处理接收到的消息
     */
    private function handleIncomingMessage(): void
    {
        // 获取原始 POST 数据
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $this->logInfo($data);
        return ;
        if (!$data) {
            $this->logError('无法解析 webhook 数据');
            http_response_code(400);
            return;
        }

        $this->logInfo('收到 webhook 数据: ' . $input);

        // 处理不同类型的 webhook 事件
        if (isset($data['entry'][0]['changes'][0]['value'])) {
            $value = $data['entry'][0]['changes'][0]['value'];

            // 处理消息
            if (isset($value['messages'])) {
                $this->processMessages($value['messages']);
            }

            // 处理状态更新
            if (isset($value['statuses'])) {
                $this->processStatuses($value['statuses']);
            }

            // 处理其他事件
            if (isset($value['messaging_product'])) {
                $this->processOtherEvents($value);
            }
        }

        // 返回成功响应
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    /**
     * 处理接收到的消息
     */
    private function processMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->logInfo('处理消息: ' . json_encode($message));

            $messageType = $message['type'] ?? 'unknown';
            $from = $message['from'] ?? '';
            $timestamp = $message['timestamp'] ?? '';
            $messageId = $message['id'] ?? '';

            switch ($messageType) {
                case 'text':
                    $this->handleTextMessage($message);
                    break;
                case 'image':
                    $this->handleImageMessage($message);
                    break;
                case 'audio':
                    $this->handleAudioMessage($message);
                    break;
                case 'video':
                    $this->handleVideoMessage($message);
                    break;
                case 'document':
                    $this->handleDocumentMessage($message);
                    break;
                case 'location':
                    $this->handleLocationMessage($message);
                    break;
                case 'contacts':
                    $this->handleContactsMessage($message);
                    break;
                case 'interactive':
                    $this->handleInteractiveMessage($message);
                    break;
                default:
                    $this->logInfo("未处理的消息类型: {$messageType}");
                    break;
            }
        }
    }

    /**
     * 处理文本消息
     */
    private function handleTextMessage(array $message): void
    {
        $text = $message['text']['body'] ?? '';
        $from = $message['from'] ?? '';

        $this->logInfo("收到来自 {$from} 的文本消息: {$text}");

        // 这里可以添加您的业务逻辑
        // 例如：自动回复、消息转发、内容分析等

        // 示例：自动回复
        if (str_contains(strtolower($text), 'hello') || str_contains(strtolower($text), 'hi')) {
            $this->sendAutoReply($from, 'Hello! 感谢您的消息，我们会尽快回复您。');
        }
    }

    /**
     * 处理图片消息
     */
    private function handleImageMessage(array $message): void
    {
        $image = $message['image'] ?? [];
        $from = $message['from'] ?? '';
        $mediaId = $image['id'] ?? '';
        $mimeType = $image['mime_type'] ?? '';
        $sha256 = $image['sha256'] ?? '';

        $this->logInfo("收到来自 {$from} 的图片消息: {$mediaId}");

        // 下载图片
        try {
            $mediaInfo = $this->client->getMediaInfo($mediaId);
            $this->logInfo("图片信息: " . json_encode($mediaInfo->toArray()));

            // 这里可以添加图片处理逻辑
            // 例如：图片识别、存储、转发等

        } catch (Exception $e) {
            $this->logError("获取图片信息失败: " . $e->getMessage());
        }
    }

    /**
     * 处理音频消息
     */
    private function handleAudioMessage(array $message): void
    {
        $audio = $message['audio'] ?? [];
        $from = $message['from'] ?? '';
        $mediaId = $audio['id'] ?? '';

        $this->logInfo("收到来自 {$from} 的音频消息: {$mediaId}");

        // 处理音频消息的逻辑
    }

    /**
     * 处理视频消息
     */
    private function handleVideoMessage(array $message): void
    {
        $video = $message['video'] ?? [];
        $from = $message['from'] ?? '';
        $mediaId = $video['id'] ?? '';

        $this->logInfo("收到来自 {$from} 的视频消息: {$mediaId}");

        // 处理视频消息的逻辑
    }

    /**
     * 处理文档消息
     */
    private function handleDocumentMessage(array $message): void
    {
        $document = $message['document'] ?? [];
        $from = $message['from'] ?? '';
        $mediaId = $document['id'] ?? '';
        $filename = $document['filename'] ?? '';

        $this->logInfo("收到来自 {$from} 的文档消息: {$filename} ({$mediaId})");

        // 处理文档消息的逻辑
    }

    /**
     * 处理位置消息
     */
    private function handleLocationMessage(array $message): void
    {
        $location = $message['location'] ?? [];
        $from = $message['from'] ?? '';
        $latitude = $location['latitude'] ?? '';
        $longitude = $location['longitude'] ?? '';

        $this->logInfo("收到来自 {$from} 的位置消息: {$latitude}, {$longitude}");

        // 处理位置消息的逻辑
    }

    /**
     * 处理联系人消息
     */
    private function handleContactsMessage(array $message): void
    {
        $contacts = $message['contacts'] ?? [];
        $from = $message['from'] ?? '';

        $this->logInfo("收到来自 {$from} 的联系人消息");

        foreach ($contacts as $contact) {
            $name = $contact['name'] ?? [];
            $phones = $contact['phones'] ?? [];

            $this->logInfo("联系人: " . ($name['formatted_name'] ?? 'Unknown'));
        }
    }

    /**
     * 处理交互式消息
     */
    private function handleInteractiveMessage(array $message): void
    {
        $interactive = $message['interactive'] ?? [];
        $from = $message['from'] ?? '';
        $type = $interactive['type'] ?? '';

        $this->logInfo("收到来自 {$from} 的交互式消息: {$type}");

        if ($type === 'button_reply') {
            $buttonReply = $interactive['button_reply'] ?? [];
            $buttonId = $buttonReply['id'] ?? '';
            $buttonTitle = $buttonReply['title'] ?? '';

            $this->logInfo("按钮回复: {$buttonTitle} ({$buttonId})");

            // 根据按钮ID处理不同的操作
            $this->handleButtonReply($from, $buttonId, $buttonTitle);

        } elseif ($type === 'list_reply') {
            $listReply = $interactive['list_reply'] ?? [];
            $listId = $listReply['id'] ?? '';
            $listTitle = $listReply['title'] ?? '';

            $this->logInfo("列表回复: {$listTitle} ({$listId})");

            // 处理列表选择
            $this->handleListReply($from, $listId, $listTitle);
        }
    }

    /**
     * 处理按钮回复
     */
    private function handleButtonReply(string $from, string $buttonId, string $buttonTitle): void
    {
        switch ($buttonId) {
            case 'btn_help':
                $this->sendAutoReply($from, "您选择了帮助选项。我们的客服团队将尽快联系您。");
                break;
            case 'btn_info':
                $this->sendAutoReply($from, "您选择了信息选项。这里是我们的产品信息...");
                break;
            default:
                $this->sendAutoReply($from, "您选择了: {$buttonTitle}");
                break;
        }
    }

    /**
     * 处理列表回复
     */
    private function handleListReply(string $from, string $listId, string $listTitle): void
    {
        $this->sendAutoReply($from, "您选择了: {$listTitle}");
    }

    /**
     * 处理状态更新
     */
    private function processStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? '';
            $statusType = $status['status'] ?? '';
            $timestamp = $status['timestamp'] ?? '';

            $this->logInfo("消息状态更新: {$messageId} -> {$statusType}");

            // 根据状态类型处理不同的逻辑
            switch ($statusType) {
                case 'sent':
                    $this->handleMessageSent($messageId);
                    break;
                case 'delivered':
                    $this->handleMessageDelivered($messageId);
                    break;
                case 'read':
                    $this->handleMessageRead($messageId);
                    break;
                case 'failed':
                    $this->handleMessageFailed($messageId, $status);
                    break;
            }
        }
    }

    /**
     * 处理其他事件
     */
    private function processOtherEvents(array $value): void
    {
        // 处理其他类型的 webhook 事件
        $this->logInfo("其他事件: " . json_encode($value));
    }

    /**
     * 处理消息发送成功
     */
    private function handleMessageSent(string $messageId): void
    {
        $this->logInfo("消息 {$messageId} 发送成功");
        // 可以在这里添加业务逻辑，如更新数据库状态
    }

    /**
     * 处理消息送达
     */
    private function handleMessageDelivered(string $messageId): void
    {
        $this->logInfo("消息 {$messageId} 已送达");
        // 可以在这里添加业务逻辑
    }

    /**
     * 处理消息已读
     */
    private function handleMessageRead(string $messageId): void
    {
        $this->logInfo("消息 {$messageId} 已被阅读");
        // 可以在这里添加业务逻辑
    }

    /**
     * 处理消息发送失败
     */
    private function handleMessageFailed(string $messageId, array $status): void
    {
        $error = $status['errors'] ?? [];
        $this->logError("消息 {$messageId} 发送失败: " . json_encode($error));
        // 可以在这里添加重试逻辑或通知管理员
    }

    /**
     * 发送自动回复
     */
    private function sendAutoReply(string $to, string $text): void
    {
        try {

            $message = new TextMessage($to, $text);
            $response = $this->client->sendMessage($message);

            if ($response->isSuccess()) {
                $this->logInfo("自动回复发送成功: {$to} -> {$text}");
            } else {
                $this->logError("自动回复发送失败: " . $response->getErrorMessage());
            }
        } catch (Exception $e) {
            $this->logError("发送自动回复时出错: " . $e->getMessage());
        }
    }

    /**
     * 记录信息日志
     */
    private function logInfo(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] INFO: {$message}" . PHP_EOL;
        file_put_contents(__DIR__ . '/webhook_info.log', $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * 记录错误日志
     */
    private function logError(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] ERROR: {$message}" . PHP_EOL;
        file_put_contents(__DIR__ . '/webhook_errors.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// 创建 webhook 接收器实例并处理请求
try {
    $receiver = new Dialog360WebhookReceiver();
    $receiver->handleWebhook();
    echo 'ok';
} catch (Exception $e) {
    error_log('Webhook 接收器初始化失败: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}