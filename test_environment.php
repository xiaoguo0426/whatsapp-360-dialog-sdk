<?php

/**
 * 环境变量测试脚本
 * 用于测试各种环境变量设置方法
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dialog360\EnvironmentLoader;

echo "🔍 环境变量测试脚本\n";
echo "==================\n\n";

// 1. 检查 PHP 配置
echo "1. 检查 PHP 配置...\n";
echo "variables_order: " . ini_get('variables_order') . "\n";
echo "auto_globals_jit: " . ini_get('auto_globals_jit') . "\n";
echo "register_argc_argv: " . ini_get('register_argc_argv') . "\n\n";

// 2. 检查当前环境变量
echo "2. 当前环境变量状态...\n";
echo "getenv() 结果:\n";
$envVars = getenv();
if (empty($envVars)) {
    echo "  - getenv() 返回空数组\n";
} else {
    foreach ($envVars as $key => $value) {
        if (strpos($key, 'DIALOG360') !== false || strpos($key, 'APP_') !== false) {
            echo "  - {$key}: {$value}\n";
        }
    }
}

echo "\n\$_ENV 结果:\n";
if (empty($_ENV)) {
    echo "  - \$_ENV 数组为空\n";
} else {
    foreach ($_ENV as $key => $value) {
        if (strpos($key, 'DIALOG360') !== false || strpos($key, 'APP_') !== false) {
            echo "  - {$key}: {$value}\n";
        }
    }
}

// 3. 测试 EnvironmentLoader
echo "\n3. 测试 EnvironmentLoader...\n";

// 检查 .env 文件是否存在
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "✅ .env 文件存在\n";
    
    // 加载环境变量
    EnvironmentLoader::load();
    
    // 测试获取环境变量
    $apiKey = EnvironmentLoader::get('DIALOG360_API_KEY');
    $phoneId = EnvironmentLoader::get('DIALOG360_PHONE_NUMBER_ID');
    $baseUrl = EnvironmentLoader::get('DIALOG360_BASE_URL');
    
    echo "API Key: " . ($apiKey ?: 'not set') . "\n";
    echo "Phone ID: " . ($phoneId ?: 'not set') . "\n";
    echo "Base URL: " . ($baseUrl ?: 'not set') . "\n";
    
    // 测试设置环境变量
    EnvironmentLoader::set('TEST_VAR', 'test-value');
    echo "Test Var: " . EnvironmentLoader::get('TEST_VAR') . "\n";
    
    // 测试检查环境变量
    if (EnvironmentLoader::has('DIALOG360_API_KEY')) {
        echo "✅ DIALOG360_API_KEY 已设置\n";
    } else {
        echo "❌ DIALOG360_API_KEY 未设置\n";
    }
    
} else {
    echo "❌ .env 文件不存在\n";
    echo "请创建 .env 文件并添加以下内容：\n";
    echo "DIALOG360_API_KEY=your-api-key-here\n";
    echo "DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here\n";
    echo "DIALOG360_BASE_URL=https://waba-api.360dialog.io\n";
}

// 4. 测试 putenv() 方法
echo "\n4. 测试 putenv() 方法...\n";
putenv('PUTENV_TEST=putenv-value');
echo "putenv 设置的值: " . getenv('PUTENV_TEST') . "\n";

// 5. 测试 $_ENV 直接设置
echo "\n5. 测试 \$_ENV 直接设置...\n";
$_ENV['ENV_TEST'] = 'env-value';
echo "\$_ENV 设置的值: " . ($_ENV['ENV_TEST'] ?? 'not set') . "\n";

// 6. 测试命令行参数
echo "\n6. 测试命令行参数...\n";
if (isset($argv)) {
    echo "命令行参数数量: " . count($argv) . "\n";
    foreach ($argv as $i => $arg) {
        echo "  argv[{$i}]: {$arg}\n";
    }
} else {
    echo "没有命令行参数\n";
}

// 7. 测试系统环境变量
echo "\n7. 测试系统环境变量...\n";
$systemVars = ['PATH', 'HOME', 'USER', 'SHELL'];
foreach ($systemVars as $var) {
    $value = getenv($var);
    if ($value) {
        echo "{$var}: " . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . "\n";
    } else {
        echo "{$var}: not set\n";
    }
}

// 8. 提供设置建议
echo "\n8. 设置建议...\n";
echo "要让 \$_ENV 能够获取到环境变量，请尝试以下方法：\n\n";

echo "方法一：创建 .env 文件\n";
echo "在项目根目录创建 .env 文件：\n";
echo "DIALOG360_API_KEY=your-api-key-here\n";
echo "DIALOG360_PHONE_NUMBER_ID=your-phone-number-id-here\n\n";

echo "方法二：使用 putenv()\n";
echo "putenv('DIALOG360_API_KEY=your-api-key-here');\n\n";

echo "方法三：直接设置 \$_ENV\n";
echo "\$_ENV['DIALOG360_API_KEY'] = 'your-api-key-here';\n\n";

echo "方法四：系统环境变量\n";
echo "export DIALOG360_API_KEY='your-api-key-here'\n\n";

echo "方法五：PHP 配置\n";
echo "在 php.ini 中设置：variables_order = 'EGPCS'\n";
echo "在 .user.ini 中设置：auto_globals_jit = Off\n\n";

echo "🎉 测试完成！\n"; 