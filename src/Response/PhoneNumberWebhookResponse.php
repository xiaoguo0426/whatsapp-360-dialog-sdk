<?php

namespace Dialog360\Response;

class PhoneNumberWebhookResponse
{
    private array $data;

    private array $headers;

    private string $url;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->headers = $data['headers'] ?: [];

        $this->url = $data['url'] ?: '';
    }

    /**
     * @return array
     */

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 转换为数组
     * @return array
     */
    public function toArray(): array
    {
        return [
            'headers' => $this->getHeaders(),
            'url' => $this->getUrl()
        ];
    }
} 