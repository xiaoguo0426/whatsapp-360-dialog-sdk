<?php

namespace Dialog360\Response;

/**
 * GET /block_users 响应（分页查询已拉黑用户）。
 *
 * 响应体形如：
 * {
 *   "data": [{"block_users": [{"input": "+1234567890", "wa_id": "1234567890"}]}],
 *   "paging": {"cursors": {"after": "...", "before": "..."}, "next": "...", "previous": "..."}
 * }
 */
class BlockedUsersListResponse
{
    private array $data;

    private array $users;

    private array $paging;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->paging = $data['paging'] ?? [];

        // data 数组中每项持有 block_users 列表，展开为统一的用户列表
        $users = [];
        foreach ($data['data'] ?? [] as $item) {
            if (is_array($item) && isset($item['block_users']) && is_array($item['block_users'])) {
                foreach ($item['block_users'] as $user) {
                    if (is_array($user)) {
                        $users[] = $user;
                    }
                }
            }
        }

        $this->users = $users;
    }

    /**
     * 获取已拉黑用户列表，每项形如 ['input' => ..., 'wa_id' => ...]
     *
     * @return array
     */
    public function getUsers(): array
    {
        return $this->users;
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
            'users' => $this->users,
            'paging' => $this->paging,
        ];
    }
}
