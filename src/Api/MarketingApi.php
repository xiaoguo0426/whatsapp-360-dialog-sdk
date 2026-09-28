<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\MarketingDatasetResponse;
use Dialog360\Response\MarketingEventsResponse;
use Dialog360\Response\MarketingMessageResponse;
use Dialog360\Response\MarketingReachEstimateResponse;
use Dialog360\Response\MarketingTemplateListResponse;

/**
 * 营销消息 API 域：/marketing_messages 与 /marketing/*
 *
 * - POST /marketing_messages：发送营销消息（模板消息 + 出价设置）；
 *   另有备用路由 POST /marketing/marketing_messages（文档描述为等价）
 * - GET/POST /marketing/dataset：查询 / 创建转化数据集（WABA 自动推导）
 * - GET/POST /marketing/{meta_node_id}/dataset：按 WABA/Page/IG 节点操作数据集
 * - GET /marketing/dataset_quality：数据集质量指标
 * - GET /marketing/message_templates、GET /marketing/{template_id}：模板查询
 * - GET /marketing/reachestimate：触达估算
 * - GET /marketing/template_analytics、POST enable=true：模板分析数据 / 开启跟踪
 * - GET /marketing/{ad_object_id}/insights：广告对象洞察
 * - POST /marketing/{dataset_id}/events：Conversions API 事件上报（单次最多 1000 条）
 *
 * 发送前应确认模板已获批准（未批准时 API 返回 403）。
 *
 * @see https://docs.360dialog.com/docs/messaging-api/api-reference/marketing-messages
 */
readonly class MarketingApi
{
    /** 触达估算 date_interval 允许取值 */
    public const DATE_INTERVALS = ['L1D', 'L7D', 'L14D', 'L28D'];

    /** 单次事件上报数量上限 */
    public const MAX_EVENTS_PER_REQUEST = 1000;

    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 发送营销消息（Cloud API: POST /marketing_messages）
     *
     * 4xx 客户端错误（模板未批准、参数错误等）不抛异常，返回 isSuccess=false 的
     * MarketingMessageResponse，与 messages()->send() 行为一致。
     *
     * @param string $to 接收者手机号（wa_id）
     * @param array $template 模板定义：['name' => ..., 'language' => 'en'|['code' => 'en'],
     *                        'components' => [...]]，language 传字符串时自动包装为 ['code' => ...]
     * @param float|null $bidMultiplier 每条消息出价倍数（默认 1，建议在模板上设置价格）
     * @param array $options 可选透传字段：recipient（BSUID，传入时取代 to）、
     *                       recipient_type、product_policy、message_activity_sharing
     * @return MarketingMessageResponse
     * @throws Dialog360Exception 5xx/网络错误重试耗尽
     */
    public function send(string $to, array $template, ?float $bidMultiplier = null, array $options = []): MarketingMessageResponse
    {
        if (!isset($template['name']) || trim((string) $template['name']) === '') {
            throw new \InvalidArgumentException('template.name 不能为空');
        }
        if (!isset($template['language']) || $template['language'] === '' || $template['language'] === []) {
            throw new \InvalidArgumentException('template.language 不能为空');
        }
        if (trim($to) === '' && !isset($options['recipient'])) {
            throw new \InvalidArgumentException('to 不能为空（或通过 options["recipient"] 传入 BSUID）');
        }

        $templatePayload = [
            'name' => (string) $template['name'],
            'language' => is_array($template['language']) ? $template['language'] : ['code' => (string) $template['language']],
        ];
        if (isset($template['components']) && is_array($template['components'])) {
            $templatePayload['components'] = $template['components'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'type' => 'template',
            'template' => $templatePayload,
        ];

        if (isset($options['recipient'])) {
            $payload['recipient'] = $options['recipient'];
        } else {
            $payload['to'] = $to;
        }

        foreach (['recipient_type', 'product_policy', 'message_activity_sharing'] as $passThrough) {
            if (array_key_exists($passThrough, $options)) {
                $payload[$passThrough] = $options[$passThrough];
            }
        }

        if ($bidMultiplier !== null) {
            $payload['bid_spec'] = ['per_message_bid_multiplier' => $bidMultiplier];
        }

        try {
            $data = $this->connector->request('POST', '/marketing_messages', ['json' => $payload], '发送营销消息');
        } catch (Dialog360ClientError $e) {
            return new MarketingMessageResponse($e->getResponseBody());
        }

        return new MarketingMessageResponse($data);
    }

    /**
     * 分页查询营销消息模板（Cloud API: GET /marketing/message_templates）
     *
     * @return MarketingTemplateListResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function listTemplates(?int $limit = null, ?string $after = null, ?string $before = null): MarketingTemplateListResponse
    {
        $query = array_filter([
            'limit' => $limit,
            'after' => $after,
            'before' => $before,
        ], static fn ($value) => $value !== null);

        $data = $this->connector->get('/marketing/message_templates', ['query' => $query], '查询营销消息模板');
        return new MarketingTemplateListResponse($data);
    }

    /**
     * 按 ID 获取单个模板（Cloud API: GET /marketing/{template_id}）
     *
     * @return array 原始响应数据（模板对象字段由 API 定义）
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getTemplate(string $templateId): array
    {
        if (trim($templateId) === '') {
            throw new \InvalidArgumentException('templateId 不能为空');
        }

        return $this->connector->get('/marketing/' . rawurlencode($templateId), [], '获取营销消息模板');
    }

    /**
     * 查询当前 WABA 的转化数据集（Cloud API: GET /marketing/dataset）
     *
     * @return MarketingDatasetResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getDataset(): MarketingDatasetResponse
    {
        $data = $this->connector->get('/marketing/dataset', [], '查询转化数据集');
        return new MarketingDatasetResponse($data);
    }

    /**
     * 创建转化数据集（Cloud API: POST /marketing/dataset）
     *
     * 已存在数据集时返回既有 dataset_id。
     *
     * @return MarketingDatasetResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function createDataset(string $name): MarketingDatasetResponse
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('name 不能为空');
        }

        $data = $this->connector->request('POST', '/marketing/dataset', ['json' => ['name' => $name]], '创建转化数据集');
        return new MarketingDatasetResponse($data);
    }

    /**
     * 按节点（WABA / Page / IG ID）查询数据集（Cloud API: GET /marketing/{meta_node_id}/dataset）
     *
     * @return MarketingDatasetResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getDatasetFor(string $metaNodeId): MarketingDatasetResponse
    {
        $this->assertMetaNodeId($metaNodeId);

        $data = $this->connector->get('/marketing/' . rawurlencode($metaNodeId) . '/dataset', [], '查询转化数据集');
        return new MarketingDatasetResponse($data);
    }

    /**
     * 按节点（WABA / Page / IG ID）创建数据集（Cloud API: POST /marketing/{meta_node_id}/dataset）
     *
     * @return MarketingDatasetResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function createDatasetFor(string $metaNodeId, string $name): MarketingDatasetResponse
    {
        $this->assertMetaNodeId($metaNodeId);
        if (trim($name) === '') {
            throw new \InvalidArgumentException('name 不能为空');
        }

        $data = $this->connector->request(
            'POST',
            '/marketing/' . rawurlencode($metaNodeId) . '/dataset',
            ['json' => ['name' => $name]],
            '创建转化数据集'
        );

        return new MarketingDatasetResponse($data);
    }

    /**
     * 查询数据集质量指标（Cloud API: GET /marketing/dataset_quality）
     *
     * @param string $datasetId 数据集 ID（必填）
     * @param string|null $agentName 按代理名称过滤
     * @param array|null $fields 需要返回的字段列表（逗号拼接传给 API）
     * @return array 原始响应数据（指标结构由 API 定义）
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getDatasetQuality(string $datasetId, ?string $agentName = null, ?array $fields = null): array
    {
        if (trim($datasetId) === '') {
            throw new \InvalidArgumentException('datasetId 不能为空');
        }

        $query = ['dataset_id' => $datasetId];
        if ($agentName !== null) {
            $query['agent_name'] = $agentName;
        }
        if ($fields !== null && $fields !== []) {
            $query['fields'] = implode(',', $fields);
        }

        return $this->connector->get('/marketing/dataset_quality', ['query' => $query], '查询数据集质量');
    }

    /**
     * 触达估算（Cloud API: GET /marketing/reachestimate）
     *
     * @param array $targetingSpec 定向配置对象，如 ['geo_locations' => ['countries' => ['BR']]]，
     *                             以 JSON 编码后作为 targeting_spec 查询参数发送
     * @param string|null $dateInterval 统计区间（L1D / L7D / L14D / L28D），null 表示不指定
     * @return MarketingReachEstimateResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getReachEstimate(array $targetingSpec, ?string $dateInterval = null): MarketingReachEstimateResponse
    {
        if ($targetingSpec === []) {
            throw new \InvalidArgumentException('targetingSpec 不能为空');
        }
        if ($dateInterval !== null && !in_array($dateInterval, self::DATE_INTERVALS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'dateInterval 取值"%s"不合法，允许的取值：%s',
                $dateInterval,
                implode(', ', self::DATE_INTERVALS)
            ));
        }

        $query = ['targeting_spec' => json_encode($targetingSpec)];
        if ($dateInterval !== null) {
            $query['date_interval'] = $dateInterval;
        }

        $data = $this->connector->get('/marketing/reachestimate', ['query' => $query], '获取触达估算');
        return new MarketingReachEstimateResponse($data);
    }

    /**
     * 查询模板分析数据（Cloud API: GET /marketing/template_analytics）
     *
     * @param array $params 额外查询参数（如日期区间等，透传给 API）
     * @return array 原始响应数据（指标结构由 API 定义）
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getTemplateAnalytics(array $params = []): array
    {
        return $this->connector->get('/marketing/template_analytics', ['query' => $params], '查询模板分析');
    }

    /**
     * 开启模板分析跟踪（Cloud API: POST /marketing/template_analytics?enable=true）
     *
     * @return array 原始响应数据
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function enableTemplateAnalytics(): array
    {
        return $this->connector->request(
            'POST',
            '/marketing/template_analytics',
            ['query' => ['enable' => 'true']],
            '开启模板分析跟踪'
        );
    }

    /**
     * 广告对象洞察（Cloud API: GET /marketing/{ad_object_id}/insights）
     *
     * @param array $params 查询参数透传（fields、date_preset、level、breakdowns 等）
     * @return array 原始响应数据（指标结构由 API 定义）
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getInsights(string $adObjectId, array $params = []): array
    {
        if (trim($adObjectId) === '') {
            throw new \InvalidArgumentException('adObjectId 不能为空');
        }

        return $this->connector->get('/marketing/' . rawurlencode($adObjectId) . '/insights', ['query' => $params], '获取广告对象洞察');
    }

    /**
     * 上报 Conversions API 事件（Cloud API: POST /marketing/{dataset_id}/events）
     *
     * @param string $datasetId 数据集 ID（来自 getDataset()/createDataset()）
     * @param array $events 事件列表（Conversions API 事件格式），单次最多 1000 条
     * @return MarketingEventsResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function sendEvents(string $datasetId, array $events): MarketingEventsResponse
    {
        if (trim($datasetId) === '') {
            throw new \InvalidArgumentException('datasetId 不能为空');
        }
        if ($events === []) {
            throw new \InvalidArgumentException('events 不能为空');
        }
        if (count($events) > self::MAX_EVENTS_PER_REQUEST) {
            throw new \InvalidArgumentException(sprintf('单次最多上报 %d 条事件', self::MAX_EVENTS_PER_REQUEST));
        }

        $data = $this->connector->request(
            'POST',
            '/marketing/' . rawurlencode($datasetId) . '/events',
            ['json' => ['data' => $events]],
            '上报转化事件'
        );

        return new MarketingEventsResponse($data);
    }

    /**
     * 校验 meta_node_id 非空
     */
    private function assertMetaNodeId(string $metaNodeId): void
    {
        if (trim($metaNodeId) === '') {
            throw new \InvalidArgumentException('metaNodeId 不能为空');
        }
    }
}
