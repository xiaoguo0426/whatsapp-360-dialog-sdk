<?php

namespace Dialog360\Response;

/**
 * GET / POST /groups/{group_id}/invite_link 响应（获取 / 重置群组邀请链接）。
 *
 * 响应体形如：
 * {"invite_link": "https://chat.whatsapp.com/...", "messaging_product": "whatsapp"}
 */
class GroupInviteLinkResponse
{
    private array $data;

    private string $inviteLink;

    private string $messagingProduct;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->inviteLink = (string) ($data['invite_link'] ?? '');
        $this->messagingProduct = (string) ($data['messaging_product'] ?? '');
    }

    /**
     * 获取群组邀请链接
     */
    public function getInviteLink(): string
    {
        return $this->inviteLink;
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
            'invite_link' => $this->inviteLink,
            'messaging_product' => $this->messagingProduct,
        ];
    }
}
