<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;
use Dialog360\Message\ContactsMessage;

// 加载环境变量
EnvironmentLoader::load();

// 从环境变量获取配置
$apiKey = EnvironmentLoader::get('DIALOG360_API_KEY', 'your-api-key');
$phoneNumberId = EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID', 'your-phone-number-id');
$baseUrl = EnvironmentLoader::get('DIALOG360_BASE_URL', 'https://waba-v2.360dialog.io');
$timeout = (int)EnvironmentLoader::get('DIALOG360_TIMEOUT', 30);
$retryAttempts = (int)EnvironmentLoader::get('DIALOG360_RETRY_ATTEMPTS', 3);

$to_phone_number = EnvironmentLoader::get('TO_PHONE_NUMBER', '');

// 初始化客户端
$client = new Dialog360Client($apiKey, $phoneNumberId, $baseUrl, $timeout, $retryAttempts);

try {
    // 构建联系人（完整字段示例，除 name.formatted_name 外均为可选）
    $contacts = [
        [
            // 姓名（formatted_name 必填）
            'name' => ContactsMessage::name(
                formattedName: 'Zhang San',
                firstName: 'San',
                lastName: 'Zhang'
            ),
            // 生日，格式 YYYY-MM-DD
            'birthday' => '1990-01-01',
            // 电话（可选，type: CELL / MAIN / IPHONE / HOME / WORK）
            'phones' => [
                ContactsMessage::phone('85268064134', 'CELL'),
                ContactsMessage::phone('85212345678', 'WORK'),
            ],
            // 邮箱（可选，type: HOME / WORK）
            'emails' => [
                ContactsMessage::email('zhangsan@example.com', 'WORK'),
            ],
            // 地址（可选）
            'addresses' => [
                ContactsMessage::address(
                    street: '123 Queen\'s Road Central',
                    city: 'Hong Kong',
                    country: 'China',
                    countryCode: 'HK',
                    type: 'WORK'
                ),
            ],
            // 组织信息（可选）
            'org' => ContactsMessage::org(
                company: 'Example Company Ltd.',
                department: 'Engineering',
                title: 'Software Engineer'
            ),
            // 网址（可选）
            'urls' => [
                ContactsMessage::url('https://example.com', 'WORK'),
            ],
        ],
        // 也可以只发送最简联系人（仅姓名 + 电话）
        [
            'name' => ContactsMessage::name(
                formattedName: 'Li Si',
                lastName: 'Li',
                firstName: 'Si'
            ),
            'phones' => [
                ContactsMessage::phone('85298765432', 'MAIN'),
            ],
        ],
    ];

    // 创建联系人消息
    $message = new ContactsMessage(
        to: $to_phone_number, // 替换为实际的电话号码
        contacts: $contacts
    );

    // 发送消息
    $response = $client->sendMessage($message);

    var_dump($response);

    // 检查响应
    if ($response->isSuccess()) {
        echo "✅ 消息发送成功！\n";
        echo "消息ID: " . $response->getMessageId() . "\n";
    } else {
        echo "❌ 发送失败！\n";
        echo "错误代码: " . $response->getErrorCode() . "\n";
        echo "错误消息: " . $response->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
}
