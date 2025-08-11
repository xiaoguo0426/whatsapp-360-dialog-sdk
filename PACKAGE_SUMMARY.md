# 360 Dialog PHP SDK 包总结

## 📦 包概述

这是一个完整的 360 Dialog WhatsApp Business API 的 PHP SDK 包，提供了简单易用的接口来发送各种类型的 WhatsApp 消息。

## 🏗️ 包结构

```
360-dialog/
├── composer.json              # Composer 配置文件
├── README.md                  # 主要文档
├── INSTALL.md                 # 安装指南
├── LICENSE                    # MIT 许可证
├── .gitignore                 # Git 忽略文件
├── phpunit.xml               # PHPUnit 测试配置
├── phpstan.neon              # PHPStan 静态分析配置
├── validate.php              # 包验证脚本
├── src/                      # 源代码目录
│   ├── Dialog360Client.php   # 主客户端类
│   ├── Message/              # 消息类型
│   │   ├── MessageInterface.php
│   │   ├── TextMessage.php
│   │   ├── MediaMessage.php
│   │   ├── TemplateMessage.php
│   │   └── InteractiveMessage.php
│   ├── Response/             # 响应类
│   │   ├── MessageResponse.php
│   │   ├── MessageStatusResponse.php
│   │   └── MediaResponse.php
│   └── Exception/            # 异常类
│       └── Dialog360Exception.php
├── tests/                    # 测试文件
│   └── Dialog360ClientTest.php
└── examples/                 # 示例代码
    ├── send_text_message.php
    ├── send_media_message.php
    ├── send_template_message.php
    └── send_interactive_message.php
```

## 🚀 主要功能

### 1. 消息发送
- ✅ 文本消息
- ✅ 媒体消息（图片、音频、视频、文档）
- ✅ 模板消息
- ✅ 交互式消息（按钮、列表、产品）

### 2. 消息管理
- ✅ 获取消息状态
- ✅ 下载媒体文件
- ✅ 获取媒体信息

### 3. 账户管理
- ✅ 获取电话号码信息
- ✅ 获取可用模板
- ✅ 获取 API 密钥信息

### 4. 错误处理
- ✅ 自定义异常类
- ✅ 重试机制
- ✅ 详细的错误信息

## 📋 技术特性

### 依赖管理
- **PHP**: >= 7.4
- **GuzzleHTTP**: ^7.0 (HTTP 客户端)
- **PHPUnit**: ^9.0 (测试框架)
- **PHPStan**: ^1.0 (静态分析)

### 代码质量
- ✅ PSR-4 自动加载
- ✅ 完整的类型提示
- ✅ 单元测试覆盖
- ✅ 静态分析支持
- ✅ 代码覆盖率报告

### 开发工具
- ✅ Composer 脚本
- ✅ PHPUnit 测试套件
- ✅ PHPStan 静态分析
- ✅ 示例代码
- ✅ 详细文档

## 🎯 使用示例

### 基本使用

```php
<?php

use Dialog360\Dialog360Client;
use Dialog360\Message\TextMessage;

// 初始化客户端
$client = new Dialog360Client('your-api-key', 'your-phone-number-id');

// 发送文本消息
$message = new TextMessage('1234567890', 'Hello World!');
$response = $client->sendMessage($message);

if ($response->isSuccess()) {
    echo "消息发送成功！";
} else {
    echo "发送失败: " . $response->getErrorMessage();
}
```

### 媒体消息

```php
use Dialog360\Message\MediaMessage;

// 发送图片
$imageMessage = MediaMessage::image(
    '1234567890',
    'https://example.com/image.jpg',
    'Beautiful image!'
);
$client->sendMessage($imageMessage);

// 发送文档
$documentMessage = MediaMessage::document(
    '1234567890',
    'https://example.com/document.pdf',
    'Important document',
    'document.pdf'
);
$client->sendMessage($documentMessage);
```

### 模板消息

```php
use Dialog360\Message\TemplateMessage;

$templateMessage = new TemplateMessage(
    '1234567890',
    'welcome_message',
    'en_US',
    [
        [
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => 'John']
            ]
        ]
    ]
);
$client->sendMessage($templateMessage);
```

### 交互式消息

```php
use Dialog360\Message\InteractiveMessage;

// 按钮消息
$buttonMessage = InteractiveMessage::button(
    '1234567890',
    'Choose an option:',
    [
        [
            'type' => 'reply',
            'reply' => ['id' => 'yes', 'title' => 'Yes']
        ],
        [
            'type' => 'reply',
            'reply' => ['id' => 'no', 'title' => 'No']
        ]
    ]
);
$client->sendMessage($buttonMessage);
```

## 🔧 安装和配置

### 使用 Composer 安装

```bash
composer require 360-dialog/php-sdk
```

### 环境配置

```bash
# .env 文件
DIALOG360_API_KEY=your-api-key-here
DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here
DIALOG360_BASE_URL=https://waba-api.360dialog.io
```

## 🧪 测试

```bash
# 运行测试
composer test

# 生成覆盖率报告
composer test-coverage

# 静态分析
composer phpstan
```

## 📚 文档

- **README.md**: 主要文档和使用指南
- **INSTALL.md**: 详细的安装和配置说明
- **examples/**: 完整的示例代码
- **tests/**: 单元测试示例

## 🛠️ 开发

### 本地开发

```bash
# 克隆仓库
git clone <repository-url>
cd 360-dialog

# 安装依赖
composer install

# 运行验证
php validate.php

# 运行测试
composer test
```

### 代码质量

- 使用 PHPStan 进行静态分析
- 使用 PHPUnit 进行单元测试
- 遵循 PSR-4 自动加载标准
- 完整的类型提示和文档注释

## 📄 许可证

MIT License - 详见 [LICENSE](LICENSE) 文件

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📞 支持

如有问题，请：
1. 查看文档和示例
2. 运行测试确保环境正常
3. 提交 Issue 描述问题

---

**这个包提供了一个完整、易用且功能丰富的 360 Dialog WhatsApp Business API 的 PHP SDK，适合各种规模的 PHP 项目使用。** 