<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\BlockUsersResponse;
use Dialog360\Response\BlockedUsersListResponse;

/**
 * 拉黑用户 API 域：/block_users
 *
 * - GET /block_users：分页查询已拉黑用户（limit / after / before）
 * - POST /block_users：拉黑用户
 * - DELETE /block_users：解除拉黑
 *
 * 被拉黑的用户无法向商家发起会话或查看在线状态，商家也无法向其发送消息。
 *
 * @see https://docs.360dialog.com/docs/messaging-api/api-reference/block-users
 */
readonly class BlockUsersApi
{
    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 分页查询已拉黑用户（Cloud API: GET /block_users）
     *
     * @param int|null $limit 单次返回的最大用户数
     * @param string|null $after 向后翻页游标（上次响应的 getAfterCursor()）
     * @param string|null $before 向前翻页游标（上次响应的 getBeforeCursor()）
     * @return BlockedUsersListResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function list(?int $limit = null, ?string $after = null, ?string $before = null): BlockedUsersListResponse
    {
        $query = array_filter([
            'limit' => $limit,
            'after' => $after,
            'before' => $before,
        ], static fn ($value) => $value !== null);

        $data = $this->connector->get('/block_users', ['query' => $query], '查询已拉黑用户');
        return new BlockedUsersListResponse($data);
    }

    /**
     * 拉黑用户（Cloud API: POST /block_users）
     *
     * 400"混合成功/失败或错误请求"不抛异常，返回携带部分结果的 BlockUsersResponse
     * （isSuccess()=false，getError() 非 null），与 messages()->send() 行为一致。
     *
     * @param array $users 用户号码（wa_id）列表
     * @return BlockUsersResponse
     * @throws Dialog360Exception 5xx/网络错误重试耗尽
     */
    public function block(array $users): BlockUsersResponse
    {
        return $this->apply('POST', '拉黑用户', $users);
    }

    /**
     * 解除拉黑（Cloud API: DELETE /block_users）
     *
     * 400"混合成功/失败或错误请求"不抛异常，返回携带部分结果的 BlockUsersResponse
     * （isSuccess()=false，getError() 非 null），与 messages()->send() 行为一致。
     *
     * @param array $users 用户号码（wa_id）列表
     * @return BlockUsersResponse
     * @throws Dialog360Exception 5xx/网络错误重试耗尽
     */
    public function unblock(array $users): BlockUsersResponse
    {
        return $this->apply('DELETE', '解除拉黑用户', $users);
    }

    /**
     * block/unblock 的统一实现（同一路径、同一请求体结构，仅方法不同）
     *
     * @throws Dialog360Exception
     */
    private function apply(string $method, string $action, array $users): BlockUsersResponse
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'block_users' => $this->normalizeUsers($users, $action),
        ];

        try {
            $data = $this->connector->request($method, '/block_users', ['json' => $payload], $action);
        } catch (Dialog360ClientError $e) {
            return new BlockUsersResponse($e->getResponseBody());
        }

        return new BlockUsersResponse($data);
    }

    /**
     * 校验并规范化用户列表：接受字符串（或标量）号码，转换为 [{'user' => ...}, ...]
     */
    private function normalizeUsers(array $users, string $action): array
    {
        $normalized = [];
        foreach ($users as $index => $user) {
            if (!is_scalar($user) || trim((string) $user) === '') {
                throw new \InvalidArgumentException(sprintf('%s：users[%s] 必须为非空的用户号码', $action, $index));
            }

            $normalized[] = ['user' => (string) $user];
        }

        return $normalized;
    }
}
