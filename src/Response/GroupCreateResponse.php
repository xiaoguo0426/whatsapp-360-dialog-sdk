<?php

namespace Dialog360\Response;

/**
 * POST /groups 响应（创建群组）。
 *
 * 成功时返回 {"id": "<group-id>"}。
 */
class GroupCreateResponse
{
    private array $data;

    private string $groupId;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->groupId = (string) ($data['id'] ?? '');
    }

    /**
     * 获取新群组的 group_id
     */
    public function getGroupId(): string
    {
        return $this->groupId;
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
            'group_id' => $this->groupId,
        ];
    }
}
