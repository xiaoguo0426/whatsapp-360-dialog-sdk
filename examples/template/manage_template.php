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

$templateId = 'your-template-id';

// 编辑模板（Cloud API: POST /message_templates/{template_id}，仅 APPROVED/REJECTED 可编辑）
// 可更新字段见 TemplateApi::UPDATABLE_FIELDS
$response = $client->templates()->update($templateId, [
    'category' => 'MARKETING',
    'components' => [
        ['type' => 'BODY', 'text' => 'Updated body text'],
    ],
]);

var_dump($response->isSuccess());

// 删除模板（三选一）：
// 按名称删除（全部语言版本）
// $client->templates()->deleteByName('promo');
// 按 ID 删除（单个语言版本）
// $client->templates()->deleteById($templateId);
// 按 ID 批量删除（单次最多 100 个）
// $client->templates()->deleteByIds([$templateId]);

// 归档 / 恢复（归档 28 天后自动删除；仅 APPROVED/REJECTED 可归档）
// $client->templates()->archive([$templateId]);
// $client->templates()->unarchive([$templateId]);

// 模板效果对比（阻塞率 / 发送量 / 主要阻塞原因，需发送量 ≥ 1000）
// $compare = $client->templates()->compare($templateId, ['other-template-id'], time() - 30*86400, time());
