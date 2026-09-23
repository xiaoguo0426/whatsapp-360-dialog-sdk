<?php

namespace Dialog360\Tests;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApiConnectorTest extends TestCase
{
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
    }

    private function createConnector(int $retryAttempts = 3): ApiConnector
    {
        $client = new Client([
            'base_uri' => 'https://waba-v2.360dialog.io',
            'handler' => HandlerStack::create($this->mockHandler)
        ]);

        return new ApiConnector($client, $retryAttempts);
    }

    private function jsonBody(array $data): string
    {
        return (string) json_encode($data);
    }

    public function testRequestReturnsDecodedJson(): void
    {
        $this->mockHandler->append(new Response(200, [], $this->jsonBody(['ok' => true])));
        $connector = $this->createConnector();

        $result = $connector->request('POST', '/messages', ['json' => []], '发送消息');

        $this->assertSame(['ok' => true], $result);
    }

    public function testRequestThrowsClientErrorOn4xxWithoutRetry(): void
    {
        // 只入队一个 4xx 响应：如果发生重试，MockHandler 将抛出队列耗尽异常导致测试失败
        $this->mockHandler->append(new Response(400, [], $this->jsonBody([
            'errors' => [
                ['code' => 'invalid_param', 'message' => 'Invalid parameter']
            ]
        ])));
        $connector = $this->createConnector(3);

        try {
            $connector->request('POST', '/messages', ['json' => []], '发送消息');
            $this->fail('应抛出 Dialog360ClientError');
        } catch (Dialog360ClientError $e) {
            $this->assertSame(400, $e->getStatusCode());
            $this->assertSame('Invalid parameter', $e->getResponseBody()['errors'][0]['message']);
            $this->assertSame(0, $this->mockHandler->count(), '4xx 客户端错误不应重试');
        }
    }

    public function testRequestRetriesOn5xxThenSucceeds(): void
    {
        $this->mockHandler->append(new Response(500, [], 'Internal Server Error'));
        $this->mockHandler->append(new Response(200, [], $this->jsonBody(['ok' => true])));
        $connector = $this->createConnector(2);

        $result = $connector->request('POST', '/messages', ['json' => []], '发送消息');

        $this->assertSame(['ok' => true], $result);
    }

    public function testRequestThrowsWithActionMessageWhenRetriesExhausted(): void
    {
        $this->mockHandler->append(new RequestException('boom', new Request('POST', '/messages')));
        $this->mockHandler->append(new RequestException('boom', new Request('POST', '/messages')));
        $connector = $this->createConnector(2);

        $this->expectException(Dialog360Exception::class);
        $this->expectExceptionMessage('发送消息失败，已重试2次: boom');

        $connector->request('POST', '/messages', ['json' => []], '发送消息');
    }

    public function testGetThrowsClientErrorOn4xx(): void
    {
        $this->mockHandler->append(new Response(404, [], $this->jsonBody([
            'error' => ['message' => 'Not found']
        ])));
        $connector = $this->createConnector();

        try {
            $connector->get('/health_status', [], '获取健康状态');
            $this->fail('应抛出 Dialog360ClientError');
        } catch (Dialog360ClientError $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertSame('Not found', $e->getResponseBody()['error']['message']);
        }
    }

    public function testGetDoesNotRetryAndThrowsOn5xx(): void
    {
        $this->mockHandler->append(new Response(500, [], 'Internal Server Error'));
        $connector = $this->createConnector(3);

        $this->expectException(Dialog360Exception::class);
        $this->expectExceptionMessage('获取健康状态失败');

        $connector->get('/health_status', [], '获取健康状态');
    }

    public function testGetRawReturnsPsr7Response(): void
    {
        $this->mockHandler->append(new Response(200, [], 'binary-content'));
        $connector = $this->createConnector();

        $response = $connector->getRaw('/download/path');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('binary-content', $response->getBody()->getContents());
    }
}
