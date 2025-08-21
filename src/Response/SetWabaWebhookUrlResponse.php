<?php

namespace Dialog360\Response;

class SetWabaWebhookUrlResponse
{
    private array $data;
    private string $message;
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->message = $data['message'] ?? '';
    }

    /**
     * 获取消息
     */
    public function getMessage(): string
    {
        return $this->message;
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
            'message' => $this->message,
        ];
    }
} 