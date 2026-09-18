<?php

namespace Dialog360\Message;

/**
 * 表情回应消息（Reaction）
 *
 * 对已发送/收到的 WhatsApp 消息添加或移除 emoji 回应。
 *
 * JSON 结构:
 * {
 *   "messaging_product": "whatsapp",
 *   "to": "<phone>",
 *   "type": "reaction",
 *   "reaction": {
 *     "message_id": "<WHATSAPP_MESSAGE_ID>",  // 必填，被回应消息的 ID（wamid.xxx）
 *     "emoji": "<EMOJI>"                      // 必填，emoji 表情；空字符串 "" 表示移除回应
 *   }
 * }
 */
class ReactionMessage implements MessageInterface
{
    private string $to;
    private string $messageId;
    private string $emoji;

    /**
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string $messageId 被回应的 WhatsApp 消息 ID（wamid.xxx 格式）
     * @param string $emoji emoji 表情，如 "👍"；传空字符串 "" 则移除已有回应
     */
    public function __construct(string $to, string $messageId, string $emoji)
    {
        if ($messageId === '') {
            throw new \InvalidArgumentException('message_id 不能为空');
        }

        $this->to = $to;
        $this->messageId = $messageId;
        $this->emoji = $emoji;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'reaction';
    }

    public function toArray(): array
    {
        return [
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $this->messageId,
                'emoji' => $this->emoji
            ]
        ];
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getEmoji(): string
    {
        return $this->emoji;
    }

    /**
     * 对指定消息添加 emoji 回应
     *
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string $messageId 被回应的消息 ID（wamid.xxx）
     * @param string $emoji emoji 表情，如 "👍"
     */
    public static function react(string $to, string $messageId, string $emoji): self
    {
        return new self($to, $messageId, $emoji);
    }

    /**
     * 移除对指定消息的已有回应（官方规定 emoji 传空字符串 ""）
     *
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string $messageId 被回应的消息 ID（wamid.xxx）
     */
    public static function remove(string $to, string $messageId): self
    {
        return new self($to, $messageId, '');
    }
}
