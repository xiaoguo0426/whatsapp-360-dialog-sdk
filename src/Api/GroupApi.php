<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\GroupCreateResponse;
use Dialog360\Response\GroupInfoResponse;
use Dialog360\Response\GroupInviteLinkResponse;
use Dialog360\Response\GroupJoinRequestsListResponse;
use Dialog360\Response\GroupJoinRequestsResponse;
use Dialog360\Response\GroupListResponse;
use Dialog360\Response\GroupOperationResponse;

/**
 * 群组 API 域：/groups
 *
 * - GET/POST /groups：查询群组列表（分页）/ 创建群组
 * - GET/POST/DELETE /groups/{group_id}：群组详情 / 更新群组 / 删除群组
 * - GET/POST /groups/{group_id}/invite_link：获取 / 重置邀请链接
 * - GET/POST/DELETE /groups/{group_id}/join_requests：加群请求的查询 / 批准 / 拒绝
 * - DELETE /groups/{group_id}/participants：移除成员（单次最多 8 人）
 *
 * @see https://docs.360dialog.com/docs/messaging-api/api-reference/groups
 */
readonly class GroupApi
{
    /** 单次移除成员的数量上限 */
    public const MAX_REMOVE_PARTICIPANTS = 8;

    /** GET /groups 分页 limit 的取值范围（1-1024，默认 25） */
    public const MIN_LIST_LIMIT = 1;

    public const MAX_LIST_LIMIT = 1024;

    /** 更新群组时可设置的 JSON 字段 */
    public const UPDATABLE_FIELDS = ['subject', 'description', 'profile_picture_file'];

    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 分页查询活跃群组（Cloud API: GET /groups）
     *
     * @param int|null $limit 单次返回的群组数（1-1024，默认 25）
     * @param string|null $after 向后翻页游标（上次响应的 getAfterCursor()）
     * @param string|null $before 向前翻页游标（上次响应的 getBeforeCursor()）
     * @return GroupListResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function list(?int $limit = null, ?string $after = null, ?string $before = null): GroupListResponse
    {
        if ($limit !== null && ($limit < self::MIN_LIST_LIMIT || $limit > self::MAX_LIST_LIMIT)) {
            throw new \InvalidArgumentException(sprintf(
                'limit 必须在 %d 到 %d 之间',
                self::MIN_LIST_LIMIT,
                self::MAX_LIST_LIMIT
            ));
        }

        $query = array_filter([
            'limit' => $limit,
            'after' => $after,
            'before' => $before,
        ], static fn ($value) => $value !== null);

        $data = $this->connector->get('/groups', ['query' => $query], '查询群组列表');
        return new GroupListResponse($data);
    }

    /**
     * 创建群组（Cloud API: POST /groups）
     *
     * @param string $subject 群组名称
     * @param string|null $description 群组描述
     * @param bool|null $joinApprovalMode 是否开启加群审批模式（null 表示不设置）
     * @return GroupCreateResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function create(string $subject, ?string $description = null, ?bool $joinApprovalMode = null): GroupCreateResponse
    {
        if (trim($subject) === '') {
            throw new \InvalidArgumentException('subject 不能为空');
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'subject' => $subject,
        ];
        if ($description !== null) {
            $payload['description'] = $description;
        }
        if ($joinApprovalMode !== null) {
            $payload['join_approval_mode'] = $joinApprovalMode;
        }

        $data = $this->connector->request('POST', '/groups', ['json' => $payload], '创建群组');

        return new GroupCreateResponse($data);
    }

    /**
     * 获取群组详情（Cloud API: GET /groups/{group_id}）
     *
     * @param string $groupId 群组 ID
     * @param array|null $fields 需要返回的字段列表（逗号分隔传给 API，字段集合由 API 定义）
     * @return GroupInfoResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function get(string $groupId, ?array $fields = null): GroupInfoResponse
    {
        $this->assertGroupId($groupId);

        $options = [];
        if ($fields !== null && $fields !== []) {
            $options['query'] = ['fields' => implode(',', $fields)];
        }

        $data = $this->connector->get('/groups/' . rawurlencode($groupId), $options, '获取群组详情');
        return new GroupInfoResponse($data);
    }

    /**
     * 更新群组（Cloud API: POST /groups/{group_id}）
     *
     * 字段键见 GroupApi::UPDATABLE_FIELDS。仅 subject/description 时以 JSON 提交；
     * 携带 profile_picture_file（本地图片路径）时自动改用 multipart 提交。
     *
     * @param string $groupId 群组 ID
     * @param array $fields 要更新的字段
     * @return GroupOperationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function update(string $groupId, array $fields): GroupOperationResponse
    {
        $this->assertGroupId($groupId);

        if ($fields === []) {
            throw new \InvalidArgumentException('至少提供一个要更新的字段');
        }

        $unknown = array_diff(array_keys($fields), self::UPDATABLE_FIELDS);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf(
                'fields 含未知字段"%s"，允许的字段：%s',
                implode('", "', $unknown),
                implode(', ', self::UPDATABLE_FIELDS)
            ));
        }

        // 携带头像文件时改用 multipart，其余字段作为表单项一起提交
        if (isset($fields['profile_picture_file'])) {
            return new GroupOperationResponse(
                $this->connector->request(
                    'POST',
                    '/groups/' . rawurlencode($groupId),
                    ['multipart' => $this->buildMultipartFields($fields)],
                    '更新群组'
                )
            );
        }

        $payload = ['messaging_product' => 'whatsapp'];
        foreach ($fields as $key => $value) {
            if (!is_scalar($value)) {
                throw new \InvalidArgumentException(sprintf('%s 必须为标量值', $key));
            }
            $payload[$key] = (string) $value;
        }

        $data = $this->connector->request('POST', '/groups/' . rawurlencode($groupId), ['json' => $payload], '更新群组');

        return new GroupOperationResponse($data);
    }

    /**
     * 删除群组并永久移除全部成员（Cloud API: DELETE /groups/{group_id}）
     *
     * @return GroupOperationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function delete(string $groupId): GroupOperationResponse
    {
        $this->assertGroupId($groupId);

        $data = $this->connector->request('DELETE', '/groups/' . rawurlencode($groupId), [], '删除群组');

        return new GroupOperationResponse($data);
    }

    /**
     * 获取当前邀请链接（Cloud API: GET /groups/{group_id}/invite_link）
     *
     * @return GroupInviteLinkResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getInviteLink(string $groupId): GroupInviteLinkResponse
    {
        $this->assertGroupId($groupId);

        $data = $this->connector->get('/groups/' . rawurlencode($groupId) . '/invite_link', [], '获取群组邀请链接');
        return new GroupInviteLinkResponse($data);
    }

    /**
     * 重置邀请链接（旧链接立即失效，Cloud API: POST /groups/{group_id}/invite_link）
     *
     * @return GroupInviteLinkResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function resetInviteLink(string $groupId): GroupInviteLinkResponse
    {
        $this->assertGroupId($groupId);

        $data = $this->connector->request(
            'POST',
            '/groups/' . rawurlencode($groupId) . '/invite_link',
            ['json' => ['messaging_product' => 'whatsapp']],
            '重置群组邀请链接'
        );

        return new GroupInviteLinkResponse($data);
    }

    /**
     * 分页查询待审批的加群请求（Cloud API: GET /groups/{group_id}/join_requests）
     *
     * @return GroupJoinRequestsListResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function listJoinRequests(string $groupId, ?int $limit = null, ?string $after = null, ?string $before = null): GroupJoinRequestsListResponse
    {
        $this->assertGroupId($groupId);

        $query = array_filter([
            'limit' => $limit,
            'after' => $after,
            'before' => $before,
        ], static fn ($value) => $value !== null);

        $data = $this->connector->get('/groups/' . rawurlencode($groupId) . '/join_requests', ['query' => $query], '查询加群请求');
        return new GroupJoinRequestsListResponse($data);
    }

    /**
     * 批准加群请求（Cloud API: POST /groups/{group_id}/join_requests）
     *
     * @param array $joinRequestIds 加群请求 ID 列表（来自 listJoinRequests）
     * @return GroupJoinRequestsResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function approveJoinRequests(string $groupId, array $joinRequestIds): GroupJoinRequestsResponse
    {
        return $this->applyJoinRequests('POST', '批准加群请求', $groupId, $joinRequestIds);
    }

    /**
     * 拒绝加群请求（Cloud API: DELETE /groups/{group_id}/join_requests）
     *
     * @param array $joinRequestIds 加群请求 ID 列表（来自 listJoinRequests）
     * @return GroupJoinRequestsResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function rejectJoinRequests(string $groupId, array $joinRequestIds): GroupJoinRequestsResponse
    {
        return $this->applyJoinRequests('DELETE', '拒绝加群请求', $groupId, $joinRequestIds);
    }

    /**
     * 移除成员（Cloud API: DELETE /groups/{group_id}/participants）
     *
     * @param array $users 成员号码（wa_id）列表，单次最多 8 人
     * @return GroupOperationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function removeParticipants(string $groupId, array $users): GroupOperationResponse
    {
        $this->assertGroupId($groupId);

        $payload = [
            'messaging_product' => 'whatsapp',
            'participants' => $this->normalizeParticipants($users),
        ];

        $data = $this->connector->request(
            'DELETE',
            '/groups/' . rawurlencode($groupId) . '/participants',
            ['json' => $payload],
            '移除群组成员'
        );

        return new GroupOperationResponse($data);
    }

    /**
     * approve/reject 加群请求的统一实现（同一路径与请求体结构，仅方法不同）
     *
     * @throws Dialog360Exception
     */
    private function applyJoinRequests(string $method, string $action, string $groupId, array $joinRequestIds): GroupJoinRequestsResponse
    {
        $this->assertGroupId($groupId);

        $payload = [
            'messaging_product' => 'whatsapp',
            'join_requests' => $this->normalizeJoinRequestIds($joinRequestIds, $action),
        ];

        $data = $this->connector->request($method, '/groups/' . rawurlencode($groupId) . '/join_requests', ['json' => $payload], $action);

        return new GroupJoinRequestsResponse($data);
    }

    /**
     * 校验 group_id 非空
     */
    private function assertGroupId(string $groupId): void
    {
        if (trim($groupId) === '') {
            throw new \InvalidArgumentException('groupId 不能为空');
        }
    }

    /**
     * 校验并规范化加群请求 ID 列表（非空字符串数组）
     *
     * @return string[]
     */
    private function normalizeJoinRequestIds(array $joinRequestIds, string $action): array
    {
        if ($joinRequestIds === []) {
            throw new \InvalidArgumentException($action . '：joinRequestIds 不能为空');
        }

        $normalized = [];
        foreach ($joinRequestIds as $index => $id) {
            if (!is_scalar($id) || trim((string) $id) === '') {
                throw new \InvalidArgumentException(sprintf('%s：joinRequestIds[%s] 必须为非空字符串', $action, $index));
            }

            $normalized[] = (string) $id;
        }

        return $normalized;
    }

    /**
     * 校验并规范化移除成员列表：接受字符串号码，转换为 [{'user' => ...}, ...]，1-8 人
     *
     * @return array
     */
    private function normalizeParticipants(array $users): array
    {
        if ($users === []) {
            throw new \InvalidArgumentException('users 不能为空');
        }
        if (count($users) > self::MAX_REMOVE_PARTICIPANTS) {
            throw new \InvalidArgumentException(sprintf('单次最多移除 %d 名成员', self::MAX_REMOVE_PARTICIPANTS));
        }

        $normalized = [];
        foreach ($users as $index => $user) {
            if (!is_scalar($user) || trim((string) $user) === '') {
                throw new \InvalidArgumentException(sprintf('users[%s] 必须为非空的用户号码', $index));
            }

            $normalized[] = ['user' => (string) $user];
        }

        return $normalized;
    }

    /**
     * 构造 multipart 表单项（更新群组头像场景）
     */
    private function buildMultipartFields(array $fields): array
    {
        $filePath = (string) $fields['profile_picture_file'];
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException(sprintf('profile_picture_file 文件不存在或不可读：%s', $filePath));
        }

        $multipart = [
            ['name' => 'messaging_product', 'contents' => 'whatsapp'],
        ];
        foreach (['subject', 'description'] as $textField) {
            if (isset($fields[$textField])) {
                if (!is_scalar($fields[$textField])) {
                    throw new \InvalidArgumentException(sprintf('%s 必须为标量值', $textField));
                }
                $multipart[] = ['name' => $textField, 'contents' => (string) $fields[$textField]];
            }
        }
        $multipart[] = [
            'name' => 'profile_picture_file',
            'contents' => fopen($filePath, 'rb'),
            'filename' => basename($filePath),
        ];

        return $multipart;
    }
}
