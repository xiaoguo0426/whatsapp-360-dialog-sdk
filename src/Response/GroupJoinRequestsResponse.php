<?php

namespace Dialog360\Response;

/**
 * POST / DELETE /groups/{group_id}/join_requests 响应（批准 / 拒绝加群请求）。
 *
 * 响应体形如：
 * {
 *   "approved_join_requests": ["..."],
 *   "rejected_join_requests": ["..."],
 *   "failed_join_requests": [{"join_request_id": "...", "errors": [{"code": ..., "message": ...}]}],
 *   "errors": [{"code": ..., "message": ..., "title": ..., "error_data": {"details": ...}}],
 *   "messaging_product": "whatsapp"
 * }
 */
class GroupJoinRequestsResponse
{
    private array $data;

    private array $approvedJoinRequests;

    private array $rejectedJoinRequests;

    private array $failedJoinRequests;

    private array $errors;

    private string $messagingProduct;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->approvedJoinRequests = is_array($data['approved_join_requests'] ?? null) ? $data['approved_join_requests'] : [];
        $this->rejectedJoinRequests = is_array($data['rejected_join_requests'] ?? null) ? $data['rejected_join_requests'] : [];
        $this->failedJoinRequests = is_array($data['failed_join_requests'] ?? null) ? $data['failed_join_requests'] : [];
        $this->errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
        $this->messagingProduct = (string) ($data['messaging_product'] ?? '');
    }

    /**
     * 检查是否全部请求处理成功（无请求级错误且无处理失败的条目）。
     *
     * 部分失败不影响已批准/已拒绝的结果，可通过对应 getter 获取明细。
     */
    public function isSuccess(): bool
    {
        return $this->errors === [] && $this->failedJoinRequests === [];
    }

    /**
     * 获取已批准的加群请求列表
     *
     * @return array
     */
    public function getApprovedJoinRequests(): array
    {
        return $this->approvedJoinRequests;
    }

    /**
     * 获取已拒绝的加群请求列表
     *
     * @return array
     */
    public function getRejectedJoinRequests(): array
    {
        return $this->rejectedJoinRequests;
    }

    /**
     * 获取处理失败的加群请求列表，每项形如
     * ['join_request_id' => ..., 'errors' => [['code' => ..., 'message' => ...], ...]]
     *
     * @return array
     */
    public function getFailedJoinRequests(): array
    {
        return $this->failedJoinRequests;
    }

    /**
     * 获取请求级错误列表（每项形如 ['code' => ..., 'message' => ..., ...]）
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取消息产品标识（whatsapp）
     */
    public function getMessagingProduct(): string
    {
        return $this->messagingProduct;
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
            'approved_join_requests' => $this->approvedJoinRequests,
            'rejected_join_requests' => $this->rejectedJoinRequests,
            'failed_join_requests' => $this->failedJoinRequests,
            'errors' => $this->errors,
            'messaging_product' => $this->messagingProduct,
        ];
    }
}
