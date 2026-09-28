<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\ConversationalAutomationResponse;
use Dialog360\Response\SetConversationalAutomationResponse;

/**
 * Conversational Components（会话自动化）API 域：/conversational_automation
 *
 * 配置电话号码的欢迎消息开关、命令（commands）与提示语（prompts）。
 *
 * @see https://docs.360dialog.com/docs/messaging-api/api-reference/conversational-components
 */
readonly class ConversationalComponentsApi
{
    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 获取会话自动化配置（Cloud API: GET /conversational_automation）
     *
     * @return ConversationalAutomationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function get(): ConversationalAutomationResponse
    {
        $data = $this->connector->get('/conversational_automation', [], '获取会话自动化配置');
        return new ConversationalAutomationResponse($data);
    }

    /**
     * 配置会话自动化（Cloud API: POST /conversational_automation）
     *
     * @param array $commands 命令列表，每项形如 ['command_name' => ..., 'command_description' => ...]，
     *                        可用 ConversationalComponentsApi::command() 构造
     * @param bool $enableWelcomeMessage 是否启用欢迎消息
     * @param array $prompts 提示语列表（字符串数组）
     * @return SetConversationalAutomationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function configure(array $commands, bool $enableWelcomeMessage = false, array $prompts = []): SetConversationalAutomationResponse
    {
        $payload = [
            'commands' => $this->normalizeCommands($commands),
            'enable_welcome_message' => $enableWelcomeMessage,
            'prompts' => $this->normalizePrompts($prompts),
        ];

        $data = $this->connector->request('POST', '/conversational_automation', ['json' => $payload], '配置会话自动化');

        return new SetConversationalAutomationResponse($data);
    }

    /**
     * 构造单个命令条目（command_name + command_description）
     *
     * @return array ['command_name' => ..., 'command_description' => ...]
     */
    public static function command(string $name, string $description): array
    {
        return [
            'command_name' => $name,
            'command_description' => $description,
        ];
    }

    /**
     * 校验命令列表结构（每项必须包含非空的 command_name / command_description）
     */
    private function normalizeCommands(array $commands): array
    {
        $normalized = [];
        foreach ($commands as $index => $command) {
            if (!is_array($command) || trim((string) ($command['command_name'] ?? '')) === '') {
                throw new \InvalidArgumentException(sprintf('commands[%s].command_name 不能为空', $index));
            }
            if (trim((string) ($command['command_description'] ?? '')) === '') {
                throw new \InvalidArgumentException(sprintf('commands[%s].command_description 不能为空', $index));
            }

            $normalized[] = [
                'command_name' => (string) $command['command_name'],
                'command_description' => (string) $command['command_description'],
            ];
        }

        return $normalized;
    }

    /**
     * 校验提示语列表（每项必须为非空字符串）
     */
    private function normalizePrompts(array $prompts): array
    {
        $normalized = [];
        foreach ($prompts as $index => $prompt) {
            if (!is_string($prompt) || trim($prompt) === '') {
                throw new \InvalidArgumentException(sprintf('prompts[%s] 必须为非空字符串', $index));
            }

            $normalized[] = $prompt;
        }

        return $normalized;
    }
}
