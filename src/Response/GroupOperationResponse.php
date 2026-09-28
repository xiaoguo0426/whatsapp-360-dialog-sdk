<?php

namespace Dialog360\Response;

/**
 * 群组操作通用成功响应（返回 {"success": bool} 的端点共用）：
 *
 * - POST /groups/{group_id}（更新群组）
 * - DELETE /groups/{group_id}（删除群组）
 * - DELETE /groups/{group_id}/participants（移除成员）
 */
class GroupOperationResponse
{
    private array $data;

    private bool $success;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->success = (bool) ($data['success'] ?? false);
    }

    /**
     * 检查操作是否成功
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
