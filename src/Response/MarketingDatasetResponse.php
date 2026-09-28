<?php

namespace Dialog360\Response;

/**
 * GET / POST /marketing/dataset（及 /marketing/{meta_node_id}/dataset）响应（转化数据集）。
 *
 * 响应体形如：{"id": "<dataset_id>", "name": "..."}
 */
class MarketingDatasetResponse
{
    private array $data;

    private string $datasetId;

    private string $name;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->datasetId = (string) ($data['id'] ?? '');
        $this->name = (string) ($data['name'] ?? '');
    }

    /**
     * 获取 dataset_id（配合 Conversions API 事件上报使用）
     */
    public function getDatasetId(): string
    {
        return $this->datasetId;
    }

    /**
     * 获取数据集名称
     */
    public function getName(): string
    {
        return $this->name;
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
            'dataset_id' => $this->datasetId,
            'name' => $this->name,
        ];
    }
}
