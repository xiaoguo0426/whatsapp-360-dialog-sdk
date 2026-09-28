<?php

namespace Dialog360\Response;

/**
 * POST /block_users（拉黑）与 DELETE /block_users（解除拉黑）响应。
 *
 * 成功（200）响应体形如：
 * {"messaging_product": "whatsapp", "block_users": {"added_users": [...], "failed_users": [...]}}
 *
 * 400 可能表示"混合成功/失败或错误请求"，此时响应体同时包含 block_users 部分结果
 * 与 error 对象；本类统一包装，调用方通过 isSuccess()/getError() 区分。
 */
class BlockUsersResponse
{
    private array $data;

    private string $messagingProduct;

    private array $addedUsers;

    private array $failedUsers;

    private ?array $error;

    public function __construct(array $data)
    {
        $this->data = $data;

        // 200 与 400 的结果都挂在 block_users 键下
        $result = $data['block_users'] ?? [];

        $this->messagingProduct = (string) ($data['messaging_product'] ?? '');
        $this->addedUsers = $result['added_users'] ?? [];
        $this->failedUsers = $result['failed_users'] ?? [];
        $this->error = isset($data['error']) && is_array($data['error']) ? $data['error'] : null;
    }

    /**
     * 检查请求是否被 API 接受（无 error 对象）。
     *
     * 部分用户处理失败的情况（failed_users 非空）不影响此返回值，需单独检查 getFailedUsers()。
     */
    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    /**
     * 获取消息产品标识（whatsapp）
     */
    public function getMessagingProduct(): string
    {
        return $this->messagingProduct;
    }

    /**
     * 获取成功处理的用户列表，每项形如 ['input' => ..., 'wa_id' => ...]
     *
     * @return array
     */
    public function getAddedUsers(): array
    {
        return $this->addedUsers;
    }

    /**
     * 获取处理失败的用户列表，每项形如 ['input' => ..., 'wa_id' => ..., 'errors' => [...]]
     *
     * @return array
     */
    public function getFailedUsers(): array
    {
        return $this->failedUsers;
    }

    /**
     * 获取 API 错误对象（400 混合结果/错误请求时返回数组，其余返回 null）
     */
    public function getError(): ?array
    {
        return $this->error;
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
            'messaging_product' => $this->messagingProduct,
            'added_users' => $this->addedUsers,
            'failed_users' => $this->failedUsers,
            'error' => $this->error,
        ];
    }
}
