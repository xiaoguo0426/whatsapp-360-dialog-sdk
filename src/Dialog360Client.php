<?php

namespace Dialog360;

use Dialog360\Exception\Dialog360Exception;
use Dialog360\Message\MessageInterface;
use Dialog360\Response\MessageResponse;
use Dialog360\Response\MessageStatusResponse;
use Dialog360\Response\MediaResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class Dialog360Client
{
    private string $apiKey;
    private string $phoneNumberId;
    private string $baseUrl;
    private int $timeout;
    private int $retryAttempts;
    private Client $httpClient;

    public function __construct(
        string $apiKey,
        string $phoneNumberId,
        string $baseUrl = 'https://waba-api.360dialog.io',
        int $timeout = 30,
        int $retryAttempts = 3
    ) {
        $this->apiKey = $apiKey;
        $this->phoneNumberId = $phoneNumberId;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;
        
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
//                'User-Agent' => 'Dialog360-PHP-SDK/1.0.0'
            ]
        ]);
    }

    /**
     * 发送消息
     */
    public function sendMessage(MessageInterface $message): MessageResponse
    {
        $payload = $message->toArray();
        $payload['messaging_product'] = 'whatsapp';
        $payload['to'] = $message->getTo();

        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryAttempts) {
            try {
                $response = $this->httpClient->post("/v1/messages", [
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
     * 获取消息状态
     */
    public function getMessageStatus(string $messageId): MessageStatusResponse
    {
        try {
            $response = $this->httpClient->get("/v1/messages/{$messageId}");
            $data = json_decode($response->getBody()->getContents(), true);
            return new MessageStatusResponse($data);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取消息状态失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取媒体文件信息
     */
    public function getMediaInfo(string $mediaId): MediaResponse
    {
        try {
            $response = $this->httpClient->get("/v1/media/{$mediaId}");
            $data = json_decode($response->getBody()->getContents(), true);
            return new MediaResponse($data);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取媒体信息失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 下载媒体文件
     */
    public function downloadMedia(string $mediaId, string $savePath = null): string
    {
        try {
            $mediaInfo = $this->getMediaInfo($mediaId);
            $downloadUrl = $mediaInfo->getUrl();
            
            $response = $this->httpClient->get($downloadUrl);
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
     * 获取电话号码信息
     */
    public function getPhoneNumberInfo(): array
    {
        try {
            $response = $this->httpClient->get("/v1/{$this->phoneNumberId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取电话号码信息失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取可用的模板
     */
    public function getTemplates(): array
    {
        try {
            $response = $this->httpClient->get("/v1/{$this->phoneNumberId}/message_templates");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取模板失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取API密钥信息
     */
    public function getApiKeyInfo(): array
    {
        try {
            $response = $this->httpClient->get("/v1/account");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new Dialog360Exception('获取API密钥信息失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
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