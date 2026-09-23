<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;

/**
 * 健康状态 API 域：/health_status
 */
readonly class HealthApi
{
    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 获取消息发送健康状态（Cloud API）
     * @return array
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function status(): array
    {
        return $this->connector->get('/health_status', [], '获取健康状态');
    }
}
