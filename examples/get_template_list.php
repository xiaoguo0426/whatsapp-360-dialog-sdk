<?php

require_once __DIR__ . '/../vendor/autoload.php';

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

try {
    $filters = [
//        'id' => 'generic_msg_v2',
//        'partner_id'=>'1OyuqDPA',
        'business_templates.name'=>'camp_inv_v_8', //没有测试通过
//        'status'=>'rejected'   //approved/rejected/created
//            'category'=>'',
    ];
    $response = $client->getTemplates($filters, 'id');
    var_dump($response->getWabaTemplates());

} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
} 