# 360 Dialog PHP SDK 包摘要

## 概述

这是一个用于360 Dialog WhatsApp Business API的PHP SDK包，支持Cloud API v2。

## 主要功能

- 发送各种类型的WhatsApp消息
- 媒体文件上传和管理
- 错误处理和重试机制
- 完整的类型提示和文档

## 安装

```bash
composer require onetech/whatsapp-360-dialog-sdk
```

## 快速开始

```php
use Dialog360\Dialog360Client;
use Dialog360\Message\TextMessage;

$client = new Dialog360Client($apiKey, $phoneNumberId);
$message = new TextMessage('1234567890', 'Hello World!');
$response = $client->sendMessage($message);
```

## 配置

```bash
DIALOG360_API_KEY=your-api-key
DIALOG360_PHONE_NUMBER_ID=your-phone-number-id
DIALOG360_BASE_URL=https://waba-v2.360dialog.io
```

## 文件结构

```
src/
├── Dialog360Client.php          # 主客户端类
├── EnvironmentLoader.php        # 环境变量加载器
├── Exception/                   # 异常类
├── Message/                     # 消息类型类
└── Response/                    # 响应处理类
```

## 测试

```bash
composer test
```

## 许可证

MIT License 