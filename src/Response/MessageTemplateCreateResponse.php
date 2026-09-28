<?php

namespace Dialog360\Response;

/**
 * POST /message_templates 响应（创建模板）。
 *
 * 响应体形如：{"id": "<template-id>", "status": "PENDING", "category": "MARKETING"}
 */
class MessageTemplateCreateResponse
{
    private array $data;

    private string $templateId;

    private string $status;

    private string $category;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->templateId = (string) ($data['id'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->category = (string) ($data['category'] ?? '');
    }

    /**
     * 获取新模板 ID
     */
    public function getTemplateId(): string
    {
        return $this->templateId;
    }

    /**
     * 获取模板状态（创建后通常为 PENDING）
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 获取模板分类
     */
    public function getCategory(): string
    {
        return $this->category;
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
            'template_id' => $this->templateId,
            'status' => $this->status,
            'category' => $this->category,
        ];
    }
}
