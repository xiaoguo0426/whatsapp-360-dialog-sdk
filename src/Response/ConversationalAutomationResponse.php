<?php

namespace Dialog360\Response;

/**
 * GET /conversational_automation 响应（Conversational Components）。
 *
 * 包含电话号码的会话自动化配置：命令（commands）、欢迎消息开关、提示语（prompts）。
 */
class ConversationalAutomationResponse
{
    private array $data;

    private string $phoneNumberId;

    private array $commands;

    private bool $enableWelcomeMessage;

    private array $prompts;

    public function __construct(array $data)
    {
        $this->data = $data;

        $automation = $data['conversational_automation'] ?? [];

        $this->phoneNumberId = (string) ($data['id'] ?? '');
        $this->commands = $automation['commands'] ?? [];
        $this->enableWelcomeMessage = (bool) ($automation['enable_welcome_message'] ?? false);
        $this->prompts = $automation['prompts'] ?? [];
    }

    /**
     * 获取电话号码 ID
     */
    public function getPhoneNumberId(): string
    {
        return $this->phoneNumberId;
    }

    /**
     * 获取命令列表，每项形如 ['command_name' => ..., 'command_description' => ...]
     *
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * 获取是否启用欢迎消息
     */
    public function getEnableWelcomeMessage(): bool
    {
        return $this->enableWelcomeMessage;
    }

    /**
     * 获取提示语列表（字符串数组）
     *
     * @return array
     */
    public function getPrompts(): array
    {
        return $this->prompts;
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
            'phone_number_id' => $this->phoneNumberId,
            'commands' => $this->commands,
            'enable_welcome_message' => $this->enableWelcomeMessage,
            'prompts' => $this->prompts,
        ];
    }
}
