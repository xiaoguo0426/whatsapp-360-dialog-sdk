<?php

namespace Dialog360\Message;

class TextMessage implements MessageInterface
{
    private string $to;
    private string $text;
    private bool $previewUrl;

    public function __construct(string $to, string $text, bool $previewUrl = false)
    {
        $this->to = $to;
        $this->text = $text;
        $this->previewUrl = $previewUrl;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'text';
    }

    public function toArray(): array
    {
        return [
            'type' => 'text',
            'text' => [
                'body' => $this->text,
                'preview_url' => $this->previewUrl
            ]
        ];
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function isPreviewUrl(): bool
    {
        return $this->previewUrl;
    }
} 