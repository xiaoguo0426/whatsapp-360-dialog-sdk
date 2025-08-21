<?php

namespace Dialog360\Response;

class MessageResponse
{
    private array $data;
    private ?string $messageId;
    private bool $success;
    private ?string $errorCode;
    private ?string $errorMessage;

    public function __construct(array $data)
    {
        $this->data = $data;
        // Cloud API v2 响应结构
        $this->messageId = $data['messages'][0]['id'] ?? null;
        $this->success = isset($data['messages'][0]['id']);
        
        if (!$this->success) {
            // Cloud API 错误响应结构
            $this->errorCode = $data['errors'][0]['code'] ?? null;
            $this->errorMessage = $data['errors'][0]['message'] ?? null;
        }
    }

    /**
     * 检查是否成功
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * 获取消息ID
     */
    public function getMessageId(): ?string
    {
        // Cloud API 返回 wamid.ID 格式
        return $this->messageId;
    }

    /**
     * 获取错误代码
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * 获取错误消息
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * 获取原始响应数据
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message_id' => $this->messageId,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'data' => $this->data
        ];
    }
} 