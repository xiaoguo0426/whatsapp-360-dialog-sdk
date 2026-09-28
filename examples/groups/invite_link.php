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

$groupId = 'your-group-id';

// 获取当前邀请链接（Cloud API: GET /groups/{group_id}/invite_link）
$link = $client->groups()->getInviteLink($groupId);
echo "邀请链接: {$link->getInviteLink()}\n";

// 重置邀请链接（旧链接立即失效；确认需要时再执行）
// $reset = $client->groups()->resetInviteLink($groupId);
// echo "新邀请链接: {$reset->getInviteLink()}\n";
