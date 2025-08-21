<?php

namespace Dialog360\Response;

class WabaWebhookResponse
{
    private array $data;

    private string $waba_id;

    private array $phone_numbers;

    private string $url;

    private array $headers;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->waba_id = $data['waba_id'];

        $this->phone_numbers = $data['phone_numbers'];

        $this->url = $data['url'] ?: '';

        $this->headers = $data['headers'] ?: [];

    }

    /**
     * @return string
     */
    public function getWabaId(): string
    {
        return $this->waba_id;
    }

    /**
     * @return array
     */
    public function getPhoneNumbers(): array
    {
        return $this->phone_numbers;
    }

    /**
     * @return array
     */

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

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'url' => $this->getUrl(),
            'headers' => $this->getHeaders(),
            'phone_numbers' => $this->getPhoneNumbers(),
            'waba_id' => $this->getWabaId(),
        ];
    }
} 