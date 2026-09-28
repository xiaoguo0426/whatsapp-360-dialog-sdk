<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\MessageTemplateArchiveResponse;
use Dialog360\Response\MessageTemplateCompareResponse;
use Dialog360\Response\MessageTemplateCreateResponse;
use Dialog360\Response\MessageTemplateListResponse;
use Dialog360\Response\MessageTemplateOperationResponse;
use Dialog360\Response\MessageTemplateResponse;
use Dialog360\Response\TemplateMessageResponse;

/**
 * 模板 API 域：
 * - 旧端点 /v1/configs/templates（已废弃，保留 list() 以向后兼容）
 * - 在用端点 /message_templates 与 /message_template_library
 *
 * 模板创建后需经审批（status=APPROVED）方可用于发送。
 *
 * @see https://docs.360dialog.com/docs/messaging-api/api-reference/templates
 */
readonly class TemplateApi
{
    /** 批量删除 / 归档 / 恢复的单次数量上限 */
    public const MAX_BATCH_IDS = 100;

    /** 创建模板时 options 允许的附加字段 */
    public const CREATE_OPTIONS = [
        'sub_category',
        'allow_category_change',
        'parameter_format',
        'display_format',
        'send_type',
        'message_send_ttl_seconds',
        'cta_url_link_tracking_opted_out',
        'is_primary_device_delivery_only',
        'library_template_name',
        'library_template_body_inputs',
        'library_template_button_inputs',
        'optimization_spec',
    ];

    /** 编辑模板时允许更新的字段 */
    public const UPDATABLE_FIELDS = [
        'category',
        'components',
        'allow_category_change',
        'cta_url_link_tracking_opted_out',
        'display_format',
        'message_send_ttl_seconds',
        'optimization_spec',
        'parameter_format',
        'sub_category',
    ];

    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 获取可用的模板列表（旧端点 GET /v1/configs/templates，官方已标记废弃）
     *
     * @param array $filters
     * @param string $sort
     * @param int $offset
     * @param int $limit
     * @return TemplateMessageResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     * @deprecated 请使用 listMessageTemplates()（GET /message_templates）
     */
    public function list(array $filters = [], string $sort = '', int $offset = 0, int $limit = 1000): TemplateMessageResponse
    {
        $query = [
            'offset' => $offset,
            'limit' => $limit
        ];
        if ($filters) {
            $query['filters'] = json_encode($filters);
        }
        if ($sort) {
            $query['sort'] = $sort;
        }

        $data = $this->connector->get('/v1/configs/templates', ['query' => $query], '获取模板');
        return new TemplateMessageResponse($data);
    }

    /**
     * 分页查询模板列表（Cloud API: GET /message_templates，含状态/分类/质量分等）
     *
     * @param int|null $limit 单次返回条数
     * @param string|null $after 向后翻页游标（上次响应的 getAfterCursor()）
     * @param string|null $before 向前翻页游标（上次响应的 getBeforeCursor()）
     * @param array|null $fields 需要返回的字段列表（逗号拼接传给 API）
     * @return MessageTemplateListResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function listMessageTemplates(?int $limit = null, ?string $after = null, ?string $before = null, ?array $fields = null): MessageTemplateListResponse
    {
        $query = array_filter([
            'limit' => $limit,
            'after' => $after,
            'before' => $before,
        ], static fn ($value) => $value !== null);
        if ($fields !== null && $fields !== []) {
            $query['fields'] = implode(',', $fields);
        }

        $data = $this->connector->get('/message_templates', ['query' => $query], '查询模板列表');
        return new MessageTemplateListResponse($data);
    }

    /**
     * 获取 WABA 可用的模板库（Cloud API: GET /message_template_library）
     *
     * @return MessageTemplateListResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getTemplateLibrary(): MessageTemplateListResponse
    {
        $data = $this->connector->get('/message_template_library', [], '获取模板库');
        return new MessageTemplateListResponse($data);
    }

    /**
     * 按 ID 获取模板详情（Cloud API: GET /message_templates/{template_id}）
     *
     * @param string $templateId 模板 ID
     * @param array|null $fields 需要返回的字段列表（如 id,name,status,components）
     * @return MessageTemplateResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function get(string $templateId, ?array $fields = null): MessageTemplateResponse
    {
        $this->assertTemplateId($templateId);

        $options = [];
        if ($fields !== null && $fields !== []) {
            $options['query'] = ['fields' => implode(',', $fields)];
        }

        $data = $this->connector->get('/message_templates/' . rawurlencode($templateId), $options, '获取模板详情');
        return new MessageTemplateResponse($data);
    }

    /**
     * 创建模板（Cloud API: POST /message_templates）
     *
     * @param string $name 模板名称（小写字母/数字/下划线）
     * @param string $language 模板语言（如 en_US、zh_CN）
     * @param string $category 模板分类（如 MARKETING / UTILITY / AUTHENTICATION）
     * @param array $components 组件列表（header/body/buttons 等，结构见官方文档）
     * @param array $options 附加字段（键见 TemplateApi::CREATE_OPTIONS），如
     *                       allow_category_change、parameter_format、library_template_name 等
     * @return MessageTemplateCreateResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function create(string $name, string $language, string $category, array $components, array $options = []): MessageTemplateCreateResponse
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('name 不能为空');
        }
        if (trim($language) === '') {
            throw new \InvalidArgumentException('language 不能为空');
        }
        if (trim($category) === '') {
            throw new \InvalidArgumentException('category 不能为空');
        }
        if ($components === []) {
            throw new \InvalidArgumentException('components 不能为空');
        }

        $unknown = array_diff(array_keys($options), self::CREATE_OPTIONS);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf(
                'options 含未知字段"%s"，允许的字段：%s',
                implode('", "', $unknown),
                implode(', ', self::CREATE_OPTIONS)
            ));
        }

        $payload = [
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'components' => $components,
        ];
        foreach ($options as $key => $value) {
            $payload[$key] = $value;
        }

        $data = $this->connector->request('POST', '/message_templates', ['json' => $payload], '创建模板');
        return new MessageTemplateCreateResponse($data);
    }

    /**
     * 编辑模板（Cloud API: POST /message_templates/{template_id}，仅 APPROVED/REJECTED 可编辑）
     *
     * @param string $templateId 模板 ID
     * @param array $fields 要更新的字段（键见 TemplateApi::UPDATABLE_FIELDS），如
     *                      ['category' => 'MARKETING', 'components' => [...]]
     * @return MessageTemplateOperationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function update(string $templateId, array $fields): MessageTemplateOperationResponse
    {
        $this->assertTemplateId($templateId);
        if ($fields === []) {
            throw new \InvalidArgumentException('至少提供一个要更新的字段');
        }

        $unknown = array_diff(array_keys($fields), self::UPDATABLE_FIELDS);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf(
                'fields 含未知字段"%s"，允许的字段：%s',
                implode('", "', $unknown),
                implode(', ', self::UPDATABLE_FIELDS)
            ));
        }

        $data = $this->connector->request(
            'POST',
            '/message_templates/' . rawurlencode($templateId),
            ['json' => $fields],
            '编辑模板'
        );

        return new MessageTemplateOperationResponse($data);
    }

    /**
     * 按名称删除模板（删除其全部语言版本，Cloud API: DELETE /message_templates?name=...）
     *
     * @return MessageTemplateOperationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function deleteByName(string $name): MessageTemplateOperationResponse
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('name 不能为空');
        }

        $data = $this->connector->request('DELETE', '/message_templates', ['query' => ['name' => $name]], '删除模板');
        return new MessageTemplateOperationResponse($data);
    }

    /**
     * 按 ID 删除模板（仅删除单个语言版本，Cloud API: DELETE /message_templates?hsm_id=...）
     *
     * @return MessageTemplateOperationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function deleteById(string $templateId): MessageTemplateOperationResponse
    {
        $this->assertTemplateId($templateId);

        $data = $this->connector->request('DELETE', '/message_templates', ['query' => ['hsm_id' => $templateId]], '删除模板');
        return new MessageTemplateOperationResponse($data);
    }

    /**
     * 按 ID 批量删除模板（Cloud API: DELETE /message_templates?hsm_ids=...，单次最多 100 个）
     *
     * @param array $templateIds 模板 ID 列表
     * @return MessageTemplateOperationResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function deleteByIds(array $templateIds): MessageTemplateOperationResponse
    {
        $ids = $this->normalizeBatchIds($templateIds, '删除模板');

        $data = $this->connector->request(
            'DELETE',
            '/message_templates',
            ['query' => ['hsm_ids' => json_encode($ids)]],
            '批量删除模板'
        );

        return new MessageTemplateOperationResponse($data);
    }

    /**
     * 归档模板（Cloud API: POST /message_templates/archive，仅 APPROVED/REJECTED 可归档，
     * 归档 28 天后自动删除，单次最多 100 个）
     *
     * @param array $templateIds 模板 ID 列表
     * @return MessageTemplateArchiveResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function archive(array $templateIds): MessageTemplateArchiveResponse
    {
        return $this->applyArchive('archive', '归档模板', $templateIds);
    }

    /**
     * 恢复归档的模板（28 天窗口内，Cloud API: POST /message_templates/unarchive）
     *
     * @param array $templateIds 模板 ID 列表
     * @return MessageTemplateArchiveResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function unarchive(array $templateIds): MessageTemplateArchiveResponse
    {
        return $this->applyArchive('unarchive', '恢复模板', $templateIds);
    }

    /**
     * 对比模板效果（Cloud API: GET /message_templates/{template_id}/compare，
     * 对比阻塞率 / 发送量 / 主要阻塞原因；模板需同 WABA 且发送量 ≥ 1000）
     *
     * @param string $templateId 基准模板 ID（路径参数）
     * @param array $compareTemplateIds 参与对比的模板 ID 列表
     * @param int $start 起始 UNIX 时间戳（回看 7/30/60/90 天）
     * @param int $end 结束 UNIX 时间戳
     * @return MessageTemplateCompareResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function compare(string $templateId, array $compareTemplateIds, int $start, int $end): MessageTemplateCompareResponse
    {
        $this->assertTemplateId($templateId);
        if ($compareTemplateIds === []) {
            throw new \InvalidArgumentException('compareTemplateIds 不能为空');
        }
        if ($start <= 0 || $end <= 0) {
            throw new \InvalidArgumentException('start/end 必须为有效的 UNIX 时间戳');
        }

        $query = [
            'template_ids' => json_encode($this->normalizeBatchIds($compareTemplateIds, '对比模板')),
            'start' => $start,
            'end' => $end,
        ];

        $data = $this->connector->get('/message_templates/' . rawurlencode($templateId) . '/compare', ['query' => $query], '对比模板');
        return new MessageTemplateCompareResponse($data);
    }

    /**
     * archive/unarchive 的统一实现（同构请求体，仅路径与动作不同）
     *
     * @throws Dialog360Exception
     */
    private function applyArchive(string $action, string $actionLabel, array $templateIds): MessageTemplateArchiveResponse
    {
        $ids = $this->normalizeBatchIds($templateIds, $actionLabel);

        $data = $this->connector->request('POST', '/message_templates/' . $action, ['json' => ['hsm_ids' => $ids]], $actionLabel);
        return new MessageTemplateArchiveResponse($data);
    }

    /**
     * 校验并规范化批量模板 ID 列表（非空字符串数组，单次最多 100 个）
     *
     * @return string[]
     */
    private function normalizeBatchIds(array $templateIds, string $action): array
    {
        if ($templateIds === []) {
            throw new \InvalidArgumentException($action . '：templateIds 不能为空');
        }
        if (count($templateIds) > self::MAX_BATCH_IDS) {
            throw new \InvalidArgumentException(sprintf('%s：单次最多处理 %d 个模板', $action, self::MAX_BATCH_IDS));
        }

        $normalized = [];
        foreach ($templateIds as $index => $id) {
            if (!is_scalar($id) || trim((string) $id) === '') {
                throw new \InvalidArgumentException(sprintf('%s：templateIds[%s] 必须为非空字符串', $action, $index));
            }

            $normalized[] = (string) $id;
        }

        return $normalized;
    }

    /**
     * 校验 templateId 非空
     */
    private function assertTemplateId(string $templateId): void
    {
        if (trim($templateId) === '') {
            throw new \InvalidArgumentException('templateId 不能为空');
        }
    }
}
