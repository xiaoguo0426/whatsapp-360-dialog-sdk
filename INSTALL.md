# 360 Dialog PHP SDK 安装指南

## 系统要求

- PHP 7.4 或更高版本
- Composer
- ext-json 扩展
- ext-curl 扩展

## 安装步骤

### 1. 使用 Composer 安装

```bash
composer require 360-dialog/php-sdk
```

### 2. 手动安装

如果你想要从源代码安装：

```bash
# 克隆仓库
git clone https://github.com/your-username/360-dialog-php-sdk.git
cd 360-dialog-php-sdk

# 安装依赖
composer install
```

## 配置

### 环境变量

创建 `.env` 文件并添加以下配置：

```bash
DIALOG360_API_KEY=your-api-key-here
DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here
DIALOG360_BASE_URL=https://waba-v2.360dialog.io
```

### 获取 API 密钥

1. 登录到 [360 Dialog 控制台](https://app.360dialog.io/)
2. 导航到 API 设置
3. 复制你的 API 密钥
4. 复制你的电话号码 ID

## 快速开始

```php
<?php

require_once 'vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\Message\TextMessage;

// 初始化客户端
$client = new Dialog360Client(
    $_ENV['DIALOG360_API_KEY'],
    $_ENV['DIALOG360_PHONE_NUMBER_ID']
);

// 发送文本消息
$message = new TextMessage('1234567890', 'Hello from 360 Dialog!');
$response = $client->sendMessage($message);

if ($response->isSuccess()) {
    echo "消息发送成功！";
} else {
    echo "发送失败: " . $response->getErrorMessage();
}
```

## 测试

运行测试套件：

```bash
# 运行所有测试
composer test

# 运行测试并生成覆盖率报告
composer test-coverage

# 运行静态分析
composer phpstan
```

## 示例

查看 `examples/` 目录中的示例文件：

- `send_text_message.php` - 发送文本消息
- `send_media_message.php` - 发送媒体消息
- `send_template_message.php` - 发送模板消息
- `send_interactive_message.php` - 发送交互式消息

## 故障排除

### 常见问题

1. **认证错误**
   - 检查 API 密钥是否正确
   - 确保 API 密钥有足够的权限

2. **网络错误**
   - 检查网络连接
   - 确保防火墙允许 HTTPS 连接

3. **电话号码错误**
   - 确保电话号码格式正确（包含国家代码）
   - 检查电话号码是否已在 WhatsApp Business 中注册

### 调试

启用详细日志记录：

```php
$client = new Dialog360Client(
    $apiKey,
    $phoneNumberId,
    'https://waba-v2.360dialog.io',
    30, // timeout
    3   // retry attempts
);

// 获取客户端配置进行调试
$config = $client->getConfig();
var_dump($config);
```

## 支持

如果你遇到问题，请：

1. 检查 [README.md](README.md) 中的文档
2. 查看示例代码
3. 运行测试确保环境正常
4. 提交 Issue 或联系支持团队 