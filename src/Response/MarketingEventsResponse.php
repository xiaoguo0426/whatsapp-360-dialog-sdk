<?php

namespace Dialog360\Response;

/**
 * POST /marketing/{dataset_id}/events 响应（Conversions API 事件上报）。
 *
 * 响应体形如：
 * {"events_received": 3, "fbtrace_id": "...", "messages": [...]}
 */
class MarketingEventsResponse
{
    private array $data;

    private int $eventsReceived;

    private string $fbtraceId;

    private array $messages;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->eventsReceived = (int) ($data['events_received'] ?? 0);
        $this->fbtraceId = (string) ($data['fbtrace_id'] ?? '');
        $this->messages = is_array($data['messages'] ?? null) ? $data['messages'] : [];
    }

    /**
     * 获取成功接收的事件数量
     */
    public function getEventsReceived(): int
    {
        return $this->eventsReceived;
    }

    /**
     * 获取 Meta 跟踪 ID
     */
    public function getFbtraceId(): string
    {
        return $this->fbtraceId;
    }

    /**
     * 获取响应消息列表（原始结构）
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
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
            'events_received' => $this->eventsReceived,
            'fbtrace_id' => $this->fbtraceId,
            'messages' => $this->messages,
        ];
    }
}
