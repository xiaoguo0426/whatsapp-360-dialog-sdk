<?php

namespace Dialog360\Message;

class InteractiveMessage implements MessageInterface
{
    private string $to;
    private string $type;
    private string $body;
    private array $action;
    private ?string $footer;

    public function __construct(
        string $to,
        string $type,
        string $body,
        array $action,
        ?string $footer = null
    ) {
        $this->to = $to;
        $this->type = $type;
        $this->body = $body;
        $this->action = $action;
        $this->footer = $footer;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'interactive';
    }

    public function toArray(): array
    {
        $interactive = [
            'type' => 'interactive',
            'interactive' => [
                'type' => $this->type,
                'body' => [
                    'text' => $this->body
                ],
                'action' => $this->action
            ]
        ];

        if ($this->footer) {
            $interactive['interactive']['footer'] = [
                'text' => $this->footer
            ];
        }

        return $interactive;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getAction(): array
    {
        return $this->action;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    /**
     * 创建按钮消息
     */
    public static function button(
        string $to,
        string $body,
        array $buttons,
        ?string $footer = null
    ): self {
        return new self($to, 'button', $body, ['buttons' => $buttons], $footer);
    }

    /**
     * 创建列表消息
     */
    public static function list(
        string $to,
        string $body,
        array $action,
        ?string $footer = null
    ): self {
        return new self($to, 'list', $body, $action, $footer);
    }

    /**
     * 创建产品消息
     */
    public static function product(
        string $to,
        string $body,
        array $action,
        ?string $footer = null
    ): self {
        return new self($to, 'product', $body, $action, $footer);
    }

    /**
     * 创建产品列表消息
     */
    public static function productList(
        string $to,
        string $body,
        array $action,
        ?string $footer = null
    ): self {
        return new self($to, 'product_list', $body, $action, $footer);
    }
} 