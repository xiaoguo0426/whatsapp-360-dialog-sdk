<?php

namespace Dialog360\Response;

/**
 * POST /conversational_automation 响应（Conversational Components）。
 *
 * 成功时返回 {"success": true}。
 */
class SetConversationalAutomationResponse
{
    private array $data;

    private bool $success;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->success = (bool) ($data['success'] ?? false);
    }

    /**
     * 检查配置是否成功
     */
    public function isSuccess(): bool
    {
        return $this->success;
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
            'success' => $this->success,
        ];
    }
}
