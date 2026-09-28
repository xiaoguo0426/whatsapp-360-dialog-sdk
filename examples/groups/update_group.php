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

// 更新群组名称与描述（Cloud API: POST /groups/{group_id}）
$response = $client->groups()->update($groupId, [
    'subject' => 'New Subject',
    'description' => 'New description',
]);

var_dump($response->isSuccess());

// 更新群组头像：传入本地图片路径，SDK 自动改用 multipart 提交
// $response = $client->groups()->update($groupId, [
//     'profile_picture_file' => '/path/to/avatar.jpg',
// ]);
