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

// 分页查询模板列表（Cloud API: GET /message_templates）
$list = $client->templates()->listMessageTemplates(limit: 25);
var_dump($list->getTemplates());

// 翻页：下一页把 getAfterCursor() 传回 $after
if ($list->hasNextPage()) {
    $next = $client->templates()->listMessageTemplates(limit: 25, after: $list->getAfterCursor());
    var_dump($next->getTemplates());
}

// 按 ID 获取模板详情（Cloud API: GET /message_templates/{template_id}）
if ($list->getTemplates() !== []) {
    $templateId = $list->getTemplates()[0]['id'];
    $template = $client->templates()->get($templateId);
    echo "模板: {$template->getName()}，状态: {$template->getStatus()}，分类: {$template->getCategory()}\n";
}
