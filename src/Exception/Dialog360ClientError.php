<?php

namespace Dialog360\Exception;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * 4xx 客户端错误：API 拒绝了请求（参数错误、号码无效、鉴权失败等）。
 *
 * 与暂时性的 5xx/网络错误不同，此类错误重试无意义，由传输层立即抛出。
 * 继承 Dialog360Exception，已有的 catch (Dialog360Exception) 逻辑不受影响；
 * 上层可按需捕获本类型做特殊处理（如 MessagesApi 转换为 isSuccess=false 的响应对象）。
 */
class Dialog360ClientError extends Dialog360Exception
{
    private int $statusCode;
    private array $responseBody;

    public function __construct(string $message, int $statusCode, array $responseBody = [], ?\Exception $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    /**
     * 从 4xx 响应构建客户端错误异常
     */
    public static function fromResponse(ResponseInterface $response, RequestException $previous): self
    {
        $data = json_decode((string)$response->getBody(), true);
        $responseBody = is_array($data) ? $data : [];
        $statusCode = $response->getStatusCode();

        return new self(self::buildMessage($statusCode, $responseBody), $statusCode, $responseBody, $previous);
    }

    /**
     * HTTP 状态码
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * API 返回的错误体（如 {"errors":[{"code":...,"message":...}]}），供上层解析
     */
    public function getResponseBody(): array
    {
        return $this->responseBody;
    }

    private static function buildMessage(int $statusCode, array $responseBody): string
    {
        $apiMessage = null;
        if (isset($responseBody['error']['message']) && is_string($responseBody['error']['message'])) {
            $apiMessage = $responseBody['error']['message'];
        } elseif (isset($responseBody['errors'][0]['message']) && is_string($responseBody['errors'][0]['message'])) {
            $apiMessage = $responseBody['errors'][0]['message'];
        } elseif (isset($responseBody['message']) && is_string($responseBody['message'])) {
            $apiMessage = $responseBody['message'];
        }

        return "API 客户端错误 (HTTP {$statusCode})" . ($apiMessage !== null ? ": {$apiMessage}" : '');
    }
}
