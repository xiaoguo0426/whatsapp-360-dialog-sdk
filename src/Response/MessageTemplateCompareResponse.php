<?php

namespace Dialog360\Response;

/**
 * GET /message_templates/{template_id}/compare 响应（模板效果对比）。
 *
 * 响应体形如：
 * {
 *   "data": [
 *     {
 *       "metric": "BLOCK_RATE",
 *       "type": "RELATIVE",
 *       "number_values": [{"key": "...", "value": ...}],
 *       "string_values": [{"key": "...", "value": "..."}],
 *       "order_by_relative_metric": ...
 *     }
 *   ]
 * }
 */
class MessageTemplateCompareResponse
{
    private array $data;

    private array $metrics;

    public function __construct(array $data)
    {
        $this->data = $data;

        $items = $data['data'] ?? [];
        $this->metrics = is_array($items) ? $items : [];
    }

    /**
     * 获取指标列表，每项形如
     * ['metric' => ..., 'type' => ..., 'number_values' => [...], 'string_values' => [...]]
     * metric 取值：BLOCK_RATE / MESSAGE_SENDS / TOP_BLOCK_REASON
     *
     * @return array
     */
    public function getMetrics(): array
    {
        return $this->metrics;
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
            'metrics' => $this->metrics,
        ];
    }
}
