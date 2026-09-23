<?php

namespace Dialog360;

use Dialog360\Api\HealthApi;
use Dialog360\Api\MediaApi;
use Dialog360\Api\MessagesApi;
use Dialog360\Api\TemplateApi;
use Dialog360\Api\WebhookApi;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Message\MessageInterface;
use Dialog360\Response\MediaResponse;
use Dialog360\Response\MessageResponse;
use Dialog360\Response\PhoneNumberWebhookResponse;
use Dialog360\Response\SetWabaWebhookUrlResponse;
use Dialog360\Response\TemplateMessageResponse;
use Dialog360\Response\WabaWebhookResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;

/**
 * 360dialog WhatsApp Cloud API 客户端（门面）。
 *
 * 推荐通过域访问器使用：
 *   $client->messages()->send($message);
 *   $client->media()->upload($filePath, $mimeType);
 *   $client->webhook()->setUrl('https://...');
 *   $client->templates()->list();
 *   $client->health()->status();
 *
 * 旧的扁平方法（sendMessage/uploadMedia/...）保留为向后兼容的一行委托，已标注 @deprecated。
 */
class Dialog360Client
{
    private string $apiKey;
    private string $phoneNumberId;
    private string $baseUrl;
    private int $timeout;
    private int $retryAttempts;
    private ApiConnector $connector;

    private ?MessagesApi $messagesApi = null;
    private ?MediaApi $mediaApi = null;
    private ?WebhookApi $webhookApi = null;
    private ?TemplateApi $templateApi = null;
    private ?HealthApi $healthApi = null;

    public function __construct(
        string        $apiKey,
        string        $phoneNumberId,
        string        $baseUrl = 'https://waba-v2.360dialog.io',
        int           $timeout = 30,
        int           $retryAttempts = 3,
        ?HandlerStack $handlerStack = null
    )
    {
        $this->apiKey = $apiKey;
        $this->phoneNumberId = $phoneNumberId;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;

        $clientOptions = [
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'D360-API-KEY' => $this->apiKey,
                // JSON requests will set Content-Type automatically when using 'json' option
            ]
        ];

        if ($handlerStack !== null) {
            $clientOptions['handler'] = $handlerStack;
        }

        $this->connector = new ApiConnector(new Client($clientOptions), $this->retryAttempts);
    }

    /** ===== 域访问器（新用法入口） ===== */

    /**
     * 消息 API 域（发送各类消息）
     */
    public function messages(): MessagesApi
    {
        return $this->messagesApi ??= new MessagesApi($this->connector);
    }

    /**
     * 媒体 API 域（上传/查询/下载/删除媒体文件）
     */
    public function media(): MediaApi
    {
        return $this->mediaApi ??= new MediaApi($this->connector);
    }

    /**
     * Webhook 配置 API 域（电话号码级 / WABA 级）
     */
    public function webhook(): WebhookApi
    {
        return $this->webhookApi ??= new WebhookApi($this->connector);
    }

    /**
     * 模板 API 域（查询模板列表）
     */
    public function templates(): TemplateApi
    {
        return $this->templateApi ??= new TemplateApi($this->connector);
    }

    /**
     * 健康状态 API 域
     */
    public function health(): HealthApi
    {
        return $this->healthApi ??= new HealthApi($this->connector);
    }

    /** ===== 向后兼容层（@deprecated，委托实现） ===== */

    /**
     * 发送消息
     *
     * @param MessageInterface $message
     * @return MessageResponse
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->messages()->send($message)
     */
    public function sendMessage(MessageInterface $message): MessageResponse
    {
        return $this->messages()->send($message);
    }

    /**
     * 获取消息发送健康状态（Cloud API）
     *
     * @return array
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->health()->status()
     */
    public function getHealthStatus(): array
    {
        return $this->health()->status();
    }

    /**
     * 上传媒体文件（Cloud API: POST /media）
     *
     * @param string $filePath 本地文件路径
     * @param string $mimeType MIME类型
     * @return string 返回媒体ID
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->media()->upload($filePath, $mimeType)
     */
    public function uploadMedia(string $filePath, string $mimeType): string
    {
        return $this->media()->upload($filePath, $mimeType);
    }

    /**
     * 获取媒体文件信息（Cloud API: GET /{media-id}）
     *
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->media()->getInfo($mediaId)
     */
    public function getMediaInfo(string $mediaId): MediaResponse
    {
        return $this->media()->getInfo($mediaId);
    }

    /**
     * 通过下载URL直接下载媒体文件并保存
     *
     * @throws Dialog360Exception
     * @throws GuzzleException
     * @deprecated 请使用 $client->media()->downloadByUrl($downloadUrl, $savePath)
     */
    public function downloadMediaFile(string $downloadUrl, string $savePath): bool
    {
        return $this->media()->downloadByUrl($downloadUrl, $savePath);
    }

    /**
     * 删除媒体文件（Cloud API: DELETE /{media-id}）
     *
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->media()->delete($mediaId)
     */
    public function deleteMedia(string $mediaId): bool
    {
        return $this->media()->delete($mediaId);
    }

    /**
     * 下载媒体文件（Cloud API 两步：先取URL，再通过 v2 根域下载）
     *
     * @deprecated 请使用 $client->media()->download($mediaId, $savePath)
     */
    public function downloadMedia(string $mediaId, ?string $savePath = null): string
    {
        return $this->media()->download($mediaId, $savePath);
    }

    /**
     * 获取电话号码信息（Cloud API 暂无对应Messaging端点）
     *
     * @deprecated Cloud API 暂不支持，请使用 Meta Graph API 或 360dialog Hub
     */
    public function getPhoneNumberInfo(): array
    {
        throw new Dialog360Exception('Cloud API 暂不支持通过 Messaging API 获取电话号码信息，请使用 Meta Graph API 或 360dialog Hub。');
    }

    /**
     * 获取可用的模板（Cloud API 暂无 Messaging 端点）
     *
     * @deprecated 请使用 $client->templates()->list()
     */
    public function getTemplates(array $filters = [], string $sort = '', int $offset = 0, int $limit = 1000): TemplateMessageResponse
    {
        return $this->templates()->list($filters, $sort, $offset, $limit);
    }

    /**
     * 获取API密钥信息（Cloud API 暂无 Messaging 端点）
     *
     * @deprecated Cloud API 暂不支持，请在 360dialog Hub 查看
     */
    public function getApiKeyInfo(): array
    {
        throw new Dialog360Exception('Cloud API 暂不支持通过 Messaging API 获取API密钥信息，请在 360dialog Hub 查看。');
    }

    /**
     * 获取电话号码Webhook URL（Cloud API: GET /v1/configs/webhook）
     *
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->webhook()->getUrl()
     */
    public function getWebhookUrl(): PhoneNumberWebhookResponse
    {
        return $this->webhook()->getUrl();
    }

    /**
     * 设置电话号码Webhook URL（Cloud API: POST /v1/configs/webhook）
     *
     * @param string $webhook_url
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->webhook()->setUrl($webhook_url)
     */
    public function setWebhookUrl($webhook_url): PhoneNumberWebhookResponse
    {
        return $this->webhook()->setUrl((string) $webhook_url);
    }

    /**
     * 获取WABA级Webhook配置（Cloud API: GET /waba_webhook）
     *
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->webhook()->getWabaUrl()
     */
    public function getWabaWebhookUrl(): WabaWebhookResponse
    {
        return $this->webhook()->getWabaUrl();
    }

    /**
     * 设置WABA级Webhook（Cloud API: POST /waba_webhook）
     *
     * @throws Dialog360Exception
     * @deprecated 请使用 $client->webhook()->setWabaUrl(...)
     */
    public function setWabaWebhookUrl(string $webhook_url, array $headers = [], bool $override_all = false): SetWabaWebhookUrlResponse
    {
        return $this->webhook()->setWabaUrl($webhook_url, $headers, $override_all);
    }

    /**
     * 获取客户端配置
     */
    public function getConfig(): array
    {
        return [
            'apiKey' => $this->apiKey,
            'phoneNumberId' => $this->phoneNumberId,
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
            'retryAttempts' => $this->retryAttempts
        ];
    }
}