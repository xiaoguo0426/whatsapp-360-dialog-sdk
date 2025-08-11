<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\Message\TemplateMessage;
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
    // 发送简单的模板消息
    $templateMessage = new TemplateMessage(
        to: '1234567890',
        templateName: 'hello_world',
        language: 'en_US'
    );
    
    $response = $client->sendMessage($templateMessage);
    
    if ($response->isSuccess()) {
        echo "✅ 模板消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 模板消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }

    // 发送带参数的模板消息
    $templateWithParams = new TemplateMessage(
        to: '1234567890',
        templateName: 'welcome_message',
        language: 'en_US',
        components: [
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => 'John'
                    ],
                    [
                        'type' => 'text',
                        'text' => 'Doe'
                    ]
                ]
            ],
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => [
                            'link' => 'https://example.com/header-image.jpg'
                        ]
                    ]
                ]
            ],
            [
                'type' => 'button',
                'sub_type' => 'quick_reply',
                'index' => 0,
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => 'Yes'
                    ]
                ]
            ]
        ]
    );
    
    $response = $client->sendMessage($templateWithParams);
    
    if ($response->isSuccess()) {
        echo "✅ 带参数的模板消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 带参数的模板消息发送失败！\n";
        echo "错误: " . $response->getErrorMessage() . "\n";
    }

    // 获取可用的模板
    $templates = $client->getTemplates();
    echo "📋 可用的模板:\n";
    foreach ($templates['data'] ?? [] as $template) {
        echo "- " . $template['name'] . " (" . $template['language'] . ")\n";
    }

} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
} 