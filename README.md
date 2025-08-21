# 360 Dialog PHP SDK (Cloud API v2)

一个用于360 Dialog WhatsApp Business API Cloud API v2的PHP SDK包。

> **⚠️ 重要更新**: 本 SDK 已升级到 Cloud API v2。如果您从 v1 (On-Premise API) 迁移，请查看 [迁移指南](MIGRATION_V1_TO_V2.md)。

## 安装

```bash
composer require onetech/whatsapp-360-dialog-sdk
```

## 快速开始（Cloud API v2）

```php
<?php

use Dialog360\Dialog360Client;
use Dialog360\Message\TextMessage;
use Dialog360\EnvironmentLoader;

// 加载环境变量
EnvironmentLoader::load();

// 初始化客户端
$client = new Dialog360Client(
    EnvironmentLoader::get('DIALOG360_API_KEY'),
    EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID')
);

// 发送文本消息
$message = new TextMessage('1234567890', 'Hello from 360 Dialog!');
$response = $client->sendMessage($message);

// 检查响应
if ($response->isSuccess()) {
    echo "消息发送成功！";
    // Cloud API 可查询健康状态（替代 v1 的消息状态查询）
    $health = $client->getHealthStatus();
    echo "健康状态: " . ($health['health_status']['can_send_message'] ?? 'UNKNOWN');
} else {
    echo "发送失败: " . $response->getErrorMessage();
}
```

## 功能特性

- ✅ 发送文本消息
- ✅ 发送媒体消息（图片、音频、视频、文档）
- ✅ 发送模板消息
- ✅ 发送交互式消息（按钮、列表）
- ✅ 健康检查（Cloud API）
- ✅ 获取媒体文件
- ✅ 错误处理和重试机制
- ✅ 完整的类型提示
- ✅ 单元测试覆盖

## 配置

### 环境变量

创建 `.env` 文件并添加以下配置：

```bash
# 360 Dialog API 配置
DIALOG360_API_KEY=your-api-key
DIALOG360_PHONE_NUMBER_ID=your-phone-number-id
DIALOG360_BASE_URL=https://waba-v2.360dialog.io

# 应用环境
APP_ENV=development
APP_DEBUG=true
```

### 客户端配置

```php
use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;

// 加载环境变量
EnvironmentLoader::load();

$client = new Dialog360Client(
    apiKey: EnvironmentLoader::get('DIALOG360_API_KEY'),
    phoneNumberId: EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID'),
    baseUrl: EnvironmentLoader::get('DIALOG360_BASE_URL', 'https://waba-v2.360dialog.io'),
    timeout: (int) EnvironmentLoader::get('DIALOG360_TIMEOUT', 30),
    retryAttempts: (int) EnvironmentLoader::get('DIALOG360_RETRY_ATTEMPTS', 3)
);
```

## 消息类型

### 文本消息

```php
use Dialog360\Message\TextMessage;

$message = new TextMessage(
    to: '1234567890',
    text: 'Hello World!',
    previewUrl: false // 可选，是否显示链接预览
);
```

### 媒体消息

```php
use Dialog360\Message\MediaMessage;

// 图片消息
$imageMessage = new MediaMessage(
    to: '1234567890',
    type: 'image',
    url: 'https://example.com/image.jpg',
    caption: 'Beautiful image!' // 可选
);

// 音频消息
$audioMessage = new MediaMessage(
    to: '1234567890',
    type: 'audio',
    url: 'https://example.com/audio.mp3'
);

// 视频消息
$videoMessage = new MediaMessage(
    to: '1234567890',
    type: 'video',
    url: 'https://example.com/video.mp4',
    caption: 'Check out this video!'
);

// 文档消息
$documentMessage = new MediaMessage(
    to: '1234567890',
    type: 'document',
    url: 'https://example.com/document.pdf',
    filename: 'document.pdf' // 可选
);
```

### 模板消息

```php
use Dialog360\Message\TemplateMessage;

$templateMessage = new TemplateMessage(
    to: '1234567890',
    templateName: 'hello_world',
    language: 'en_US',
    components: [
        [
            'type' => 'body',
            'parameters' => [
                [
                    'type' => 'text',
                    'text' => 'John'
                ]
            ]
        ]
    ]
);
```

### 交互式消息

```php
use Dialog360\Message\InteractiveMessage;

// 按钮消息
$buttonMessage = new InteractiveMessage(
    to: '1234567890',
    type: 'button',
    body: 'Choose an option:',
    buttons: [
        [
            'type' => 'reply',
            'reply' => [
                'id' => 'btn_1',
                'title' => 'Option 1'
            ]
        ],
        [
            'type' => 'reply',
            'reply' => [
                'id' => 'btn_2',
                'title' => 'Option 2'
            ]
        ]
    ]
);

// 列表消息
$listMessage = new InteractiveMessage(
    to: '1234567890',
    type: 'list',
    body: 'Select from the list:',
    action: [
        'button' => 'View Options',
        'sections' => [
            [
                'title' => 'Section 1',
                'rows' => [
                    [
                        'id' => 'item_1',
                        'title' => 'Item 1',
                        'description' => 'Description for item 1'
                    ],
                    [
                        'id' => 'item_2',
                        'title' => 'Item 2',
                        'description' => 'Description for item 2'
                    ]
                ]
            ]
        ]
    ]
);
```

## 健康状态（Cloud API）

```php
$health = $client->getHealthStatus();
echo $health['health_status']['can_send_message'] ?? 'UNKNOWN';
```

## 获取媒体文件

```php
use Dialog360\Media;

$media = $client->getMedia('media-id');
$fileContent = $media->getContent();
$fileInfo = $media->getInfo();
```

## 错误处理

```php
use Dialog360\Exception\Dialog360Exception;

try {
    $response = $client->sendMessage($message);
    
    if (!$response->isSuccess()) {
        echo "错误: " . $response->getErrorMessage();
        echo "错误代码: " . $response->getErrorCode();
    }
} catch (Dialog360Exception $e) {
    echo "SDK错误: " . $e->getMessage();
} catch (\Exception $e) {
    echo "一般错误: " . $e->getMessage();
}
```

## 测试

```bash
# 运行所有测试
composer test

# 运行测试并生成覆盖率报告
composer test-coverage

# 运行静态分析
composer phpstan
```

## 迁移支持

如果您从 v1 (On-Premise API) 迁移到 v2 (Cloud API)：

1. 📖 阅读 [迁移指南](MIGRATION_V1_TO_V2.md)
2. 🔄 更新基础 URL 到 `https://waba-v2.360dialog.io`
3. 🔑 确保使用最新的 API 密钥
4. 🧪 运行测试验证配置

## 贡献

欢迎提交Issue和Pull Request！

## 许可证

MIT License 