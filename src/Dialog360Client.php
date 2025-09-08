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
     * 上传媒体文件（Cloud API: POST /media）
     * @param string $filePath 本地文件路径
     * @param string $mimeType MIME类型
     * @return string 返回媒体ID
     * @throws Dialog360Exception
     */
    public function uploadMedia(string $filePath, string $mimeType): string
    {
        if (!file_exists($filePath)) {
            throw new Dialog360Exception('文件不存在: ' . $filePath);
        }

        // 验证文件大小和类型
        $this->validateMediaFile($filePath, $mimeType);

        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryAttempts) {
            try {
                $response = $this->httpClient->post('/media', [
                    'multipart' => [
                        [
                            'name' => 'messaging_product',
                            'contents' => 'whatsapp'
                        ],
                        [
                            'name' => 'file',
                            'contents' => fopen($filePath, 'r'),
                            'filename' => basename($filePath),
                            'type' => $mimeType
                        ]
                    ]
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                
                if (!isset($data['id'])) {
                    throw new Dialog360Exception('上传响应中缺少媒体ID');
                }

                return $data['id'];

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
            '上传媒体文件失败，已重试' . $this->retryAttempts . '次: ' . $lastException->getMessage(),
            0,
            $lastException
        );
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
     * @param string $downloadUrl
     * @param string $savePath
     * @return bool
     * @throws Dialog360Exception
     * @throws GuzzleException
     */
    public function downloadMediaFile(string $downloadUrl,string $savePath): bool
    {
        // Cloud API 指南：将 lookaside 主机替换为 waba-v2 根域后面的路径
        $parsed = parse_url($downloadUrl);
        if (!$parsed || !isset($parsed['path'])) {
            throw new Dialog360Exception('媒体下载URL无效');
        }
        $path = $parsed['path'] . (isset($parsed['query']) ? ('?' . $parsed['query']) : '');

        // 通过相对路径请求（自动带上 D360-API-KEY 头）
        $response = $this->httpClient->get($path);
        $content = $response->getBody()->getContents();

        return (bool)file_put_contents($savePath, $content);
    }

    /**
     * 删除媒体文件（Cloud API: DELETE /{media-id}）
     * @param string $mediaId
     * @return bool
     * @throws Dialog360Exception
     */
    public function deleteMedia(string $mediaId): bool
    {
        try {
            $response = $this->httpClient->delete("/{$mediaId}");
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            throw new Dialog360Exception('删除媒体文件失败: ' . $e->getMessage(), 0, $e);
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
     * 验证媒体文件
     * @param string $filePath
     * @param string $mimeType
     * @throws Dialog360Exception
     */
    private function validateMediaFile(string $filePath, string $mimeType): void
    {
        $fileSize = filesize($filePath);
        
        // 根据文档定义的文件大小限制
        $sizeLimits = [
            'audio' => 16 * 1024 * 1024, // 16MB
            'image' => 5 * 1024 * 1024,  // 5MB
            'video' => 16 * 1024 * 1024, // 16MB
            'document' => 100 * 1024 * 1024, // 100MB
            'sticker' => 500 * 1024, // 500KB (动画贴纸)
        ];

        // 检查文件大小
        if ($fileSize > 100 * 1024 * 1024) { // 最大100MB
            throw new Dialog360Exception('文件大小超过100MB限制');
        }

        // 根据MIME类型检查特定限制
        if (strpos($mimeType, 'audio/') === 0 && $fileSize > $sizeLimits['audio']) {
            throw new Dialog360Exception('音频文件大小超过16MB限制');
        }
        
        if (strpos($mimeType, 'image/') === 0) {
            if ($mimeType === 'image/webp' && $fileSize > $sizeLimits['sticker']) {
                throw new Dialog360Exception('贴纸文件大小超过500KB限制');
            }
            if ($mimeType !== 'image/webp' && $fileSize > $sizeLimits['image']) {
                throw new Dialog360Exception('图片文件大小超过5MB限制');
            }
        }
        
        if (strpos($mimeType, 'video/') === 0 && $fileSize > $sizeLimits['video']) {
            throw new Dialog360Exception('视频文件大小超过16MB限制');
        }

        // 验证支持的MIME类型（支持带codecs参数的格式）
        $supportedTypes = [
            // 音频
            'audio/aac', 'audio/amr', 'audio/mpeg', 'audio/mp4', 'audio/ogg',
            'audio/ogg; codecs=opus', 'audio/ogg; codecs=vorbis',
            // 图片
            'image/jpeg', 'image/png', 'image/webp',
            // 视频
            'video/mp4', 'video/3gp',
            // 文档
            'text/plain', 'application/pdf', 'application/vnd.ms-powerpoint',
            'application/msword', 'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        // 检查基础MIME类型（忽略codecs参数）
        $baseMimeType = explode(';', $mimeType)[0];
        $isSupported = false;
        
        foreach ($supportedTypes as $supportedType) {
            $baseSupportedType = explode(';', $supportedType)[0];
            if ($baseMimeType === $baseSupportedType) {
                $isSupported = true;
                break;
            }
        }

        if (!$isSupported) {
            throw new Dialog360Exception('不支持的媒体类型: ' . $mimeType);
        }
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