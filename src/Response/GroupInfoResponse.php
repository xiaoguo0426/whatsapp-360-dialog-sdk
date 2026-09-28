<?php

namespace Dialog360\Response;

/**
 * GET /groups/{group_id} 响应（群组元数据）。
 *
 * 响应体形如：
 * {
 *   "id": "...",
 *   "subject": "...",
 *   "description": "...",
 *   "creation_timestamp": 1700000000,
 *   "join_approval_mode": false,
 *   "messaging_product": "whatsapp",
 *   "suspended": false,
 *   "total_participant_count": 3,
 *   "participants": [{"wa_id": "1234567890"}]
 * }
 */
class GroupInfoResponse
{
    private array $data;

    private string $groupId;

    private string $subject;

    private string $description;

    private ?int $creationTimestamp;

    private ?bool $joinApprovalMode;

    private string $messagingProduct;

    private bool $suspended;

    private ?int $totalParticipantCount;

    private array $participants;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->groupId = (string) ($data['id'] ?? '');
        $this->subject = (string) ($data['subject'] ?? '');
        $this->description = (string) ($data['description'] ?? '');
        $this->creationTimestamp = isset($data['creation_timestamp']) ? (int) $data['creation_timestamp'] : null;
        $this->joinApprovalMode = isset($data['join_approval_mode']) ? (bool) $data['join_approval_mode'] : null;
        $this->messagingProduct = (string) ($data['messaging_product'] ?? '');
        $this->suspended = (bool) ($data['suspended'] ?? false);
        $this->totalParticipantCount = isset($data['total_participant_count']) ? (int) $data['total_participant_count'] : null;
        $this->participants = is_array($data['participants'] ?? null) ? $data['participants'] : [];
    }

    /**
     * 获取群组 ID
     */
    public function getGroupId(): string
    {
        return $this->groupId;
    }

    /**
     * 获取群组名称（subject）
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * 获取群组描述
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * 获取群组创建时间戳（Unix 秒）
     */
    public function getCreationTimestamp(): ?int
    {
        return $this->creationTimestamp;
    }

    /**
     * 获取是否开启加群审批模式
     */
    public function getJoinApprovalMode(): ?bool
    {
        return $this->joinApprovalMode;
    }

    /**
     * 获取消息产品标识（whatsapp）
     */
    public function getMessagingProduct(): string
    {
        return $this->messagingProduct;
    }

    /**
     * 群组是否被暂停
     */
    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    /**
     * 获取成员总数
     */
    public function getTotalParticipantCount(): ?int
    {
        return $this->totalParticipantCount;
    }

    /**
     * 获取成员列表（原始结构，每项形如 ['wa_id' => ...]）
     *
     * @return array
     */
    public function getParticipants(): array
    {
        return $this->participants;
    }

    /**
     * 获取成员 wa_id 列表
     *
     * @return string[]
     */
    public function getParticipantWaIds(): array
    {
        $waIds = [];
        foreach ($this->participants as $participant) {
            if (isset($participant['wa_id']) && (string) $participant['wa_id'] !== '') {
                $waIds[] = (string) $participant['wa_id'];
            }
        }

        return $waIds;
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
            'subject' => $this->subject,
            'description' => $this->description,
            'creation_timestamp' => $this->creationTimestamp,
            'join_approval_mode' => $this->joinApprovalMode,
            'messaging_product' => $this->messagingProduct,
            'suspended' => $this->suspended,
            'total_participant_count' => $this->totalParticipantCount,
            'participants' => $this->participants,
        ];
    }
}
