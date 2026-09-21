<?php

namespace Dialog360\Message;

/**
 * 贴图消息（Sticker）
 *
 * 在 WhatsApp 消息中显示动画或静态贴图。
 *
 * 支持的贴图格式（仅 WebP）:
 * - 动画贴图: .webp (image/webp)，最大 500 KB
 * - 静态贴图: .webp (image/webp)，最大 100 KB
 *
 * 注意: 贴图不支持 caption / filename 等字段，仅能通过 id 或 link 引用媒体。
 *
 * JSON 结构:
 * {
 *   "messaging_product": "whatsapp",
 *   "to": "<phone>",
 *   "type": "sticker",
 *   "sticker": {
 *     "id": "<MEDIA_ID>",    // 使用已上传媒体时必填（推荐）
 *     "link": "<MEDIA_URL>"  // 使用托管媒体时必填（不推荐）
 *   }
 * }
 */
class StickerMessage implements MessageInterface
{
    private string $to;
    private ?string $mediaId;
    private ?string $link;

    /**
     * mediaId 与 link 必须且只能提供其一（互斥）
     *
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string|null $mediaId 已上传媒体的 ID（官方推荐）
     * @param string|null $link 托管在公开服务器上的贴图（.webp）URL（不推荐，性能较差）
     */
    public function __construct(string $to, ?string $mediaId = null, ?string $link = null)
    {
        $hasMediaId = $mediaId !== null && $mediaId !== '';
        $hasLink = $link !== null && $link !== '';

        if ($hasMediaId === $hasLink) {
            throw new \InvalidArgumentException('必须且只能提供 mediaId 或 link 之一');
        }

        $this->to = $to;
        $this->mediaId = $hasMediaId ? $mediaId : null;
        $this->link = $hasLink ? $link : null;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'sticker';
    }

    public function toArray(): array
    {
        $sticker = [];

        // 二选一：已上传媒体用 id，托管媒体用 link
        if ($this->mediaId !== null) {
            $sticker['id'] = $this->mediaId;
        } else {
            $sticker['link'] = $this->link;
        }

        return [
            'type' => 'sticker',
            'sticker' => $sticker
        ];
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * 通过已上传媒体的 ID 创建贴图消息（官方推荐方式）
     *
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string $mediaId 已上传媒体的 ID（可通过 Dialog360Client::uploadMedia() 获取）
     */
    public static function fromMediaId(string $to, string $mediaId): self
    {
        return new self($to, $mediaId);
    }

    /**
     * 通过托管在公开服务器上的贴图 URL 创建消息
     *
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string $link 贴图（.webp）的公开 URL
     */
    public static function fromUrl(string $to, string $link): self
    {
        return new self($to, null, $link);
    }
}
