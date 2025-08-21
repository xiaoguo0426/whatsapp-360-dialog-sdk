# 360 Dialog Webhook 设置指南

## 概述

本指南将帮助您设置和配置 360 Dialog webhook，以接收 WhatsApp 消息和状态更新。

## 前置要求

1. 有效的 360 Dialog API 密钥
2. 可公开访问的 HTTPS 服务器
3. 域名和 SSL 证书
4. PHP 7.4+ 环境

## 步骤 1: 准备 Webhook 端点

### 选择 Webhook 文件

我们提供了两个 webhook 接收器：

1. **`webhook_receiver.php`** - 完整的面向对象版本，适合生产环境
2. **`simple_webhook.php`** - 简化版本，适合快速测试和开发

### 部署到服务器

将选择的 webhook 文件上传到您的服务器，确保：

- 文件可以通过 HTTPS 访问
- 服务器支持 PHP
- 文件有适当的执行权限

**示例 URL:**
```
https://yourdomain.com/webhook/simple_webhook.php
```

## 步骤 2: 配置环境变量

### 创建 `.env` 文件

```bash
# 360 Dialog API 配置
DIALOG360_API_KEY=your-actual-api-key
DIALOG360_PHONE_NUMBER_ID=your-phone-number-id
DIALOG360_BASE_URL=https://waba-v2.360dialog.io

# Webhook 验证令牌（自定义设置）
DIALOG360_VERIFY_TOKEN=your-custom-verify-token
```

### 重要说明

- **`DIALOG360_VERIFY_TOKEN`**: 这是您自定义的验证令牌，用于 webhook 验证
- 确保令牌足够复杂且保密
- 不要将真实的 API 密钥提交到版本控制系统

## 步骤 3: 设置 Webhook URL

### 使用 SDK 设置

```php
<?php
require_once 'vendor/autoload.php';

use Dialog360\Dialog360Client;
use Dialog360\EnvironmentLoader;

EnvironmentLoader::load();

$client = new Dialog360Client(
    EnvironmentLoader::get('DIALOG360_API_KEY'),
    EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID')
);

// 设置 webhook URL
$webhookUrl = 'https://yourdomain.com/webhook/simple_webhook.php';
$response = $client->setWebhookUrl($webhookUrl);

if ($response->isSuccess()) {
    echo "Webhook URL 设置成功！\n";
} else {
    echo "设置失败: " . $response->getErrorMessage() . "\n";
}
```

### 手动设置

您也可以在 360 Dialog Hub 中手动设置 webhook URL：

1. 登录 360 Dialog Hub
2. 进入您的 WhatsApp Business API 账户
3. 找到 Webhook 设置部分
4. 输入您的 webhook URL
5. 设置验证令牌（与 `DIALOG360_VERIFY_TOKEN` 一致）

## 步骤 4: 测试 Webhook

### 验证 Webhook 端点

访问您的 webhook URL 进行验证：

```
GET https://yourdomain.com/webhook/simple_webhook.php?hub_mode=subscribe&hub_verify_token=your-verify-token&hub_challenge=test-challenge
```

如果配置正确，您应该看到 `test-challenge` 响应。

### 发送测试消息

1. 向您的 WhatsApp 号码发送消息
2. 检查 webhook 日志文件
3. 验证自动回复是否正常工作

## 步骤 5: 监控和调试

### 日志文件

Webhook 接收器会创建以下日志文件：

- **`webhook.log`** - 所有 webhook 活动的日志
- **`webhook_errors.log`** - 错误日志
- **`webhook_info.log`** - 信息日志（完整版本）

### 实时监控

```bash
# 实时查看 webhook 日志
tail -f webhook.log

# 查看错误日志
tail -f webhook_errors.log
```

### 常见问题排查

#### 1. Webhook 验证失败

**症状**: 收到 "Verification failed" 错误

**解决方案**:
- 检查 `DIALOG360_VERIFY_TOKEN` 是否与 Hub 中设置的一致
- 确保 webhook URL 可以通过 HTTPS 访问
- 验证服务器时间是否正确

#### 2. 无法接收消息

**症状**: 没有收到 webhook 数据

**解决方案**:
- 检查 webhook URL 是否正确设置
- 验证 API 密钥是否有效
- 确保服务器可以接收 POST 请求
- 检查防火墙设置

#### 3. 自动回复失败

**症状**: 收到消息但没有自动回复

**解决方案**:
- 检查 API 密钥和电话号码 ID 配置
- 验证网络连接和 API 端点
- 查看错误日志中的具体错误信息

## 步骤 6: 生产环境配置

### 安全考虑

1. **HTTPS 必需**: 确保使用有效的 SSL 证书
2. **验证令牌**: 使用强随机字符串作为验证令牌
3. **访问控制**: 限制对 webhook 端点的访问
4. **日志安全**: 不要在生产环境中记录敏感信息

### 性能优化

1. **异步处理**: 对于耗时的操作，使用队列系统
2. **数据库连接**: 使用连接池管理数据库连接
3. **缓存**: 缓存频繁访问的数据
4. **负载均衡**: 在高流量情况下使用负载均衡器

### 监控和告警

1. **健康检查**: 定期检查 webhook 端点状态
2. **错误告警**: 设置错误率告警
3. **性能监控**: 监控响应时间和吞吐量
4. **备份策略**: 实现 webhook 端点的备份

## 示例配置

### Nginx 配置

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    location /webhook/ {
        try_files $uri $uri/ /webhook/simple_webhook.php?$query_string;
        
        # 限制请求大小
        client_max_body_size 1M;
        
        # 设置超时
        proxy_read_timeout 30s;
        proxy_connect_timeout 30s;
        proxy_send_timeout 30s;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Apache 配置

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/html
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/html/webhook>
        AllowOverride All
        Require all granted
        
        # 限制请求方法
        <LimitExcept GET POST>
            Deny from all
        </LimitExcept>
    </Directory>
    
    # PHP 处理
    <FilesMatch "\.php$">
        SetHandler application/x-httpd-php
    </FilesMatch>
</VirtualHost>
```

## 测试工具

### 使用 cURL 测试

```bash
# 测试 GET 请求（验证）
curl "https://yourdomain.com/webhook/simple_webhook.php?hub_mode=subscribe&hub_verify_token=your-token&hub_challenge=test123"

# 测试 POST 请求（模拟消息）
curl -X POST "https://yourdomain.com/webhook/simple_webhook.php" \
  -H "Content-Type: application/json" \
  -d '{
    "entry": [{
      "changes": [{
        "value": {
          "messaging_product": "whatsapp",
          "messages": [{
            "from": "1234567890",
            "id": "test-message-id",
            "timestamp": "1234567890",
            "type": "text",
            "text": {
              "body": "Hello, this is a test message"
            }
          }]
        }
      }]
    }]
  }'
```

### 使用 Postman 测试

1. 创建新的请求
2. 设置 URL 为您的 webhook 端点
3. 选择 POST 方法
4. 在 Body 中添加 JSON 数据
5. 发送请求并检查响应

## 故障排除

### 检查清单

- [ ] Webhook URL 可以通过 HTTPS 访问
- [ ] 验证令牌配置正确
- [ ] API 密钥有效且未过期
- [ ] 服务器可以接收 POST 请求
- [ ] PHP 环境配置正确
- [ ] 日志文件可写
- [ ] 网络连接正常

### 获取帮助

如果遇到问题：

1. 检查 webhook 日志文件
2. 验证 360 Dialog Hub 中的配置
3. 测试网络连接和服务器状态
4. 查看 360 Dialog 官方文档
5. 联系技术支持

## 总结

设置 webhook 是接收 WhatsApp 消息的关键步骤。通过遵循本指南，您应该能够：

1. 成功配置 webhook 端点
2. 接收和处理 WhatsApp 消息
3. 实现自动回复功能
4. 监控和调试 webhook 活动
5. 在生产环境中安全运行

记住，webhook 是实时通信的基础，确保其稳定性和安全性对于提供良好的用户体验至关重要。 