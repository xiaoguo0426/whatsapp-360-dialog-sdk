<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;

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

// 查询营销消息模板列表（Cloud API: GET /marketing/message_templates）
$list = $client->marketing()->listTemplates(limit: 25);
var_dump($list->getTemplates());

// 按 ID 获取单个模板（确认审批状态：status === 'APPROVED'）
if ($list->getTemplates() !== []) {
    $templateId = $list->getTemplates()[0]['id'];
    $template = $client->marketing()->getTemplate($templateId);
    var_dump($template);
}

// 模板分析（响应结构由 API 定义，SDK 返回原始数组）
$analytics = $client->marketing()->getTemplateAnalytics(['date_preset' => 'last_7d']);
var_dump($analytics);

// 首次使用需先开启分析跟踪
// $client->marketing()->enableTemplateAnalytics();
