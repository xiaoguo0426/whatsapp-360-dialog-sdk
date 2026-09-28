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

// 查询待审批的加群请求（Cloud API: GET /groups/{group_id}/join_requests）
$list = $client->groups()->listJoinRequests($groupId, limit: 25);
$ids = array_column($list->getJoinRequests(), 'join_request_id');

var_dump($list->getJoinRequests());

// 批准加群请求（Cloud API: POST /groups/{group_id}/join_requests）
if ($ids !== []) {
    $approved = $client->groups()->approveJoinRequests($groupId, $ids);
    var_dump($approved->toArray());

    // 拒绝加群请求（Cloud API: DELETE /groups/{group_id}/join_requests）
    // $rejected = $client->groups()->rejectJoinRequests($groupId, $ids);
}
