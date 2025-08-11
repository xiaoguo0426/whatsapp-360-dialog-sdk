<?php

/**
 * 360 Dialog PHP SDK 验证脚本
 * 用于检查包的结构和基本功能
 */

echo "🔍 360 Dialog PHP SDK 验证脚本\n";
echo "================================\n\n";

// 检查 PHP 版本
echo "1. 检查 PHP 版本...\n";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "✅ PHP 版本: " . PHP_VERSION . " (符合要求)\n";
} else {
    echo "❌ PHP 版本: " . PHP_VERSION . " (需要 7.4 或更高版本)\n";
    exit(1);
}

// 检查必要的扩展
echo "\n2. 检查必要的扩展...\n";
$requiredExtensions = ['json', 'curl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ {$ext} 扩展已安装\n";
    } else {
        echo "❌ {$ext} 扩展未安装\n";
        exit(1);
    }
}

// 检查文件结构
echo "\n3. 检查文件结构...\n";
$requiredFiles = [
    'composer.json',
    'README.md',
    'LICENSE',
    'src/Dialog360Client.php',
    'src/Message/MessageInterface.php',
    'src/Message/TextMessage.php',
    'src/Message/MediaMessage.php',
    'src/Message/TemplateMessage.php',
    'src/Message/InteractiveMessage.php',
    'src/Response/MessageResponse.php',
    'src/Response/MessageStatusResponse.php',
    'src/Response/MediaResponse.php',
    'src/Exception/Dialog360Exception.php',
    'tests/Dialog360ClientTest.php',
    'examples/send_text_message.php',
    'examples/send_media_message.php',
    'examples/send_template_message.php',
    'examples/send_interactive_message.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file}\n";
    } else {
        echo "❌ {$file} (缺失)\n";
        exit(1);
    }
}

// 检查 Composer 配置
echo "\n4. 检查 Composer 配置...\n";
if (file_exists('composer.json')) {
    $composerJson = json_decode(file_get_contents('composer.json'), true);
    
    if (isset($composerJson['name']) && $composerJson['name'] === '360-dialog/php-sdk') {
        echo "✅ 包名称正确\n";
    } else {
        echo "❌ 包名称不正确\n";
    }
    
    if (isset($composerJson['require']['php']) && version_compare($composerJson['require']['php'], '>=7.4', '>=')) {
        echo "✅ PHP 版本要求正确\n";
    } else {
        echo "❌ PHP 版本要求不正确\n";
    }
    
    if (isset($composerJson['autoload']['psr-4']['Dialog360\\'])) {
        echo "✅ PSR-4 自动加载配置正确\n";
    } else {
        echo "❌ PSR-4 自动加载配置不正确\n";
    }
} else {
    echo "❌ composer.json 文件不存在\n";
    exit(1);
}

// 检查命名空间
echo "\n5. 检查命名空间...\n";
$namespaceFiles = [
    'src/Dialog360Client.php' => 'Dialog360',
    'src/Message/MessageInterface.php' => 'Dialog360\\Message',
    'src/Message/TextMessage.php' => 'Dialog360\\Message',
    'src/Message/MediaMessage.php' => 'Dialog360\\Message',
    'src/Message/TemplateMessage.php' => 'Dialog360\\Message',
    'src/Message/InteractiveMessage.php' => 'Dialog360\\Message',
    'src/Response/MessageResponse.php' => 'Dialog360\\Response',
    'src/Response/MessageStatusResponse.php' => 'Dialog360\\Response',
    'src/Response/MediaResponse.php' => 'Dialog360\\Response',
    'src/Exception/Dialog360Exception.php' => 'Dialog360\\Exception'
];

foreach ($namespaceFiles as $file => $expectedNamespace) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match('/namespace\s+' . preg_quote($expectedNamespace, '/') . '/', $content)) {
            echo "✅ {$file} 命名空间正确\n";
        } else {
            echo "❌ {$file} 命名空间不正确\n";
        }
    }
}

// 检查类和方法
echo "\n6. 检查类和方法...\n";
$classes = [
    'Dialog360\\Dialog360Client' => ['sendMessage', 'getMessageStatus', 'getMediaInfo'],
    'Dialog360\\Message\\TextMessage' => ['getTo', 'getType', 'toArray'],
    'Dialog360\\Message\\MediaMessage' => ['getTo', 'getType', 'toArray', 'image', 'audio', 'video', 'document'],
    'Dialog360\\Message\\TemplateMessage' => ['getTo', 'getType', 'toArray'],
    'Dialog360\\Message\\InteractiveMessage' => ['getTo', 'getType', 'toArray', 'button', 'list'],
    'Dialog360\\Response\\MessageResponse' => ['isSuccess', 'getMessageId', 'getErrorCode', 'getErrorMessage'],
    'Dialog360\\Response\\MessageStatusResponse' => ['getMessageId', 'getStatus', 'isSent', 'isDelivered', 'isRead', 'isFailed'],
    'Dialog360\\Response\\MediaResponse' => ['getMediaId', 'getUrl', 'getMimeType', 'isImage', 'isAudio', 'isVideo', 'isDocument'],
    'Dialog360\\Exception\\Dialog360Exception' => ['getErrorCode', 'getErrorData', 'apiError', 'networkError', 'authenticationError']
];

// 注意：这里只是检查文件是否存在，实际的类检查需要 autoloader
foreach ($classes as $class => $methods) {
    $classFile = str_replace('\\', '/', $class) . '.php';
    $classFile = 'src/' . $classFile;
    
    if (file_exists($classFile)) {
        echo "✅ {$class} 类文件存在\n";
    } else {
        echo "❌ {$class} 类文件不存在\n";
    }
}

echo "\n7. 检查测试配置...\n";
if (file_exists('phpunit.xml')) {
    echo "✅ PHPUnit 配置文件存在\n";
} else {
    echo "❌ PHPUnit 配置文件不存在\n";
}

if (file_exists('phpstan.neon')) {
    echo "✅ PHPStan 配置文件存在\n";
} else {
    echo "❌ PHPStan 配置文件不存在\n";
}

echo "\n8. 检查文档...\n";
if (file_exists('README.md')) {
    $readme = file_get_contents('README.md');
    if (strpos($readme, '360 Dialog PHP SDK') !== false) {
        echo "✅ README.md 内容正确\n";
    } else {
        echo "❌ README.md 内容不正确\n";
    }
} else {
    echo "❌ README.md 文件不存在\n";
}

if (file_exists('INSTALL.md')) {
    echo "✅ INSTALL.md 文件存在\n";
} else {
    echo "❌ INSTALL.md 文件不存在\n";
}

echo "\n9. 检查许可证...\n";
if (file_exists('LICENSE')) {
    $license = file_get_contents('LICENSE');
    if (strpos($license, 'MIT License') !== false) {
        echo "✅ MIT 许可证文件正确\n";
    } else {
        echo "❌ 许可证文件不正确\n";
    }
} else {
    echo "❌ LICENSE 文件不存在\n";
}

echo "\n10. 检查 .gitignore...\n";
if (file_exists('.gitignore')) {
    $gitignore = file_get_contents('.gitignore');
    if (strpos($gitignore, 'vendor/') !== false && strpos($gitignore, 'composer.lock') !== false) {
        echo "✅ .gitignore 配置正确\n";
    } else {
        echo "❌ .gitignore 配置不正确\n";
    }
} else {
    echo "❌ .gitignore 文件不存在\n";
}

echo "\n🎉 验证完成！\n";
echo "如果所有检查都通过，你的 360 Dialog PHP SDK 包结构是正确的。\n";
echo "\n下一步：\n";
echo "1. 运行 'composer install' 安装依赖\n";
echo "2. 运行 'composer test' 执行测试\n";
echo "3. 运行 'composer phpstan' 进行静态分析\n";
echo "4. 查看 examples/ 目录中的示例代码\n"; 