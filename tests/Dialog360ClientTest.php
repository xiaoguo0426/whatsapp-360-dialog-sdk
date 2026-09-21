<?php

namespace Dialog360\Tests;

use Dialog360\Dialog360Client;
use Dialog360\Message\TextMessage;
use Dialog360\Message\MediaMessage;
use Dialog360\Message\TemplateMessage;
use Dialog360\Message\InteractiveMessage;
use Dialog360\Message\ContactsMessage;
use Dialog360\Message\DocumentMessage;
use Dialog360\Message\LocationMessage;
use Dialog360\Message\ReactionMessage;
use Dialog360\Message\StickerMessage;
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

    public function testSendContactsMessage(): void
    {
        $this->mockSuccessResponse('wamid.test-contacts-message-id');

        $contacts = [
            [
                'name' => ContactsMessage::name(
                    formattedName: 'Zhang San',
                    firstName: 'San',
                    lastName: 'Zhang'
                ),
                'birthday' => '1990-01-01',
                'phones' => [
                    ContactsMessage::phone('85268064134', 'CELL'),
                ],
                'emails' => [
                    ContactsMessage::email('zhangsan@example.com', 'WORK'),
                ],
            ],
        ];

        $message = new ContactsMessage('1234567890', $contacts);
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.test-contacts-message-id', $response->getMessageId());

        // 验证请求 payload 结构
        $payload = $this->getLastRequestPayload();
        $this->assertEquals('contacts', $payload['type']);
        $this->assertEquals('whatsapp', $payload['messaging_product']);
        $this->assertEquals('1234567890', $payload['to']);
        $this->assertCount(1, $payload['contacts']);
        $this->assertEquals('Zhang San', $payload['contacts'][0]['name']['formatted_name']);
        $this->assertEquals('San', $payload['contacts'][0]['name']['first_name']);
        $this->assertEquals('85268064134', $payload['contacts'][0]['phones'][0]['phone']);
        $this->assertEquals('CELL', $payload['contacts'][0]['phones'][0]['type']);
        $this->assertEquals('1990-01-01', $payload['contacts'][0]['birthday']);
    }

    public function testContactsMessageRequiresContacts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('联系人数组不能为空');

        new ContactsMessage('1234567890', []);
    }

    public function testContactsMessageRequiresFormattedName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('name.formatted_name');

        new ContactsMessage('1234567890', [
            ['phones' => [['phone' => '85268064134']]]
        ]);
    }

    public function testContactsMessageExceedsMaxContacts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('257');

        $contacts = array_fill(0, 258, ['name' => ['formatted_name' => 'Test User']]);
        new ContactsMessage('1234567890', $contacts);
    }

    public function testSendDocumentMessageByMediaId(): void
    {
        $this->mockSuccessResponse('wamid.test-document-message-id');

        $message = DocumentMessage::fromMediaId(
            '1234567890',
            'test-media-id',
            'Your order confirmation (PDF)',
            'order_abc123.pdf'
        );
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('document', $payload['type']);
        $this->assertEquals('test-media-id', $payload['document']['id']);
        $this->assertEquals('Your order confirmation (PDF)', $payload['document']['caption']);
        $this->assertEquals('order_abc123.pdf', $payload['document']['filename']);
        $this->assertArrayNotHasKey('link', $payload['document']);
    }

    public function testSendDocumentMessageByUrl(): void
    {
        $this->mockSuccessResponse('wamid.test-document-message-id');

        $message = DocumentMessage::fromUrl(
            '1234567890',
            'https://example.com/docs/report.pdf',
            'Monthly report'
        );
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('document', $payload['type']);
        $this->assertEquals('https://example.com/docs/report.pdf', $payload['document']['link']);
        $this->assertArrayNotHasKey('id', $payload['document']);
    }

    public function testDocumentMessageRequiresMediaIdOrLink(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('必须且只能提供 mediaId 或 link 之一');

        new DocumentMessage('1234567890');
    }

    public function testDocumentMessageRejectsBothMediaIdAndLink(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('必须且只能提供 mediaId 或 link 之一');

        new DocumentMessage('1234567890', 'media-id', 'https://example.com/doc.pdf');
    }

    public function testDocumentMessageCaptionTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('caption 不能超过 1024');

        DocumentMessage::fromMediaId('1234567890', 'media-id', str_repeat('a', 1025));
    }

    public function testDocumentMessageUnsupportedExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的文档扩展名');

        DocumentMessage::fromMediaId('1234567890', 'media-id', null, 'archive.zip');
    }

    public function testSendLocationMessage(): void
    {
        $this->mockSuccessResponse('wamid.test-location-message-id');

        $message = LocationMessage::named(
            '1234567890',
            114.1694,
            22.2933,
            'Victoria Harbour',
            'Tsim Sha Tsui, Hong Kong'
        );
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('location', $payload['type']);
        $this->assertEquals(114.1694, $payload['location']['longitude']);
        $this->assertEquals(22.2933, $payload['location']['latitude']);
        $this->assertEquals('Victoria Harbour', $payload['location']['name']);
        $this->assertEquals('Tsim Sha Tsui, Hong Kong', $payload['location']['address']);
    }

    public function testSendLocationCoordinatesOnly(): void
    {
        $this->mockSuccessResponse('wamid.test-location-message-id');

        $message = LocationMessage::coordinates('1234567890', 114.1694, 22.2933);
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('location', $payload['type']);
        $this->assertArrayNotHasKey('name', $payload['location']);
        $this->assertArrayNotHasKey('address', $payload['location']);
    }

    public function testLocationMessageInvalidLatitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('纬度必须在 -90 到 90 之间');

        new LocationMessage('1234567890', 114.1694, 91.0);
    }

    public function testLocationMessageInvalidLongitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('经度必须在 -180 到 180 之间');

        new LocationMessage('1234567890', 181.0, 22.2933);
    }

    public function testLocationMessageAddressRequiresName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('address 仅在提供 name 时才能使用');

        new LocationMessage('1234567890', 114.1694, 22.2933, null, 'Some address');
    }

    public function testSendReactionMessage(): void
    {
        $this->mockSuccessResponse('wamid.test-reaction-message-id');

        $message = ReactionMessage::react('1234567890', 'wamid.test-message-id', '👍');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('reaction', $payload['type']);
        $this->assertEquals('wamid.test-message-id', $payload['reaction']['message_id']);
        $this->assertEquals('👍', $payload['reaction']['emoji']);
    }

    public function testRemoveReactionMessage(): void
    {
        $this->mockSuccessResponse('wamid.test-reaction-message-id');

        $message = ReactionMessage::remove('1234567890', 'wamid.test-message-id');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        // 官方规定移除回应时 emoji 为空字符串
        $payload = $this->getLastRequestPayload();
        $this->assertEquals('', $payload['reaction']['emoji']);
    }

    public function testReactionMessageRequiresMessageId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('message_id 不能为空');

        new ReactionMessage('1234567890', '', '👍');
    }

    public function testSendStickerMessageByMediaId(): void
    {
        $this->mockSuccessResponse('wamid.test-sticker-message-id');

        $message = StickerMessage::fromMediaId('1234567890', 'test-sticker-media-id');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('sticker', $payload['type']);
        $this->assertEquals('test-sticker-media-id', $payload['sticker']['id']);
        $this->assertArrayNotHasKey('link', $payload['sticker']);
    }

    public function testSendStickerMessageByUrl(): void
    {
        $this->mockSuccessResponse('wamid.test-sticker-message-id');

        $message = StickerMessage::fromUrl('1234567890', 'https://example.com/sticker.webp');
        $response = $this->client->sendMessage($message);

        $this->assertTrue($response->isSuccess());

        $payload = $this->getLastRequestPayload();
        $this->assertEquals('sticker', $payload['type']);
        $this->assertEquals('https://example.com/sticker.webp', $payload['sticker']['link']);
        $this->assertArrayNotHasKey('id', $payload['sticker']);
    }

    public function testStickerMessageRequiresMediaIdOrLink(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('必须且只能提供 mediaId 或 link 之一');

        new StickerMessage('1234567890');
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
        // 网络错误（无响应）属于暂时性错误，会触发客户端重试；测试中使用 1 次重试上限以快速验证
        $client = new Dialog360Client(
            'test-api-key',
            'test-phone-number-id',
            'https://waba-v2.360dialog.io',
            30,
            1,
            HandlerStack::create($this->mockHandler)
        );

        $this->mockHandler->append(
            new RequestException('Network error', new Request('POST', '/messages'))
        );

        $message = new TextMessage('1234567890', 'Hello World!');

        $this->expectException(Dialog360Exception::class);
        $this->expectExceptionMessage('发送消息失败，已重试1次: Network error');

        $client->sendMessage($message);
    }

    public function testSendMessageRetriesOnServerError(): void
    {
        // 5xx 属于暂时性错误，重试后成功
        $client = new Dialog360Client(
            'test-api-key',
            'test-phone-number-id',
            'https://waba-v2.360dialog.io',
            30,
            2,
            HandlerStack::create($this->mockHandler)
        );

        // 第一次 500 触发重试，第二次成功
        $this->mockHandler->append(new Response(500, [], 'Internal Server Error'));
        $this->mockSuccessResponse('wamid.test-retry-message-id');

        $message = new TextMessage('1234567890', 'Hello World!');
        $response = $client->sendMessage($message);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.test-retry-message-id', $response->getMessageId());
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

    /**
     * 模拟消息发送成功响应
     */
    private function mockSuccessResponse(string $messageId): void
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
                    ['id' => $messageId]
                ]
            ]))
        );
    }

    /**
     * 获取最后一次请求的 payload
     */
    private function getLastRequestPayload(): array
    {
        return json_decode($this->mockHandler->getLastRequest()->getBody()->getContents(), true);
    }
} 