# 环境变量设置指南

## 概述

在PHP中设置环境变量让`$_ENV`能够获取到，有多种方法。本指南将详细介绍各种设置方法。

## 方法一：使用 .env 文件（推荐）

### 1. 创建 .env 文件

在项目根目录创建 `.env` 文件：

```bash
# 360 Dialog API 配置
DIALOG360_API_KEY=your-api-key-here
DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here
DIALOG360_BASE_URL=https://waba-api.360dialog.io

# 应用环境
APP_ENV=development
APP_DEBUG=true

# 超时设置（秒）
DIALOG360_TIMEOUT=30

# 重试次数
DIALOG360_RETRY_ATTEMPTS=3
```

### 2. 使用 EnvironmentLoader 类

```php
<?php

require_once 'vendor/autoload.php';

use Dialog360\EnvironmentLoader;

// 加载环境变量
EnvironmentLoader::load();

// 获取环境变量
$apiKey = EnvironmentLoader::get('DIALOG360_API_KEY', 'default-value');
$phoneNumberId = EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID');

// 检查环境变量是否存在
if (EnvironmentLoader::has('DIALOG360_API_KEY')) {
    echo "API 密钥已设置";
}

// 设置环境变量
EnvironmentLoader::set('CUSTOM_VAR', 'custom-value');
```

## 方法二：使用 putenv() 函数

### 在代码中设置

```php
<?php

// 设置环境变量
putenv('DIALOG360_API_KEY=your-api-key-here');
putenv('DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here');

// 获取环境变量
$apiKey = $_ENV['DIALOG360_API_KEY'] ?? getenv('DIALOG360_API_KEY');
$phoneNumberId = $_ENV['DIALOG360_PHONE_NUMBER_ID'] ?? getenv('DIALOG360_PHONE_NUMBER_ID');
```

## 方法三：使用 $_ENV 数组直接设置

```php
<?php

// 直接设置 $_ENV 数组
$_ENV['DIALOG360_API_KEY'] = 'your-api-key-here';
$_ENV['DIALOG360_PHONE_NUMBER_ID'] = 'your-phone-number-id-here';

// 获取环境变量
$apiKey = $_ENV['DIALOG360_API_KEY'];
$phoneNumberId = $_ENV['DIALOG360_PHONE_NUMBER_ID'];
```

## 方法四：系统环境变量

### Linux/macOS

```bash
# 临时设置（当前会话有效）
export DIALOG360_API_KEY="your-api-key-here"
export DIALOG360_PHONE_NUMBER_ID="your-phone-number-id-here"

# 永久设置（添加到 ~/.bashrc 或 ~/.zshrc）
echo 'export DIALOG360_API_KEY="your-api-key-here"' >> ~/.bashrc
echo 'export DIALOG360_PHONE_NUMBER_ID="your-phone-number-id-here"' >> ~/.bashrc
source ~/.bashrc
```

### Windows

```cmd
# 临时设置（当前会话有效）
set DIALOG360_API_KEY=your-api-key-here
set DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here

# 永久设置（通过系统设置）
# 右键"此电脑" -> 属性 -> 高级系统设置 -> 环境变量
```

## 方法五：使用 Apache/Nginx 配置

### Apache (.htaccess)

```apache
SetEnv DIALOG360_API_KEY "your-api-key-here"
SetEnv DIALOG360_PHONE_NUMBER_ID "your-phone-number-id-here"
```

### Nginx

```nginx
location / {
    fastcgi_param DIALOG360_API_KEY "your-api-key-here";
    fastcgi_param DIALOG360_PHONE_NUMBER_ID "your-phone-number-id-here";
    # ... 其他配置
}
```

## 方法六：使用 Docker

### Dockerfile

```dockerfile
FROM php:8.2-fpm

# 设置环境变量
ENV DIALOG360_API_KEY=your-api-key-here
ENV DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here

# ... 其他配置
```

### docker-compose.yml

```yaml
version: '3.8'
services:
  app:
    build: .
    environment:
      - DIALOG360_API_KEY=your-api-key-here
      - DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here
    # ... 其他配置
```

## 方法七：使用 PHP 配置

### 在 php.ini 中设置

```ini
; 在 php.ini 文件中添加
variables_order = "EGPCS"
auto_globals_jit = Off
```

### 使用 .user.ini 文件

```ini
; 在项目根目录创建 .user.ini 文件
auto_globals_jit = Off
```

## 最佳实践

### 1. 安全性

```php
<?php

// 不要将敏感信息硬编码在代码中
// ❌ 错误做法
$apiKey = 'your-actual-api-key';

// ✅ 正确做法
$apiKey = EnvironmentLoader::get('DIALOG360_API_KEY');
```

### 2. 默认值

```php
<?php

// 提供默认值
$apiKey = EnvironmentLoader::get('DIALOG360_API_KEY', 'default-key');
$timeout = EnvironmentLoader::get('DIALOG360_TIMEOUT', 30);
```

### 3. 验证环境变量

```php
<?php

// 验证必需的环境变量
$requiredVars = ['DIALOG360_API_KEY', 'DIALOG360_PHONE_NUMBER_ID'];

foreach ($requiredVars as $var) {
    if (!EnvironmentLoader::has($var)) {
        throw new Exception("必需的环境变量 {$var} 未设置");
    }
}
```

### 4. 环境特定配置

```php
<?php

// 根据环境加载不同的配置
$env = EnvironmentLoader::get('APP_ENV', 'production');

if ($env === 'development') {
    EnvironmentLoader::load('.env.development');
} elseif ($env === 'testing') {
    EnvironmentLoader::load('.env.testing');
} else {
    EnvironmentLoader::load('.env.production');
}
```

## 故障排除

### 1. $_ENV 为空

```php
<?php

// 检查 variables_order 设置
echo ini_get('variables_order'); // 应该包含 'E'

// 检查 auto_globals_jit 设置
echo ini_get('auto_globals_jit'); // 应该为 'Off'
```

### 2. 环境变量不生效

```php
<?php

// 调试环境变量
var_dump($_ENV);
var_dump(getenv());

// 检查特定变量
echo "API Key: " . ($_ENV['DIALOG360_API_KEY'] ?? 'not set') . "\n";
echo "Phone ID: " . (getenv('DIALOG360_PHONE_NUMBER_ID') ?: 'not set') . "\n";
```

### 3. 权限问题

```bash
# 检查文件权限
ls -la .env

# 设置正确的权限
chmod 600 .env
```

## 总结

推荐使用 `.env` 文件 + `EnvironmentLoader` 类的方法，因为：

1. **安全性**：敏感信息不暴露在代码中
2. **灵活性**：不同环境可以使用不同的配置
3. **易维护**：配置集中管理
4. **跨平台**：适用于各种部署环境

记住将 `.env` 文件添加到 `.gitignore` 中，避免敏感信息被提交到版本控制系统。 