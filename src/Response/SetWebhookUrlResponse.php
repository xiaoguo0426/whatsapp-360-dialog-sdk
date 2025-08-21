<?php

namespace Dialog360\Response;

class SetWebhookUrlResponse
{
    private array $data;
    private string $messageId;
    private string $status;
    private ?string $timestamp;

    public function __construct(array $data)
    {
        $this->data = $data;
        // Cloud API 不提供 /v1/messages/{id} 查询；此响应类保留以向后兼容
        $this->messageId = $data['id'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->timestamp = $data['timestamp'] ?? null;
    }

    /**
     * 获取消息ID
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * 获取消息状态
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 获取时间戳
     */
    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    /**
     * 检查是否为已发送状态
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * 检查是否为已送达状态
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * 检查是否为已读状态
     */
    public function isRead(): bool
    {
        return $this->status === 'read';
    }

    /**
     * 检查是否为失败状态
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
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
            'message_id' => $this->messageId,
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'data' => $this->data
        ];
    }
} 