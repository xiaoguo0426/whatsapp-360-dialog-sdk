<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Message\MessageInterface;
use Dialog360\Response\MessageResponse;

/**
 * 消息 API 域：POST /messages
 */
readonly class MessagesApi
{
    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     *  发送消息
     *
     *  4xx 客户端错误保留既有行为：解析 API 错误详情，返回 isSuccess=false 的响应对象，
     *  而不是抛异常。5xx/网络错误重试耗尽后抛 Dialog360Exception。
     * @param MessageInterface $message
     * @return MessageResponse
     * @throws Dialog360Exception
     */
    public function send(MessageInterface $message): MessageResponse
    {
        $payload = $message->toArray();
        $payload['messaging_product'] = 'whatsapp';
        $payload['recipient_type'] = $payload['recipient_type'] ?? 'individual';
        $payload['to'] = $message->getTo();

        try {
            $data = $this->connector->request('POST', '/messages', ['json' => $payload], '发送消息');
        } catch (Dialog360ClientError $e) {
            return new MessageResponse($e->getResponseBody());
        }

        return new MessageResponse($data);
    }
}
