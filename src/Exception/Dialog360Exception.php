<?php

namespace Dialog360\Exception;

use Exception;

class Dialog360Exception extends Exception
{
    private ?string $errorCode;
    private ?array $errorData;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        ?string $errorCode = null,
        ?array $errorData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }

    /**
     * 获取错误代码
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * 获取错误数据
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * 创建API错误异常
     */
    public static function apiError(string $message, string $errorCode, array $errorData = []): self
    {
        return new self($message, 0, null, $errorCode, $errorData);
    }

    /**
     * 创建网络错误异常
     */
    public static function networkError(string $message, Exception $previous = null): self
    {
        return new self($message, 0, $previous, 'NETWORK_ERROR');
    }

    /**
     * 创建认证错误异常
     */
    public static function authenticationError(string $message = 'Invalid API key'): self
    {
        return new self($message, 401, null, 'AUTHENTICATION_ERROR');
    }

    /**
     * 创建参数错误异常
     */
    public static function validationError(string $message, array $errors = []): self
    {
        return new self($message, 400, null, 'VALIDATION_ERROR', $errors);
    }

    /**
     * 创建速率限制错误异常
     */
    public static function rateLimitError(string $message = 'Rate limit exceeded'): self
    {
        return new self($message, 429, null, 'RATE_LIMIT_ERROR');
    }
} 