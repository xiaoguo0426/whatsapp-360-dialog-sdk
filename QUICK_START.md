# 快速开始指南

## 1. 安装

```bash
composer require onetech/whatsapp-360-dialog-sdk
```

## 2. 设置环境变量

### 方法一：创建 .env 文件（推荐）

在项目根目录创建 `.env` 文件：

```bash
# 360 Dialog API 配置
DIALOG360_API_KEY=your-api-key-here
DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here
DIALOG360_BASE_URL=https://waba-v2.360dialog.io
```

### 方法二：使用 putenv()

```php
<?php
putenv('DIALOG360_API_KEY=your-api-key-here');
putenv('DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here');
```

### 方法三：直接设置 $_ENV

```php
<?php
$_ENV['DIALOG360_API_KEY'] = 'your-api-key-here';
$_ENV['DIALOG360_PHONE_NUMBER_ID'] = 'your-phone-number-id-here';
```

## 3. 基本使用

```php
<?php

require_once 'vendor/autoload.php';

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

if ($response->isSuccess()) {
    echo "消息发送成功！";
} else {
    echo "发送失败: " . $response->getErrorMessage();
}
```

## 4. 测试环境变量

运行测试脚本检查环境变量是否正确设置：

```bash
php test_environment.php
```

## 5. 常见问题

### $_ENV 为空怎么办？

1. **检查 PHP 配置**：
   ```php
   echo ini_get('variables_order'); // 应该包含 'E'
   echo ini_get('auto_globals_jit'); // 应该为 'Off'
   ```

2. **使用 EnvironmentLoader**：
   ```php
   use Dialog360\EnvironmentLoader;
   EnvironmentLoader::load();
   $apiKey = EnvironmentLoader::get('DIALOG360_API_KEY');
   ```

3. **使用 getenv()**：
   ```php
   $apiKey = getenv('DIALOG360_API_KEY');
   ```

### 环境变量不生效？

1. 确保 `.env` 文件存在且格式正确
2. 检查文件权限
3. 重启 Web 服务器
4. 清除 PHP 缓存

## 6. 完整示例

查看 `examples/` 目录中的完整示例：

- `send_text_message.php` - 发送文本消息
- `send_media_message.php` - 发送媒体消息
- `send_template_message.php` - 发送模板消息
- `send_interactive_message.php` - 发送交互式消息

## 7. 下一步

- 阅读 [README.md](README.md) 了解完整功能
- 查看 [ENVIRONMENT_SETUP.md](ENVIRONMENT_SETUP.md) 了解详细的环境变量设置方法
- 运行 `composer test` 执行测试 