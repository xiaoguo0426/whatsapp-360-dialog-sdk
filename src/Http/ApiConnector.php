<?php

namespace Dialog360\Http;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * 传输层：统一重试语义、错误分类与 JSON 解析。
 *
 * - 4xx 客户端错误 → 立即抛 Dialog360ClientError（不重试）
 * - 5xx / 网络错误 → 指数退避重试，耗尽后抛 Dialog360Exception
 *
 * 重试循环只在此处出现一次，各 Api 域类不再各自实现。
 */
class ApiConnector
{
    public function __construct(
        private Client $httpClient,
        private int $retryAttempts
    ) {
    }

    /**
     * 带重试的请求（所有可变操作的统一入口）
     *
     * @param string $action 动作描述，用于异常消息（如"发送消息"）
     * @return array 解析后的 JSON 响应体
     * @throws Dialog360ClientError 4xx 客户端错误（携带 API 错误响应体，不重试）
     * @throws Dialog360Exception 5xx/网络错误重试耗尽，或非请求类网络故障
     */
    public function request(string $method, string $uri, array $options, string $action): array
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryAttempts) {
            try {
                $response = $this->httpClient->request($method, $uri, $options);
                return $this->decodeBody($response);

            } catch (RequestException $e) {
                // 4xx 客户端错误（如参数错误、鉴权失败）重试无意义，立即抛出
                $errorResponse = $e->getResponse();
                if ($errorResponse !== null && $errorResponse->getStatusCode() < 500) {
                    throw Dialog360ClientError::fromResponse($errorResponse, $e);
                }

                // 5xx 或网络错误（无响应）属于暂时性错误，重试
                $lastException = $e;
                $attempts++;

                if ($attempts >= $this->retryAttempts) {
                    break;
                }

                // 指数退避：等待 2^attempts 秒后重试
                sleep((int) pow(2, $attempts));
            } catch (GuzzleException $e) {
                throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new Dialog360Exception(
            $action . '失败，已重试' . $this->retryAttempts . '次: ' . ($lastException?->getMessage() ?? '未知错误'),
            0,
            $lastException
        );
    }

    /**
     * 不重试的 GET（幂等查询）
     *
     * @param string $action 动作描述，用于异常消息（如"获取健康状态"）
     * @return array 解析后的 JSON 响应体
     * @throws Dialog360ClientError 4xx 客户端错误
     * @throws Dialog360Exception 5xx/网络错误
     */
    public function get(string $uri, array $options, string $action): array
    {
        try {
            $response = $this->httpClient->get($uri, $options);
            return $this->decodeBody($response);
        } catch (RequestException $e) {
            $errorResponse = $e->getResponse();
            if ($errorResponse !== null && $errorResponse->getStatusCode() < 500) {
                throw Dialog360ClientError::fromResponse($errorResponse, $e);
            }
            throw new Dialog360Exception($action . '失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 原始请求（媒体下载等二进制场景），Guzzle 异常原样透出
     *
     * @throws GuzzleException
     */
    public function getRaw(string $uri): ResponseInterface
    {
        return $this->httpClient->get($uri);
    }

    private function decodeBody(ResponseInterface $response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);
        return is_array($data) ? $data : [];
    }
}
