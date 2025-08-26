<?php

namespace Dialog360;

use Dialog360\Exception\Dialog360Exception;
use Dialog360\Message\MessageInterface;
use Dialog360\Response\MessageResponse;
use Dialog360\Response\MediaResponse;
use Dialog360\Response\PhoneNumberWebhookResponse;
use Dialog360\Response\SetWabaWebhookUrlResponse;
use Dialog360\Response\WabaWebhookResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;

class Dialog360Client
{
    private string $apiKey;
    private string $phoneNumberId;
    private string $baseUrl;
    private int $timeout;
    private int $retryAttempts;
    private Client $httpClient;

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

        $this->httpClient = new Client($clientOptions);
    }

    /**
     * 发送消息
     * @param MessageInterface $message
     * @return MessageResponse
     * @throws Dialog360Exception
     */
    public function sendMessage(MessageInterface $message): MessageResponse
    {
        $payload = $message->toArray();
        $payload['messaging_product'] = 'whatsapp';
        $payload['recipient_type'] = $payload['recipient_type'] ?? 'individual';
        $payload['to'] = $message->getTo();
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryAttempts) {
            try {
                $response = $this->httpClient->post("/messages", [
                    'json' => $payload
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                return new MessageResponse($data);

            } catch (RequestException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts >= $this->retryAttempts) {
                    break;
                }

                // 等待一段时间后重试
                sleep(pow(2, $attempts));
            } catch (GuzzleException $e) {
                throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new Dialog360Exception(
            '发送消息失败，已重试' . $this->retryAttempts . '次: ' . $lastException->getMessage(),
            0,
            $lastException
        );
    }

    /**
     * 获取消息发送健康状态（Cloud API）
     * @return array
     * @throws Dialog360Exception
     */
    public function getHealthStatus(): array
    {
        try {
            $response = $this->httpClient->get('/health_status');
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取健康状态失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取媒体文件信息（Cloud API: GET /{media-id}）
     * @param string $mediaId
     * @return MediaResponse
     * @throws Dialog360Exception
     */
    public function getMediaInfo(string $mediaId): MediaResponse
    {
        try {
            $response = $this->httpClient->get("/{$mediaId}");
            $data = json_decode($response->getBody()->getContents(), true);
            return new MediaResponse($data);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取媒体信息失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 下载媒体文件（Cloud API 两步：先取URL，再通过 v2 根域下载）
     */
    public function downloadMedia(string $mediaId, string $savePath = null): string
    {
        try {
            $mediaInfo = $this->getMediaInfo($mediaId);
            $downloadUrl = $mediaInfo->getUrl();

            // Cloud API 指南：将 lookaside 主机替换为 waba-v2 根域后面的路径
            $parsed = parse_url($downloadUrl);
            if (!$parsed || !isset($parsed['path'])) {
                throw new Dialog360Exception('媒体下载URL无效');
            }
            $path = $parsed['path'] . (isset($parsed['query']) ? ('?' . $parsed['query']) : '');

            // 通过相对路径请求（自动带上 D360-API-KEY 头）
            $response = $this->httpClient->get($path);
            $content = $response->getBody()->getContents();

            if ($savePath) {
                file_put_contents($savePath, $content);
            }

            return $content;
        } catch (RequestException $e) {
            throw new Dialog360Exception('下载媒体文件失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取电话号码信息（Cloud API 暂无对应Messaging端点）
     */
    public function getPhoneNumberInfo(): array
    {
        throw new Dialog360Exception('Cloud API 暂不支持通过 Messaging API 获取电话号码信息，请使用 Meta Graph API 或 360dialog Hub。');
    }

    /**
     * 获取可用的模板（Cloud API 暂无 Messaging 端点）
     */
    public function getTemplates(): array
    {
        throw new Dialog360Exception('Cloud API 暂不支持通过 Messaging API 列出模板，请使用 Meta Graph API 或在 360dialog Hub 管理模板。');
    }

    /**
     * 获取API密钥信息（Cloud API 暂无 Messaging 端点）
     */
    public function getApiKeyInfo(): array
    {
        throw new Dialog360Exception('Cloud API 暂不支持通过 Messaging API 获取API密钥信息，请在 360dialog Hub 查看。');
    }

//    public function setSandboxWebhookUrl(string $url)
//    {
//        $payload = [
//            'url' => $url,
//        ];
//
//        $attempts = 0;
//        $lastException = null;
//var_dump($payload);
//var_dump($this->httpClient);
//die();
//        while ($attempts < $this->retryAttempts) {
//            try {
//                $response = $this->httpClient->post("/v1/configs/webhook", [
//                    'json' => $payload
//                ]);
//
//                $data = json_decode($response->getBody()->getContents(), true);
//                var_dump($data);
////                return new MessageResponse($data);
//die();
//            } catch (RequestException $e) {
//                $lastException = $e;
//                $attempts++;
//
//                if ($attempts >= $this->retryAttempts) {
//                    break;
//                }
//
//                // 等待一段时间后重试
//                sleep(pow(2, $attempts));
//            } catch (GuzzleException $e) {
//                throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
//            }
//        }
//
//        throw new Dialog360Exception(
//            '发送消息失败，已重试' . $this->retryAttempts . '次: ' . $lastException->getMessage(),
//            0,
//            $lastException
//        );
//    }

    /**
     * 获取电话号码Webhook URL（Cloud API: GET /v1/configs/webhook）
     * @return PhoneNumberWebhookResponse
     * @throws Dialog360Exception
     */
    public function getWebhookUrl(): PhoneNumberWebhookResponse
    {
        try {
            $response = $this->httpClient->get("/v1/configs/webhook");
            $data = json_decode($response->getBody()->getContents(), true);
            return new PhoneNumberWebhookResponse($data);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取电话号码Webhook URL: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    public function setWebhookUrl($webhook_url): PhoneNumberWebhookResponse
    {
        $payload = [
            'url' => $webhook_url
        ];

        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryAttempts) {
            try {
                $response = $this->httpClient->post("/v1/configs/webhook", [
                    'json' => $payload
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                return new PhoneNumberWebhookResponse($data);

            } catch (RequestException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts >= $this->retryAttempts) {
                    break;
                }
                // 等待一段时间后重试
                sleep(pow(2, $attempts));
            } catch (GuzzleException $e) {
                throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new Dialog360Exception(
            '发送消息失败，已重试' . $this->retryAttempts . '次: ' . $lastException->getMessage(),
            0,
            $lastException
        );

    }

    public function getWabaWebhookUrl(): WabaWebhookResponse
    {
        try {
            $response = $this->httpClient->get("/waba_webhook");
            $data = json_decode($response->getBody()->getContents(), true);
            return new WabaWebhookResponse($data);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取媒体信息失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    public function setWabaWebhookUrl(string $webhook_url, array $headers = [], bool $override_all = false): SetWabaWebhookUrlResponse
    {
        $payload = [
            'url' => $webhook_url,
            'headers' => $headers,
            'override_all' => $override_all
        ];

        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryAttempts) {
            try {
                $response = $this->httpClient->post("/waba_webhook", [
                    'json' => $payload
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                //This message means that the Webhook URL will be set within the next 15-20 seconds. Please confirm by fetching the current webhook URL before messaging.
                return new SetWabaWebhookUrlResponse($data);

            } catch (RequestException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts >= $this->retryAttempts) {
                    break;
                }
                // 等待一段时间后重试
                sleep(pow(2, $attempts));
            } catch (GuzzleException $e) {
                throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new Dialog360Exception(
            '发送消息失败，已重试' . $this->retryAttempts . '次: ' . $lastException->getMessage(),
            0,
            $lastException
        );

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