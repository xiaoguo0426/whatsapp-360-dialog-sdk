<?php

namespace Dialog360\Tests;

use Dialog360\Dialog360Client;
use Dialog360\Message\TextMessage;
use Dialog360\Message\MediaMessage;
use Dialog360\Message\TemplateMessage;
use Dialog360\Message\InteractiveMessage;
use Dialog360\Exception\Dialog360Exception;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class Dialog360ClientTest extends TestCase
{
    private Dialog360Client $client;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        
        $this->client = new Dialog360Client(
            'test-api-key',
            'test-phone-number-id',
            'https://waba-api.360dialog.io',
            30,
            3
        );
    }

    public function testSendTextMessageSuccess(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messages' => [
                    ['id' => 'test-message-id']
                ]
            ]))
        );

        $message = new TextMessage('1234567890', 'Hello World!');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('test-message-id', $response->getMessageId());
    }

    public function testSendTextMessageFailure(): void
    {
        $this->mockHandler->append(
            new Response(400, [], json_encode([
                'errors' => [
                    [
                        'code' => 'invalid_phone_number',
                        'message' => 'Invalid phone number'
                    ]
                ]
            ]))
        );

        $message = new TextMessage('invalid-number', 'Hello World!');
        $response = $this->client->sendMessage($message);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals('invalid_phone_number', $response->getErrorCode());
        $this->assertEquals('Invalid phone number', $response->getErrorMessage());
    }

    public function testSendMediaMessage(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messages' => [
                    ['id' => 'test-media-message-id']
                ]
            ]))
        );

        $message = MediaMessage::image('1234567890', 'https://example.com/image.jpg', 'Beautiful image!');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('test-media-message-id', $response->getMessageId());
    }

    public function testSendTemplateMessage(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messages' => [
                    ['id' => 'test-template-message-id']
                ]
            ]))
        );

        $message = new TemplateMessage(
            '1234567890',
            'hello_world',
            'en_US',
            [
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => 'John'
                        ]
                    ]
                ]
            ]
        );
        
        $response = $this->client->sendMessage($message);
        $this->assertTrue($response->isSuccess());
    }

    public function testSendInteractiveMessage(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messages' => [
                    ['id' => 'test-interactive-message-id']
                ]
            ]))
        );

        $message = InteractiveMessage::button(
            '1234567890',
            'Choose an option:',
            [
                [
                    'type' => 'reply',
                    'reply' => [
                        'id' => 'btn_1',
                        'title' => 'Option 1'
                    ]
                ]
            ]
        );
        
        $response = $this->client->sendMessage($message);
        $this->assertTrue($response->isSuccess());
    }

    public function testGetMessageStatus(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'id' => 'test-message-id',
                'status' => 'delivered',
                'timestamp' => '2023-01-01T12:00:00Z'
            ]))
        );

        $status = $this->client->getMessageStatus('test-message-id');
        
        $this->assertEquals('test-message-id', $status->getMessageId());
        $this->assertEquals('delivered', $status->getStatus());
        $this->assertTrue($status->isDelivered());
    }

    public function testGetMediaInfo(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'id' => 'test-media-id',
                'url' => 'https://example.com/media.jpg',
                'mime_type' => 'image/jpeg',
                'sha256' => 'test-sha256',
                'file_size' => 1024
            ]))
        );

        $media = $this->client->getMediaInfo('test-media-id');
        
        $this->assertEquals('test-media-id', $media->getMediaId());
        $this->assertEquals('https://example.com/media.jpg', $media->getUrl());
        $this->assertEquals('image/jpeg', $media->getMimeType());
        $this->assertTrue($media->isImage());
    }

    public function testNetworkError(): void
    {
        $this->mockHandler->append(
            new RequestException('Network error', new Request('POST', '/v1/messages'))
        );

        $message = new TextMessage('1234567890', 'Hello World!');
        
        $this->expectException(Dialog360Exception::class);
        $this->expectExceptionMessage('网络请求失败: Network error');
        
        $this->client->sendMessage($message);
    }

    public function testClientConfiguration(): void
    {
        $config = $this->client->getConfig();
        
        $this->assertEquals('test-api-key', $config['apiKey']);
        $this->assertEquals('test-phone-number-id', $config['phoneNumberId']);
        $this->assertEquals('https://waba-api.360dialog.io', $config['baseUrl']);
        $this->assertEquals(30, $config['timeout']);
        $this->assertEquals(3, $config['retryAttempts']);
    }
} 