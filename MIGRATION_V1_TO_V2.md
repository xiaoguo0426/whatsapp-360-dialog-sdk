# 360 Dialog PHP SDK: V1 到 V2 迁移指南

## 概述

本指南帮助您将现有的 360 Dialog PHP SDK 从 V1 (On-Premise API) 迁移到 V2 (Cloud API)。

## 主要变化

### 1. 基础 URL 变更

**V1 (On-Premise):**
```
https://waba.360dialog.io
```

**V2 (Cloud API):**
```
https://waba-v2.360dialog.io
```

### 2. 认证方式变更

**V1:**
```php
'Authorization' => 'Bearer ' . $apiKey
```

**V2:**
```php
'D360-API-KEY' => $apiKey
```

### 3. 端点路径变更

| 功能 | V1 端点 | V2 端点 |
|------|----------|----------|
| 发送消息 | `/v1/messages` | `/messages` |
| 媒体信息 | `/v1/media/{id}` | `/{id}` |
| 健康检查 | `/v1/health` | `/health_status` |

### 4. 响应数据结构变更

#### 消息发送响应

**V1:**
```json
{
  "messages": [
    {
      "id": "message-id"
    }
  ],
  "meta": {
    "api_status": "stable",
    "version": "2.35.4"
  }
}
```

**V2:**
```json
{
  "messaging_product": "whatsapp",
  "contacts": [
    {
      "input": "PHONE_NUMBER",
      "wa_id": "WHATSAPP_ID"
    }
  ],
  "messages": [
    {
      "id": "wamid.ID"
    }
  ]
}
```

#### 媒体响应

**V1:**
```json
{
  "id": "media-id",
  "url": "https://example.com/media.jpg",
  "mime_type": "image/jpeg",
  "sha256": "hash",
  "file_size": 1024
}
```

**V2:**
```json
{
  "messaging_product": "whatsapp",
  "url": "https://lookaside.fbsbx.com/whatsapp_business/attachments/...",
  "mime_type": "image/jpeg",
  "sha256": "hash",
  "file_size": "SIZE",
  "id": "ID"
}
```

### 5. 新增功能

#### 健康状态检查
```php
// V2 新增：获取消息发送健康状态
$health = $client->getHealthStatus();
echo $health['health_status']['can_send_message']; // AVAILABLE, LIMITED, BLOCKED
```

### 6. 不再支持的功能

以下 V1 功能在 V2 中不再通过 Messaging API 提供：

- `getMessageStatus()` - 消息状态查询
- `getPhoneNumberInfo()` - 电话号码信息
- `getTemplates()` - 模板列表
- `getApiKeyInfo()` - API 密钥信息

## 迁移步骤

### 步骤 1: 更新依赖

确保使用最新版本的 SDK：
```bash
composer update onetech/whatsapp-360-dialog-sdk
```

### 步骤 2: 更新配置

**环境变量:**
```bash
# 旧配置
DIALOG360_BASE_URL=https://waba.360dialog.io

# 新配置
DIALOG360_BASE_URL=https://waba-v2.360dialog.io
```

**代码配置:**
```php
// 旧代码
$client = new Dialog360Client(
    $apiKey,
    $phoneNumberId,
    'https://waba.360dialog.io'
);

// 新代码
$client = new Dialog360Client(
    $apiKey,
    $phoneNumberId,
    'https://waba-v2.360dialog.io'
);
```

### 步骤 3: 更新认证

**旧代码:**
```php
// 手动设置 Authorization 头（不再需要）
$headers = [
    'Authorization' => 'Bearer ' . $apiKey
];
```

**新代码:**
```php
// SDK 自动设置 D360-API-KEY 头
$client = new Dialog360Client($apiKey, $phoneNumberId);
```

### 步骤 4: 更新端点调用

**旧代码:**
```php
// 消息状态查询
$status = $client->getMessageStatus('message-id');
echo $status->getStatus();
```

**新代码:**
```php
// 健康状态检查
$health = $client->getHealthStatus();
echo $health['health_status']['can_send_message'];
```

### 步骤 5: 更新响应处理

**旧代码:**
```php
$response = $client->sendMessage($message);
if ($response->isSuccess()) {
    $messageId = $response->getMessageId(); // 格式: "message-id"
}
```

**新代码:**
```php
$response = $client->sendMessage($message);
if ($response->isSuccess()) {
    $messageId = $response->getMessageId(); // 格式: "wamid.ID"
}
```

## 代码示例对比

### 发送文本消息

**V1:**
```php
$client = new Dialog360Client($apiKey, $phoneNumberId, 'https://waba.360dialog.io');
$message = new TextMessage('1234567890', 'Hello World!');
$response = $client->sendMessage($message);

if ($response->isSuccess()) {
    echo "消息ID: " . $response->getMessageId();
    $status = $client->getMessageStatus($response->getMessageId());
    echo "状态: " . $status->getStatus();
}
```

**V2:**
```php
$client = new Dialog360Client($apiKey, $phoneNumberId, 'https://waba-v2.360dialog.io');
$message = new TextMessage('1234567890', 'Hello World!');
$response = $client->sendMessage($message);

if ($response->isSuccess()) {
    echo "消息ID: " . $response->getMessageId();
    $health = $client->getHealthStatus();
    echo "健康状态: " . $health['health_status']['can_send_message'];
}
```

### 媒体处理

**V1:**
```php
$media = $client->getMediaInfo('media-id');
$fileSize = $media->getFileSize(); // 整数
```

**V2:**
```php
$media = $client->getMediaInfo('media-id');
$fileSize = $media->getFileSize(); // 自动转换为整数
$url = $media->getUrl(); // lookaside.fbsbx.com 格式
```

## 测试迁移

### 1. 运行测试套件
```bash
composer test
```

### 2. 验证配置
```php
$config = $client->getConfig();
echo $config['baseUrl']; // 应该显示 waba-v2.360dialog.io
```

### 3. 测试健康状态
```php
try {
    $health = $client->getHealthStatus();
    echo "健康状态: " . $health['health_status']['can_send_message'];
} catch (Exception $e) {
    echo "健康检查失败: " . $e->getMessage();
}
```

## 常见问题

### Q: 为什么消息状态查询不可用？
A: Cloud API 不提供消息状态查询端点。建议使用 webhook 接收状态更新。

### Q: 如何获取模板列表？
A: 使用 Meta Graph API 或在 360dialog Hub 中查看。

### Q: 媒体下载 URL 格式变了？
A: Cloud API 使用 Meta 的 lookaside 服务，需要特殊处理。

### Q: 认证失败怎么办？
A: 确保使用最新的 API 密钥，旧密钥在 Cloud API 中无效。

## 向后兼容性

SDK 保持了大部分公共 API 的兼容性，主要变化在内部实现：

- ✅ `sendMessage()` - 完全兼容
- ✅ `getMediaInfo()` - 完全兼容  
- ✅ `downloadMedia()` - 完全兼容
- ❌ `getMessageStatus()` - 已移除
- ❌ `getPhoneNumberInfo()` - 已移除
- ❌ `getTemplates()` - 已移除
- ❌ `getApiKeyInfo()` - 已移除

## 支持

如果在迁移过程中遇到问题：

1. 查看 [README.md](README.md) 了解基本用法
2. 运行测试验证环境配置
3. 检查 360dialog 官方文档
4. 提交 Issue 或联系支持团队

## 总结

迁移到 V2 的主要好处：

- 🚀 更好的性能和可靠性
- 🔒 增强的安全性
- 📱 支持更多 WhatsApp 功能
- 🌐 完全托管的云服务
- 📈 更好的扩展性

虽然有一些破坏性变更，但 SDK 保持了核心功能的兼容性，迁移过程相对简单。 