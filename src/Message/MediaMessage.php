<?php

namespace Dialog360\Message;

class MediaMessage implements MessageInterface
{
    private string $to;
    private string $type;
    private string $url;
    private ?string $caption;
    private ?string $filename;

    public function __construct(
        string $to,
        string $type,
        string $url,
        ?string $caption = null,
        ?string $filename = null
    ) {
        $this->to = $to;
        $this->type = $type;
        $this->url = $url;
        $this->caption = $caption;
        $this->filename = $filename;
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
            $this->type => [
                'link' => $this->url
            ]
        ];

        if ($this->caption) {
            $media[$this->type]['caption'] = $this->caption;
        }

        if ($this->filename && $this->type === 'document') {
            $media[$this->type]['filename'] = $this->filename;
        }

        return $media;
    }

    public function getUrl(): string
    {
        return $this->url;
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
     * 创建图片消息
     */
    public static function image(string $to, string $url, ?string $caption = null): self
    {
        return new self($to, 'image', $url, $caption);
    }

    /**
     * 创建音频消息
     */
    public static function audio(string $to, string $url): self
    {
        return new self($to, 'audio', $url);
    }

    /**
     * 创建视频消息
     */
    public static function video(string $to, string $url, ?string $caption = null): self
    {
        return new self($to, 'video', $url, $caption);
    }

    /**
     * 创建文档消息
     */
    public static function document(string $to, string $url, ?string $caption = null, ?string $filename = null): self
    {
        return new self($to, 'document', $url, $caption, $filename);
    }
} 