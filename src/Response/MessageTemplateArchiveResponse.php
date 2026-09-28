<?php

namespace Dialog360\Response;

/**
 * POST /message_templates/archive 与 /message_templates/unarchive 响应。
 *
 * 响应体形如：
 * {
 *   "archived_templates": ["<template-id>", ...],
 *   "failed_templates": {"<template-id>": {"error": "..."}}
 * }
 * unarchive 时对应键为 unarchived_templates。
 */
class MessageTemplateArchiveResponse
{
    private array $data;

    private array $processedTemplates;

    private array $failedTemplates;

    public function __construct(array $data)
    {
        $this->data = $data;

        // archive 返回 archived_templates，unarchive 返回 unarchived_templates
        $processed = $data['archived_templates'] ?? $data['unarchived_templates'] ?? [];
        $failed = $data['failed_templates'] ?? [];

        $this->processedTemplates = is_array($processed) ? $processed : [];
        $this->failedTemplates = is_array($failed) ? $failed : [];
    }

    /**
     * 检查是否全部处理成功（无失败条目）
     */
    public function isSuccess(): bool
    {
        return $this->failedTemplates === [];
    }

    /**
     * 获取成功处理的模板 ID 列表（archive/unarchive 对应各自响应键）
     *
     * @return string[]
     */
    public function getProcessedTemplates(): array
    {
        return $this->processedTemplates;
    }

    /**
     * 获取失败明细，键为模板 ID，值为错误对象
     *
     * @return array
     */
    public function getFailedTemplates(): array
    {
        return $this->failedTemplates;
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
            'processed_templates' => $this->processedTemplates,
            'failed_templates' => $this->failedTemplates,
        ];
    }
}
