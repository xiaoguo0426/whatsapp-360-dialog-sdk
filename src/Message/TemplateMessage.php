<?php

namespace Dialog360\Message;

class TemplateMessage implements MessageInterface
{
    private string $to;
    private string $templateName;
    private string $language;
    private array $components;

    public function __construct(
        string $to,
        string $templateName,
        string $language = 'en_US',
        array $components = []
    ) {
        $this->to = $to;
        $this->templateName = $templateName;
        $this->language = $language;
        $this->components = $components;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'template';
    }

    public function toArray(): array
    {
        $template = [
            'type' => 'template',
            'template' => [
                'name' => $this->templateName,
                'language' => [
                    'code' => $this->language
                ]
            ]
        ];

        if (!empty($this->components)) {
            $template['template']['components'] = $this->components;
        }

        return $template;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * 添加组件
     */
    public function addComponent(array $component): self
    {
        $this->components[] = $component;
        return $this;
    }

    /**
     * 设置组件
     */
    public function setComponents(array $components): self
    {
        $this->components = $components;
        return $this;
    }
} 