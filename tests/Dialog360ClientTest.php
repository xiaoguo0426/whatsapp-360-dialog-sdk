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
            'https://waba-v2.360dialog.io',
            30,
            3,
            $handlerStack
        );
    }

    public function testSendTextMessageSuccess(): void
    {
        // Cloud API v2 响应结构
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => 'wamid.test-message-id']
                ]
            ]))
        );

        $message = new TextMessage('1234567890', 'Hello World!');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.test-message-id', $response->getMessageId());
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
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => 'wamid.test-media-message-id']
                ]
            ]))
        );

        $message = MediaMessage::image('1234567890', 'https://example.com/image.jpg', 'Beautiful image!');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.test-media-message-id', $response->getMessageId());
    }

    public function testSendTemplateMessage(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => 'wamid.test-template-message-id']
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
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '1234567890',
                        'wa_id' => '1234567890'
                    ]
                ],
                'messages' => [
                    ['id' => 'wamid.test-interactive-message-id']
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

    public function testGetHealthStatus(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'health_status' => [
                    'can_send_message' => 'AVAILABLE',
                    'entities' => [
                        [
                            'entity_type' => 'PHONE_NUMBER',
                            'id' => '106540352242922',
                            'can_send_message' => 'AVAILABLE'
                        ],
                        [
                            'entity_type' => 'WABA',
                            'id' => '102290129340398',
                            'can_send_message' => 'AVAILABLE'
                        ]
                    ]
                ],
                'id' => '106540352242922'
            ]))
        );

        $health = $this->client->getHealthStatus();
        $this->assertEquals('AVAILABLE', $health['health_status']['can_send_message']);
        $this->assertCount(2, $health['health_status']['entities']);
    }

    public function testGetMediaInfo(): void
    {
        // Cloud API v2 媒体响应结构
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'id' => 'test-media-id',
                'url' => 'https://lookaside.fbsbx.com/whatsapp_business/attachments/?mid=130345565692730173924&ext=1664537344507&hash=ATtBt0Cdio',
                'mime_type' => 'image/jpeg',
                'sha256' => 'test-sha256',
                'file_size' => '1024'
            ]))
        );

        $media = $this->client->getMediaInfo('test-media-id');
        
        $this->assertEquals('test-media-id', $media->getMediaId());
        $this->assertStringContainsString('lookaside.fbsbx.com', $media->getUrl());
        $this->assertEquals('image/jpeg', $media->getMimeType());
        $this->assertEquals(1024, $media->getFileSize());
        $this->assertTrue($media->isImage());
    }

    public function testNetworkError(): void
    {
        $this->mockHandler->append(
            new RequestException('Network error', new Request('POST', '/messages'))
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
        $this->assertEquals('https://waba-v2.360dialog.io', $config['baseUrl']);
        $this->assertEquals(30, $config['timeout']);
        $this->assertEquals(3, $config['retryAttempts']);
    }

    public function testUnsupportedMethods(): void
    {
        // 测试不再支持的方法抛出异常
        $this->expectException(Dialog360Exception::class);
        $this->expectExceptionMessage('Cloud API 暂不支持通过 Messaging API 获取电话号码信息');
        
        $this->client->getPhoneNumberInfo();
    }
} 