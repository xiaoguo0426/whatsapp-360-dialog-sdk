<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\PhoneNumberWebhookResponse;
use Dialog360\Response\SetWabaWebhookUrlResponse;
use Dialog360\Response\WabaWebhookResponse;

/**
 * Webhook 配置 API 域：
 * - 电话号码级：/v1/configs/webhook
 * - WABA 级：/waba_webhook
 */
readonly class WebhookApi
{
    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 获取电话号码Webhook URL（Cloud API: GET /v1/configs/webhook）
     * @return PhoneNumberWebhookResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getUrl(): PhoneNumberWebhookResponse
    {
        $data = $this->connector->get('/v1/configs/webhook', [], '获取电话号码Webhook URL');
        return new PhoneNumberWebhookResponse($data);
    }

    /**
     * 设置电话号码Webhook URL（Cloud API: POST /v1/configs/webhook）
     * @param string $webhookUrl
     * @return PhoneNumberWebhookResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function setUrl(string $webhookUrl): PhoneNumberWebhookResponse
    {
        $data = $this->connector->request('POST', '/v1/configs/webhook', [
            'json' => ['url' => $webhookUrl]
        ], '设置Webhook URL');

        return new PhoneNumberWebhookResponse($data);
    }

    /**
     * 获取WABA级Webhook配置（Cloud API: GET /waba_webhook）
     * @return WabaWebhookResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getWabaUrl(): WabaWebhookResponse
    {
        $data = $this->connector->get('/waba_webhook', [], '获取WABA Webhook URL');
        return new WabaWebhookResponse($data);
    }

    /**
     * 设置WABA级Webhook（Cloud API: POST /waba_webhook）
     * @param string $webhookUrl
     * @param array $headers
     * @param bool $overrideAll
     * @return SetWabaWebhookUrlResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function setWabaUrl(string $webhookUrl, array $headers = [], bool $overrideAll = false): SetWabaWebhookUrlResponse
    {
        $payload = [
            'url' => $webhookUrl,
            'headers' => $headers,
            'override_all' => $overrideAll
        ];

        $data = $this->connector->request('POST', '/waba_webhook', ['json' => $payload], '设置WABA Webhook URL');

        //This message means that the Webhook URL will be set within the next 15-20 seconds. Please confirm by fetching the current webhook URL before messaging.
        return new SetWabaWebhookUrlResponse($data);
    }
}
