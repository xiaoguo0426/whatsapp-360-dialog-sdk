<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\TemplateMessageResponse;

/**
 * 模板 API 域：/v1/configs/templates
 */
readonly class TemplateApi
{
    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 获取可用的模板列表
     * @param array $filters
     * @param string $sort
     * @param int $offset
     * @param int $limit
     * @return TemplateMessageResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
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
}
