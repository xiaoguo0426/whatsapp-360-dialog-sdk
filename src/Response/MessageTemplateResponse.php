<?php

namespace Dialog360\Response;

/**
 * GET /message_templates/{template_id} 响应（模板详情）。
 *
 * 常见字段：id、name、category、language、status、components[]、quality_score 等；
 * 完整字段集合由 API 定义，可通过 getData() 获取原始数据。
 */
class MessageTemplateResponse
{
    private array $data;

    private string $templateId;

    private string $name;

    private string $category;

    private string $language;

    private string $status;

    private array $components;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->templateId = (string) ($data['id'] ?? '');
        $this->name = (string) ($data['name'] ?? '');
        $this->category = (string) ($data['category'] ?? '');
        $this->language = (string) ($data['language'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->components = is_array($data['components'] ?? null) ? $data['components'] : [];
    }

    /**
     * 获取模板 ID
     */
    public function getTemplateId(): string
    {
        return $this->templateId;
    }

    /**
     * 获取模板名称
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取模板分类（如 MARKETING / UTILITY / AUTHENTICATION）
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * 获取模板语言（如 en_US）
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * 获取模板状态（如 APPROVED / PENDING / REJECTED）
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 获取组件列表（原始结构）
     *
     * @return array
     */
    public function getComponents(): array
    {
        return $this->components;
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
            'name' => $this->name,
            'category' => $this->category,
            'language' => $this->language,
            'status' => $this->status,
            'components' => $this->components,
        ];
    }
}
