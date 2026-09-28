<?php

namespace Dialog360\Response;

/**
 * POST /marketing_messages 响应（发送营销消息）。
 *
 * 成功（200）响应体形如：
 * {
 *   "messaging_product": "whatsapp",
 *   "contacts": [{"input": "...", "wa_id": "...", "user_id": "..."}],
 *   "messages": [{"id": "...", "message_status": "..."}]
 * }
 *
 * 4xx 时 API 返回 {"error": "<string>"}，SDK 不抛异常而是包装为
 * isSuccess=false 的响应对象（与 messages()->send() 行为一致）。
 */
class MarketingMessageResponse
{
    private array $data;

    private string $messagingProduct;

    private array $contacts;

    private array $messages;

    private string $errorMessage;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->messagingProduct = (string) ($data['messaging_product'] ?? '');
        $this->contacts = is_array($data['contacts'] ?? null) ? $data['contacts'] : [];
        $this->messages = is_array($data['messages'] ?? null) ? $data['messages'] : [];

        // 错误体可能是字符串或含 message 的对象
        $error = $data['error'] ?? null;
        if (is_string($error)) {
            $this->errorMessage = $error;
        } elseif (is_array($error)) {
            $this->errorMessage = (string) ($error['message'] ?? '');
        } else {
            $this->errorMessage = '';
        }
    }

    /**
     * 检查发送是否成功（有 messages 且无错误信息）
     */
    public function isSuccess(): bool
    {
        return $this->errorMessage === '' && $this->messages !== [];
    }

    /**
     * 获取消息 ID（messages[0].id）
     */
    public function getMessageId(): string
    {
        return (string) ($this->messages[0]['id'] ?? '');
    }

    /**
     * 获取消息状态（messages[0].message_status）
     */
    public function getMessageStatus(): string
    {
        return (string) ($this->messages[0]['message_status'] ?? '');
    }

    /**
     * 获取接收者 wa_id（contacts[0].wa_id）
     */
    public function getWaId(): string
    {
        return (string) ($this->contacts[0]['wa_id'] ?? '');
    }

    /**
     * 获取接收者 BSUID user_id（contacts[0].user_id，仅接入 BSUID 的商家返回）
     */
    public function getUserId(): string
    {
        return (string) ($this->contacts[0]['user_id'] ?? '');
    }

    /**
     * 获取联系人列表（原始结构）
     *
     * @return array
     */
    public function getContacts(): array
    {
        return $this->contacts;
    }

    /**
     * 获取消息列表（原始结构）
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * 获取错误信息（4xx 时不为空）
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * 获取消息产品标识（whatsapp）
     */
    public function getMessagingProduct(): string
    {
        return $this->messagingProduct;
    }

    /**
     * 获取原始响应数据
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'message_id' => $this->getMessageId(),
            'message_status' => $this->getMessageStatus(),
            'wa_id' => $this->getWaId(),
            'error' => $this->errorMessage,
            'data' => $this->data,
        ];
    }
}
