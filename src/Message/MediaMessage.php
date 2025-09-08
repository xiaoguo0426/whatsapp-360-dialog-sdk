<?php

namespace Dialog360\Message;

class MediaMessage implements MessageInterface
{
    private string $to;
    private string $type;
    private ?string $url;
    private ?string $mediaId;
    private ?string $caption;
    private ?string $filename;

    public function __construct(
        string $to,
        string $type,
        ?string $url = null,
        ?string $mediaId = null,
        ?string $caption = null,
        ?string $filename = null
    ) {
        $this->to = $to;
        $this->type = $type;
        $this->url = $url;
        $this->mediaId = $mediaId;
        $this->caption = $caption;
        $this->filename = $filename;

        // 验证必须提供url或mediaId之一
        if (!$url && !$mediaId) {
            throw new \InvalidArgumentException('必须提供URL或媒体ID之一');
        }
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        $media = [
            'type' => $this->type,
            $this->type => []
        ];

        // 使用media_id或url
        if ($this->mediaId) {
            $media[$this->type]['id'] = $this->mediaId;
        } else {
            $media[$this->type]['link'] = $this->url;
        }

        if ($this->caption) {
            $media[$this->type]['caption'] = $this->caption;
        }

        if ($this->filename && $this->type === 'document') {
            $media[$this->type]['filename'] = $this->filename;
        }

        return $media;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * 创建图片消息（通过URL）
     */
    public static function image(string $to, string $url, ?string $caption = null): self
    {
        return new self($to, 'image', $url, null, $caption);
    }

    /**
     * 创建图片消息（通过媒体ID）
     */
    public static function imageById(string $to, string $mediaId, ?string $caption = null): self
    {
        return new self($to, 'image', null, $mediaId, $caption);
    }

    /**
     * 创建音频消息（通过URL）
     */
    public static function audio(string $to, string $url): self
    {
        return new self($to, 'audio', $url);
    }

    /**
     * 创建音频消息（通过媒体ID）
     */
    public static function audioById(string $to, string $mediaId): self
    {
        return new self($to, 'audio', null, $mediaId);
    }

    /**
     * 创建视频消息（通过URL）
     */
    public static function video(string $to, string $url, ?string $caption = null): self
    {
        return new self($to, 'video', $url, null, $caption);
    }

    /**
     * 创建视频消息（通过媒体ID）
     */
    public static function videoById(string $to, string $mediaId, ?string $caption = null): self
    {
        return new self($to, 'video', null, $mediaId, $caption);
    }

    /**
     * 创建文档消息（通过URL）
     */
    public static function document(string $to, string $url, ?string $caption = null, ?string $filename = null): self
    {
        return new self($to, 'document', $url, null, $caption, $filename);
    }

    /**
     * 创建文档消息（通过媒体ID）
     */
    public static function documentById(string $to, string $mediaId, ?string $caption = null, ?string $filename = null): self
    {
        return new self($to, 'document', null, $mediaId, $caption, $filename);
    }

    /**
     * 创建贴纸消息（通过媒体ID）
     */
    public static function stickerById(string $to, string $mediaId): self
    {
        return new self($to, 'sticker', null, $mediaId);
    }
} 