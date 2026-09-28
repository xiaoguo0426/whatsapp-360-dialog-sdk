<?php

namespace Dialog360\Response;

/**
 * GET /message_templates 与 GET /message_template_library 响应（模板分页列表）。
 *
 * 响应体形如：
 * {
 *   "data": [ ...模板对象... ],
 *   "paging": {"next": "...", "previous": "...", "cursors": {"after": "...", "before": "..."}}
 * }
 */
class MessageTemplateListResponse
{
    private array $data;

    private array $templates;

    private array $paging;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->paging = $data['paging'] ?? [];

        $items = $data['data'] ?? [];
        $this->templates = is_array($items) ? $items : [];
    }

    /**
     * 获取模板列表（原始结构，模板对象字段由 API 定义）
     *
     * @return array
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * 获取分页信息
     *
     * @return array
     */
    public function getPaging(): array
    {
        return $this->paging;
    }

    /**
     * 获取向后翻页游标（传给下一次查询的 $after 参数）
     */
    public function getAfterCursor(): ?string
    {
        $cursor = $this->paging['cursors']['after'] ?? null;
        return $cursor === null ? null : (string) $cursor;
    }

    /**
     * 获取向前翻页游标（传给下一次查询的 $before 参数）
     */
    public function getBeforeCursor(): ?string
    {
        $cursor = $this->paging['cursors']['before'] ?? null;
        return $cursor === null ? null : (string) $cursor;
    }

    /**
     * 是否存在下一页
     */
    public function hasNextPage(): bool
    {
        return isset($this->paging['next']);
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
            'templates' => $this->templates,
            'paging' => $this->paging,
        ];
    }
}
