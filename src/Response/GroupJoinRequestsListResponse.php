<?php

namespace Dialog360\Response;

/**
 * GET /groups/{group_id}/join_requests 响应（查询待审批的加群请求）。
 *
 * 响应体形如：
 * {
 *   "data": [{"join_request_id": "...", "wa_id": "1234567890", "creation_timestamp": 1700000000}],
 *   "paging": {"cursors": {"after": "...", "before": "..."}}
 * }
 */
class GroupJoinRequestsListResponse
{
    private array $data;

    private array $joinRequests;

    private array $paging;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->paging = $data['paging'] ?? [];

        $items = $data['data'] ?? [];
        $this->joinRequests = is_array($items) ? $items : [];
    }

    /**
     * 获取加群请求列表，每项形如
     * ['join_request_id' => ..., 'wa_id' => ..., 'creation_timestamp' => ...]
     *
     * @return array
     */
    public function getJoinRequests(): array
    {
        return $this->joinRequests;
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
            'join_requests' => $this->joinRequests,
            'paging' => $this->paging,
        ];
    }
}
