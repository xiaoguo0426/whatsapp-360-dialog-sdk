<?php

namespace Dialog360\Response;

/**
 * GET /groups 响应（分页查询电话号码的活跃群组）。
 *
 * 响应体形如：
 * {
 *   "data": {"groups": [{"id": "...", "subject": "...", "created_at": 1700000000}]},
 *   "paging": {"cursors": {"after": "...", "before": "..."}, "next": "...", "previous": "..."}
 * }
 */
class GroupListResponse
{
    private array $data;

    private array $groups;

    private array $paging;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->paging = $data['paging'] ?? [];

        $groups = $data['data']['groups'] ?? [];
        $this->groups = is_array($groups) ? $groups : [];
    }

    /**
     * 获取群组列表，每项形如 ['id' => ..., 'subject' => ..., 'created_at' => ...]
     *
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * 获取分页信息（cursors/next/previous）
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
            'groups' => $this->groups,
            'paging' => $this->paging,
        ];
    }
}
