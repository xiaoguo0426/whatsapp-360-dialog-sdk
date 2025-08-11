<?php

namespace Dialog360;

class EnvironmentLoader
{
    /**
     * 加载环境变量
     */
    public static function load(string $path = null): void
    {
        $path = $path ?: dirname(__DIR__) . '/.env';
        
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // 跳过注释
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // 解析键值对
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 移除引号
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // 设置环境变量
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * 获取环境变量
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * 设置环境变量
     */
    public static function set(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }

    /**
     * 检查环境变量是否存在
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || getenv($key) !== false;
    }
} 